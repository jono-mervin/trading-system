<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$bankName = trim((string) ($_POST['bank_name'] ?? ''));
$accountName = trim((string) ($_POST['account_name'] ?? ''));
$accountNumber = preg_replace('/\s+/', '', (string) ($_POST['account_number'] ?? ''));
if ($bankName === '' || $accountName === '' || $accountNumber === '') {
    exit('All bank account fields are required.');
}

$stmt = db()->prepare('
    INSERT INTO bank_accounts (user_id, bank_name, account_name, account_number)
    VALUES (:user_id, :bank_name, :account_name, :account_number)
');
$stmt->execute([
    'user_id' => $user['id'],
    'bank_name' => $bankName,
    'account_name' => $accountName,
    'account_number' => $accountNumber,
]);

log_audit((int) $user['id'], 'bank_account_added', 'Bank account added');
header('Location: ' . APP_URL . '/trader/bank_accounts.php');
