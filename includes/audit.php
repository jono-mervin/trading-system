<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function log_audit(?int $userId, string $action, string $context = ''): void
{
    $stmt = db()->prepare('INSERT INTO audit_logs (user_id, action, context) VALUES (:user_id, :action, :context)');
    $stmt->execute([
        'user_id' => $userId,
        'action' => $action,
        'context' => $context,
    ]);
}
