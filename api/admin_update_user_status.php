<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';

$admin = require_role('admin');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$userId = (int) ($_POST['user_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
if ($userId <= 0 || !in_array($status, ['pending', 'verified', 'suspended'], true)) {
    header('Location: ' . APP_URL . '/admin/users.php?error=invalid');
    exit;
}

$stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id AND role <> :admin_role');
$stmt->execute([
    'status' => $status,
    'id' => $userId,
    'admin_role' => 'admin',
]);

log_audit((int) $admin['id'], 'user_status_updated', 'User ID: ' . $userId . ', status: ' . $status);
header('Location: ' . APP_URL . '/admin/users.php?kyc=updated');
