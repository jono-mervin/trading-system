<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function get_wallet_balance(int $userId): float
{
    $stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) AS balance FROM wallet_ledger WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    return (float) $stmt->fetchColumn();
}

function add_ledger_entry(
    int $userId,
    string $type,
    float $amount,
    ?int $referenceId,
    string $notes = ''
): void {
    $pdo = db();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $newBalance = get_wallet_balance($userId) + $amount;
        if ($newBalance < 0) {
            throw new RuntimeException('Insufficient wallet balance.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO wallet_ledger (user_id, type, amount, balance_after, reference_id, notes)
            VALUES (:user_id, :type, :amount, :balance_after, :reference_id, :notes)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);

        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
