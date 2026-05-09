<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$adminCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminCount > 0) {
    exit('Admin account already exists.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($name === '' || $email === '' || strlen($password) < 8) {
    exit('Invalid admin details.');
}

$stmt = db()->prepare('
    INSERT INTO users (name, email, password, role, status)
    VALUES (:name, :email, :password, :role, :status)
');
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_BCRYPT),
    'role' => 'admin',
    'status' => 'verified',
]);

$adminId = (int) db()->lastInsertId();
log_audit($adminId, 'admin_created', 'Initial admin account setup');
header('Location: ' . APP_URL . '/index.php');
