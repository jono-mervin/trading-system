<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function rate_limit_or_fail(string $key, int $maxAttempts, int $windowSeconds): void
{
    $bucketKey = 'rate_limit_' . $key;
    $now = time();
    $windowStart = $now - $windowSeconds;

    $attempts = $_SESSION[$bucketKey] ?? [];
    $attempts = array_values(array_filter($attempts, static fn (int $ts): bool => $ts >= $windowStart));

    if (count($attempts) >= $maxAttempts) {
        http_response_code(429);
        exit('Too many requests. Please wait and try again.');
    }

    $attempts[] = $now;
    $_SESSION[$bucketKey] = $attempts;
}
