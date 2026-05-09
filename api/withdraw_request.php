<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/fraud_rules.php';

$user = require_role('trader');
rate_limit_or_fail('withdraw_request_' . (string) $user['id'], 10, 60);
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$amount = (float) ($_POST['amount'] ?? 0);
$destinationType = $_POST['destination_type'] ?? 'bank';
$destinationValue = trim((string) ($_POST['destination_value'] ?? ''));
$bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
if ($amount <= 0) {
    exit('Invalid withdrawal request.');
}

if (!in_array($destinationType, ['bank', 'ewallet'], true)) {
    exit('Invalid destination type.');
}

if ($destinationType === 'bank') {
    if ($bankAccountId <= 0) {
        exit('Please select a saved bank account.');
    }

    $bankStmt = db()->prepare('
        SELECT bank_name, account_name, account_number
        FROM bank_accounts
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ');
    $bankStmt->execute(['id' => $bankAccountId, 'user_id' => $user['id']]);
    $bank = $bankStmt->fetch();
    if (!$bank) {
        exit('Selected bank account is invalid.');
    }

    $destinationValue = $bank['bank_name'] . ' - ' . $bank['account_name'] . ' (' . $bank['account_number'] . ')';
} elseif ($destinationValue === '') {
    exit('E-wallet number is required.');
}

if (($user['status'] ?? 'pending') !== 'verified') {
    exit('Withdrawal blocked: KYC not verified.');
}

$balance = get_wallet_balance((int) $user['id']);
if ($balance < $amount) {
    exit('Insufficient balance.');
}

$stmt = db()->prepare('
    INSERT INTO withdrawals (user_id, amount, destination_type, destination_value, bank_account_id, status)
    VALUES (:user_id, :amount, :destination_type, :destination_value, :bank_account_id, :status)
');
$stmt->execute([
    'user_id' => $user['id'],
    'amount' => $amount,
    'destination_type' => $destinationType,
    'destination_value' => $destinationValue,
    'bank_account_id' => $destinationType === 'bank' ? $bankAccountId : null,
    'status' => 'pending',
]);

log_audit((int) $user['id'], 'withdraw_request', 'Withdrawal request submitted');
detect_rapid_transactions((int) $user['id']);
header('Location: ' . APP_URL . '/trader/wallet.php');
