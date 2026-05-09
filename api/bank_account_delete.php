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

$bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
if ($bankAccountId <= 0) {
    exit('Invalid bank account.');
}

$stmt = db()->prepare('DELETE FROM bank_accounts WHERE id = :id AND user_id = :user_id');
$stmt->execute([
    'id' => $bankAccountId,
    'user_id' => $user['id'],
]);

log_audit((int) $user['id'], 'bank_account_removed', 'Bank account removed');
header('Location: ' . APP_URL . '/trader/bank_accounts.php');
