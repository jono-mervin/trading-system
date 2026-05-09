<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
if (strlen($newPassword) < 8) {
    set_flash('error', 'New password must be at least 8 characters.');
    header('Location: ' . APP_URL . '/trader/profile.php');
    exit;
}

$stmt = db()->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $user['id']]);
$hash = (string) $stmt->fetchColumn();
if ($hash === '' || !password_verify($currentPassword, $hash)) {
    set_flash('error', 'Current password is incorrect.');
    header('Location: ' . APP_URL . '/trader/profile.php');
    exit;
}

$upd = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
$upd->execute([
    'password' => password_hash($newPassword, PASSWORD_BCRYPT),
    'id' => $user['id'],
]);

log_audit((int) $user['id'], 'password_changed', 'Trader changed password');
set_flash('success', 'Password changed successfully.');
header('Location: ' . APP_URL . '/trader/profile.php');
