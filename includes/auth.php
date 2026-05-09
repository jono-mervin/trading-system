<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION[SESSION_KEY_USER] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
    return $user;
}

function require_role(string $role): array
{
    $user = require_login();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

function login_user(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    unset($user['password']);
    $_SESSION[SESSION_KEY_USER] = $user;
    log_audit((int) $user['id'], 'login', 'User login');
    return true;
}

function logout_user(): void
{
    $uid = (int) ($_SESSION[SESSION_KEY_USER]['id'] ?? 0);
    if ($uid > 0) {
        log_audit($uid, 'logout', 'User logout');
    }
    $_SESSION = [];
    session_destroy();
}
