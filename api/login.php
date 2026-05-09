<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/rate_limit.php';

rate_limit_or_fail('login', 10, 60);

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (!login_user($email, $password)) {
    header('Location: ' . APP_URL . '/index.php?error=invalid');
    exit;
}

$user = current_user();
$target = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/trader/dashboard.php';
header('Location: ' . APP_URL . $target);
