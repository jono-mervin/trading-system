<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/config.php';

$admin = require_role('admin');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
    header('Location: ' . APP_URL . '/admin/fraud.php');
    exit;
}

$flagId = (int) ($_POST['flag_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
if ($flagId <= 0 || !in_array($status, ['reviewed', 'closed', 'open'], true)) {
    set_flash('error', 'Invalid fraud flag request.');
    header('Location: ' . APP_URL . '/admin/fraud.php');
    exit;
}

$stmt = db()->prepare('UPDATE fraud_flags SET status = :status WHERE id = :id');
$stmt->execute(['status' => $status, 'id' => $flagId]);
log_audit((int) $admin['id'], 'fraud_flag_' . $status, 'Flag ID: ' . $flagId);
set_flash('success', 'Fraud flag updated.');
header('Location: ' . APP_URL . '/admin/fraud.php');
