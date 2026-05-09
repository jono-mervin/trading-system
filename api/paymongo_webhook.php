<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/ai_client.php';
require_once __DIR__ . '/../includes/paymongo.php';
require_once __DIR__ . '/../includes/fraud_rules.php';

function extract_source_id(array $payload): string
{
    return (string) (
        $payload['data']['attributes']['data']['id']
        ?? $payload['data']['attributes']['data']['attributes']['source']['id']
        ?? ''
    );
}

$creditOnPaidEvents = ['payment.paid', 'source.paid'];
$failedEvents = ['payment.failed', 'source.failed'];

$rawBody = file_get_contents('php://input') ?: '';
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

if (!paymongo_verify_signature($rawBody, $signatureHeader)) {
    http_response_code(401);
    echo 'Invalid signature.';
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo 'Invalid payload.';
    exit;
}

$eventId = (string) ($payload['data']['id'] ?? '');
$eventType = (string) ($payload['data']['attributes']['type'] ?? '');
$sourceId = extract_source_id($payload);

if ($eventId === '' || $sourceId === '') {
    http_response_code(400);
    echo 'Missing event metadata.';
    exit;
}

$pdo = db();
$pdo->beginTransaction();

try {
    $logStmt = $pdo->prepare('
        INSERT INTO payment_logs (event_id, event_type, source_id, payload_json)
        VALUES (:event_id, :event_type, :source_id, :payload_json)
    ');
    $logStmt->execute([
        'event_id' => $eventId,
        'event_type' => $eventType,
        'source_id' => $sourceId,
        'payload_json' => $rawBody,
    ]);

    if (!in_array($eventType, array_merge(['source.chargeable'], $creditOnPaidEvents, $failedEvents), true)) {
        $pdo->commit();
        echo 'Event logged.';
        exit;
    }

    $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE external_reference = :source_id LIMIT 1');
    $paymentStmt->execute(['source_id' => $sourceId]);
    $payment = $paymentStmt->fetch();
    if (!$payment) {
        throw new RuntimeException('Payment record not found for source.');
    }

    if ($payment['status'] === 'completed') {
        $pdo->commit();
        echo 'Already completed.';
        exit;
    }

    if ($eventType === 'source.chargeable') {
        $amountCentavos = (int) ((float) $payment['amount'] * 100);
        paymongo_create_payment_from_source($sourceId, $amountCentavos, 'PHP');

        $upd = $pdo->prepare('UPDATE payments SET status = :status WHERE id = :id');
        $upd->execute(['status' => 'pending', 'id' => $payment['id']]);
        $pdo->commit();
        echo 'Payment creation triggered.';
        exit;
    }

    if (in_array($eventType, $failedEvents, true)) {
        $upd = $pdo->prepare('UPDATE payments SET status = :status WHERE id = :id');
        $upd->execute(['status' => 'failed', 'id' => $payment['id']]);
        detect_multiple_failed_payments((int) $payment['user_id']);
        $pdo->commit();
        echo 'Payment marked failed.';
        exit;
    }

    if (!in_array($eventType, $creditOnPaidEvents, true)) {
        $pdo->commit();
        echo 'Ignored event.';
        exit;
    }

    $upd = $pdo->prepare('UPDATE payments SET status = :status WHERE id = :id');
    $upd->execute(['status' => 'completed', 'id' => $payment['id']]);

    add_ledger_entry((int) $payment['user_id'], 'deposit', (float) $payment['amount'], (int) $payment['id'], 'PayMongo webhook');
    log_audit((int) $payment['user_id'], 'deposit_completed', 'PayMongo source: ' . $sourceId);

    $risk = request_risk_score([
        'user_id' => (int) $payment['user_id'],
        'transaction_type' => 'deposit',
        'amount' => (float) $payment['amount'],
        'transactions_last_hour' => 1,
    ]);
    if (($risk['risk_level'] ?? '') === 'high') {
        $flagStmt = $pdo->prepare('INSERT INTO fraud_flags (user_id, reason, severity) VALUES (:user_id, :reason, :severity)');
        $flagStmt->execute([
            'user_id' => $payment['user_id'],
            'reason' => 'High AI risk score on deposit: ' . ($risk['reason'] ?? 'N/A'),
            'severity' => 'high',
        ]);
    }
    detect_rapid_transactions((int) $payment['user_id']);

    $pdo->commit();
    echo 'Deposit completed.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $isDuplicate = $e instanceof PDOException && (($e->errorInfo[0] ?? '') === '23000');
    if ($isDuplicate) {
        echo 'Event already processed.';
        exit;
    }

    http_response_code(500);
    echo 'Webhook processing failed.';
}
