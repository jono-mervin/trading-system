<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$rows = db()->query('
    SELECT o.id, u.email, a.symbol, o.side, o.quantity, o.price, o.total, o.created_at
    FROM orders o
    JOIN users u ON u.id = o.user_id
    JOIN assets a ON a.id = o.asset_id
    ORDER BY o.created_at DESC
    LIMIT 200
')->fetchAll();

$title = 'Trading Logs';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Trading Logs</h1>
    <div class="space-y-2 text-sm">
        <?php foreach ($rows as $row): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3 flex justify-between">
                <span><?= htmlspecialchars($row['email']) ?> | <?= strtoupper(htmlspecialchars($row['side'])) ?> <?= htmlspecialchars($row['symbol']) ?> x <?= htmlspecialchars($row['quantity']) ?></span>
                <span>PHP <?= number_format((float) $row['total'], 2) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <p class="text-slate-400">No trading logs yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
