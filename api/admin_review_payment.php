<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/ai_client.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fraud_rules.php';
require_once __DIR__ . '/../includes/flash.php';

$admin = require_role('admin');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
    header('Location: ' . APP_URL . '/admin/payments.php');
    exit;
}

$paymentId = (int) ($_POST['payment_id'] ?? 0);
$status = $_POST['status'] ?? 'failed';
if (!in_array($status, ['completed', 'failed'], true)) {
    set_flash('error', 'Invalid payment status.');
    header('Location: ' . APP_URL . '/admin/payments.php');
    exit;
}

$stmt = db()->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $paymentId]);
$payment = $stmt->fetch();
if (!$payment || $payment['provider'] !== 'workflow') {
    set_flash('error', 'Payment not found for workflow.');
    header('Location: ' . APP_URL . '/admin/payments.php');
    exit;
}
if ($payment['status'] !== 'pending') {
    set_flash('error', 'Payment is not pending.');
    header('Location: ' . APP_URL . '/admin/payments.php');
    exit;
}

$pdo = db();
$pdo->beginTransaction();
try {
    $eventId = 'wf_admin_' . $paymentId . '_' . time();
    $payload = json_encode([
        'payment_id' => $paymentId,
        'admin_id' => (int) $admin['id'],
        'status' => $status,
    ], JSON_THROW_ON_ERROR);

    $logStmt = $pdo->prepare('
        INSERT INTO payment_logs (event_id, event_type, source_id, payload_json)
        VALUES (:event_id, :event_type, :source_id, :payload_json)
    ');
    $logStmt->execute([
        'event_id' => $eventId,
        'event_type' => $status === 'completed' ? 'workflow.admin_approved' : 'workflow.admin_rejected',
        'source_id' => (string) $payment['external_reference'],
        'payload_json' => $payload,
    ]);

    $upd = $pdo->prepare('UPDATE payments SET status = :status WHERE id = :id');
    $upd->execute(['status' => $status, 'id' => $paymentId]);

    if ($status === 'completed') {
        add_ledger_entry((int) $payment['user_id'], 'deposit', (float) $payment['amount'], $paymentId, 'Admin approved workflow deposit');
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
                'reason' => 'High AI risk score on admin-approved workflow deposit: ' . ($risk['reason'] ?? 'N/A'),
                'severity' => 'high',
            ]);
        }
        detect_rapid_transactions((int) $payment['user_id']);
    } else {
        detect_multiple_failed_payments((int) $payment['user_id']);
    }

    log_audit((int) $admin['id'], 'payment_' . $status, 'Payment ID: ' . $paymentId);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Failed to review payment.');
    header('Location: ' . APP_URL . '/admin/payments.php');
    exit;
}

set_flash('success', 'Payment review completed.');
header('Location: ' . APP_URL . '/admin/payments.php');
