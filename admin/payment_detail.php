<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$paymentId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('
    SELECT p.*, u.name, u.email
    FROM payments p
    JOIN users u ON u.id = p.user_id
    WHERE p.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $paymentId]);
$payment = $stmt->fetch();
if (!$payment) {
    exit('Payment not found.');
}

$logsStmt = db()->prepare('SELECT event_type, created_at, payload_json FROM payment_logs WHERE source_id = :source_id ORDER BY created_at DESC');
$logsStmt->execute(['source_id' => $payment['external_reference']]);
$logs = $logsStmt->fetchAll();

$title = 'Payment Detail';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Payment Detail #<?= (int) $payment['id'] ?></h1>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 mb-4 text-sm space-y-1">
        <p>User: <?= htmlspecialchars($payment['name']) ?> (<?= htmlspecialchars($payment['email']) ?>)</p>
        <p>Amount: PHP <?= number_format((float) $payment['amount'], 2) ?></p>
        <p>Status: <?= strtoupper(htmlspecialchars($payment['status'])) ?> | Type: <?= strtoupper(htmlspecialchars($payment['payment_type'])) ?></p>
        <p>Reference: <?= htmlspecialchars($payment['external_reference']) ?></p>
    </div>
    <h2 class="text-lg font-semibold mb-2">Event Timeline</h2>
    <div class="space-y-2 text-xs">
        <?php foreach ($logs as $log): ?>
            <details class="bg-slate-900 border border-slate-800 rounded-xl p-3">
                <summary><?= htmlspecialchars($log['event_type']) ?> (<?= htmlspecialchars($log['created_at']) ?>)</summary>
                <pre class="mt-2 whitespace-pre-wrap text-slate-300"><?= htmlspecialchars((string) $log['payload_json']) ?></pre>
            </details>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
            <p class="text-slate-400">No events logged yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
