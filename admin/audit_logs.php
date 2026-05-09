<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$rows = db()->query('
    SELECT a.id, a.action, a.context, a.created_at, u.email
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT 300
')->fetchAll();

$title = 'Audit Logs';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Audit Logs</h1>
    <div class="space-y-2 text-sm">
        <?php foreach ($rows as $row): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-3">
                <div class="flex justify-between">
                    <span><?= htmlspecialchars((string) ($row['email'] ?? 'system')) ?> - <?= htmlspecialchars($row['action']) ?></span>
                    <span><?= htmlspecialchars($row['created_at']) ?></span>
                </div>
                <p class="text-slate-400"><?= htmlspecialchars((string) ($row['context'] ?? '')) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
