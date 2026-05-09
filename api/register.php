<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/rate_limit.php';

session_start();
rate_limit_or_fail('register', 5, 60);
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (!$name || !$email || strlen($password) < 8) {
    header('Location: ' . APP_URL . '/index.php?error=reg_invalid');
    exit;
}

$stmt = db()->prepare('INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)');
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_BCRYPT),
    'role' => 'trader',
    'status' => 'pending',
]);

log_audit((int) db()->lastInsertId(), 'register', 'New trader registration');
header('Location: ' . APP_URL . '/index.php');
