<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    $user = require_role('admin');
    $withdrawalId = (int) ($_GET['id'] ?? 0);

    $stmt = db()->prepare('
        SELECT w.*, u.name, u.email, b.bank_name, b.account_name, b.account_number
        FROM withdrawals w
        JOIN users u ON u.id = w.user_id
        LEFT JOIN bank_accounts b ON b.id = w.bank_account_id
        WHERE w.id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $withdrawalId]);
    $withdrawal = $stmt->fetch();

    if (!$withdrawal) {
        throw new Exception('Withdrawal not found');
    }

    echo json_encode([
        'success' => true,
        'withdrawal' => $withdrawal
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
