<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('trader');

// Fetch Payments
$paymentsStmt = db()->prepare('SELECT amount, payment_type, status, created_at FROM payments WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10');
$paymentsStmt->execute(['user_id' => $user['id']]);
$payments = $paymentsStmt->fetchAll();

// Fetch Ledger
$ledgerStmt = db()->prepare('SELECT type, amount, balance_after, created_at FROM wallet_ledger WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10');
$ledgerStmt->execute(['user_id' => $user['id']]);
$ledger = $ledgerStmt->fetchAll();

header('Content-Type: application/json');
echo json_encode([
    'payments' => $payments,
    'ledger' => $ledger
]);
