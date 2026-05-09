<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function create_fraud_flag_if_needed(int $userId, string $reason, string $severity): void
{
    $stmt = db()->prepare('
        SELECT id FROM fraud_flags
        WHERE user_id = :user_id AND reason = :reason AND status = :status
        LIMIT 1
    ');
    $stmt->execute([
        'user_id' => $userId,
        'reason' => $reason,
        'status' => 'open',
    ]);
    if ($stmt->fetch()) {
        return;
    }

    $ins = db()->prepare('INSERT INTO fraud_flags (user_id, reason, severity) VALUES (:user_id, :reason, :severity)');
    $ins->execute([
        'user_id' => $userId,
        'reason' => $reason,
        'severity' => $severity,
    ]);
}

function detect_rapid_transactions(int $userId): void
{
    $countStmt = db()->prepare('
        SELECT
            (
                (SELECT COUNT(*) FROM payments WHERE user_id = :user_id_a AND created_at >= (NOW() - INTERVAL 10 MINUTE))
                +
                (SELECT COUNT(*) FROM withdrawals WHERE user_id = :user_id_b AND created_at >= (NOW() - INTERVAL 10 MINUTE))
            ) AS txn_count
    ');
    $countStmt->execute([
        'user_id_a' => $userId,
        'user_id_b' => $userId,
    ]);
    $count = (int) $countStmt->fetchColumn();

    if ($count >= 5) {
        create_fraud_flag_if_needed($userId, 'Rapid transactions detected in 10-minute window', 'high');
    }
}

function detect_multiple_failed_payments(int $userId): void
{
    $stmt = db()->prepare('
        SELECT COUNT(*) FROM payments
        WHERE user_id = :user_id
          AND status = :status
          AND created_at >= (NOW() - INTERVAL 24 HOUR)
    ');
    $stmt->execute([
        'user_id' => $userId,
        'status' => 'failed',
    ]);
    $failed = (int) $stmt->fetchColumn();

    if ($failed >= 3) {
        create_fraud_flag_if_needed($userId, 'Multiple failed payments in 24-hour window', 'medium');
    }
}
