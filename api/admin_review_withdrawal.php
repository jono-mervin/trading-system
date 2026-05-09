<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/config.php';

$admin = require_role('admin');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
    header('Location: ' . APP_URL . '/admin/withdrawals.php');
    exit;
}

$withdrawalId = (int) ($_POST['withdrawal_id'] ?? 0);
$status = $_POST['status'] ?? 'rejected';
if (!in_array($status, ['completed', 'rejected'], true)) {
    set_flash('error', 'Invalid withdrawal status.');
    header('Location: ' . APP_URL . '/admin/withdrawals.php');
    exit;
}

$stmt = db()->prepare('SELECT id, user_id, amount, status FROM withdrawals WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $withdrawalId]);
$row = $stmt->fetch();
if (!$row || $row['status'] !== 'pending') {
    set_flash('error', 'Withdrawal is not pending.');
    header('Location: ' . APP_URL . '/admin/withdrawals.php');
    exit;
}

$upd = db()->prepare('UPDATE withdrawals SET status = :status WHERE id = :id');
$upd->execute(['status' => $status, 'id' => $withdrawalId]);

if ($status === 'completed') {
    add_ledger_entry((int) $row['user_id'], 'withdraw', -((float) $row['amount']), $withdrawalId, 'Admin approved withdrawal');
}

log_audit((int) $admin['id'], 'withdrawal_' . $status, 'Withdrawal ID: ' . $withdrawalId);
set_flash('success', 'Withdrawal review completed.');
header('Location: ' . APP_URL . '/admin/withdrawals.php');
