<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$withdrawalId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('
    SELECT w.*, u.name, u.email, b.bank_name, b.account_name, b.account_number
    FROM withdrawals w
    JOIN users u ON u.id = w.user_id
    LEFT JOIN bank_accounts b ON b.id = w.bank_account_id
    WHERE w.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $withdrawalId]);
$row = $stmt->fetch();
if (!$row) {
    exit('Withdrawal not found.');
}

$title = 'Withdrawal Detail';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Withdrawal Detail #<?= (int) $row['id'] ?></h1>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 text-sm space-y-1">
        <p>User: <?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['email']) ?>)</p>
        <p>Amount: PHP <?= number_format((float) $row['amount'], 2) ?></p>
        <p>Status: <?= strtoupper(htmlspecialchars($row['status'])) ?></p>
        <?php if ($row['destination_type'] === 'bank' && $row['bank_account_id']): ?>
            <p>Destination: <?= htmlspecialchars((string) $row['bank_name']) ?> - <?= htmlspecialchars((string) $row['account_name']) ?> (****<?= htmlspecialchars(substr((string) $row['account_number'], -4)) ?>)</p>
        <?php else: ?>
            <p>Destination: <?= htmlspecialchars($row['destination_type']) ?> - <?= htmlspecialchars($row['destination_value']) ?></p>
        <?php endif; ?>
        <p>Requested at: <?= htmlspecialchars($row['created_at']) ?></p>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
