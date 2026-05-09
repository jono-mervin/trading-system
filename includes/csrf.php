<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function csrf_token(): string
{
    if (empty($_SESSION[CSRF_KEY])) {
        $_SESSION[CSRF_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_KEY];
}

function csrf_validate(?string $token): bool
{
    if (!$token || empty($_SESSION[CSRF_KEY])) {
        return false;
    }

    return hash_equals($_SESSION[CSRF_KEY], $token);
}
