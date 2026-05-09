<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
$stmt = db()->prepare('
    SELECT id, bank_name, account_name, account_number, created_at
    FROM bank_accounts
    WHERE user_id = :user_id
    ORDER BY created_at DESC
');
$stmt->execute(['user_id' => $user['id']]);
$accounts = $stmt->fetchAll();

$title = 'Bank Accounts';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Manage Bank Accounts</h1>

    <section class="bg-slate-900 border border-slate-800 rounded-xl p-6 mb-6">
        <h2 class="font-semibold mb-4">Add Bank Account</h2>
        <form class="space-y-3" action="<?= APP_URL ?>/api/bank_account_add.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input class="w-full bg-slate-800 rounded px-3 py-2" name="bank_name" placeholder="Bank Name" required>
            <input class="w-full bg-slate-800 rounded px-3 py-2" name="account_name" placeholder="Account Name" required>
            <input class="w-full bg-slate-800 rounded px-3 py-2" name="account_number" placeholder="Account Number" required>
            <button class="bg-indigo-600 px-4 py-2 rounded">Add Account</button>
        </form>
    </section>

    <section class="bg-slate-900 border border-slate-800 rounded-xl p-6">
        <h2 class="font-semibold mb-4">Saved Accounts</h2>
        <div class="space-y-2 text-sm">
            <?php foreach ($accounts as $account): ?>
                <div class="bg-slate-800 rounded p-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($account['bank_name']) ?> - <?= htmlspecialchars($account['account_name']) ?></p>
                        <p class="text-slate-400">****<?= htmlspecialchars(substr((string) $account['account_number'], -4)) ?></p>
                    </div>
                    <form action="<?= APP_URL ?>/api/bank_account_delete.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="bank_account_id" value="<?= (int) $account['id'] ?>">
                        <button class="bg-rose-600 px-3 py-1 rounded">Remove</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (!$accounts): ?>
                <p class="text-slate-400">No bank accounts saved yet.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
