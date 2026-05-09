<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

$rows = db()->query('
    SELECT event_id, event_type, source_id, created_at
    FROM payment_logs
    ORDER BY created_at DESC
    LIMIT 150
')->fetchAll();

$title = 'Webhook Logs';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Webhook / Payment Event Logs</h1>
    <div class="space-y-2 text-sm">
        <?php foreach ($rows as $row): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3">
                <div class="flex justify-between">
                    <span><?= htmlspecialchars($row['event_type']) ?></span>
                    <span><?= htmlspecialchars($row['created_at']) ?></span>
                </div>
                <p class="text-slate-400">Event ID: <?= htmlspecialchars($row['event_id']) ?></p>
                <p class="text-slate-400">Source: <?= htmlspecialchars($row['source_id']) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <p class="text-slate-400">No webhook logs yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
