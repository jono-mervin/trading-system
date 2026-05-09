<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/ai_client.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$amount = (float) ($_POST['amount'] ?? 0);
$paymentType = $_POST['payment_type'] ?? 'gcash';
if ($amount <= 0) {
    exit('Invalid amount.');
}

$idempotencyKey = bin2hex(random_bytes(16));
$methodId = $paymentType === 'bank' ? 2 : 1;

$stmt = db()->prepare('
    INSERT INTO payments (user_id, amount, method_id, provider, external_reference, payment_type, status, idempotency_key)
    VALUES (:user_id, :amount, :method_id, :provider, :external_reference, :payment_type, :status, :idempotency_key)
');
$stmt->execute([
    'user_id' => $user['id'],
    'amount' => $amount,
    'method_id' => $methodId,
    'provider' => 'paymongo',
    'external_reference' => 'mock_' . time(),
    'payment_type' => $paymentType,
    'status' => 'completed',
    'idempotency_key' => $idempotencyKey,
]);

$paymentId = (int) db()->lastInsertId();
add_ledger_entry((int) $user['id'], 'deposit', $amount, $paymentId, 'Webhook simulated');
log_audit((int) $user['id'], 'deposit_completed', 'Payment ID: ' . $paymentId);

$risk = request_risk_score([
    'user_id' => (int) $user['id'],
    'transaction_type' => 'deposit',
    'amount' => $amount,
    'transactions_last_hour' => 1,
]);
if (($risk['risk_level'] ?? '') === 'high') {
    $flagStmt = db()->prepare('INSERT INTO fraud_flags (user_id, reason, severity) VALUES (:user_id, :reason, :severity)');
    $flagStmt->execute([
        'user_id' => $user['id'],
        'reason' => 'High AI risk score on deposit: ' . ($risk['reason'] ?? 'N/A'),
        'severity' => 'high',
    ]);
}

header('Location: ' . APP_URL . '/trader/wallet.php');
