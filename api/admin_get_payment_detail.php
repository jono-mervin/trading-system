<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    $user = require_role('admin');
    $paymentId = (int) ($_GET['id'] ?? 0);

    $stmt = db()->prepare('
        SELECT p.*, u.name, u.email
        FROM payments p
        JOIN users u ON u.id = p.user_id
        WHERE p.id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    $logsStmt = db()->prepare('SELECT event_type, created_at, payload_json FROM payment_logs WHERE source_id = :source_id ORDER BY created_at DESC');
    $logsStmt->execute(['source_id' => $payment['external_reference']]);
    $logs = $logsStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'payment' => $payment,
        'logs' => $logs
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
