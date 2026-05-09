<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

$adminCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$title = 'Setup Admin Account';
require_once __DIR__ . '/includes/ui.php';
?>
<main class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Setup Admin Account</h1>
    <?php if ($adminCount > 0): ?>
        <p class="text-amber-300">Admin account already exists. Login with admin credentials.</p>
        <a class="text-indigo-300 underline" href="<?= APP_URL ?>/index.php">Back to Login</a>
    <?php else: ?>
        <section class="bg-slate-900 border border-slate-800 rounded-xl p-6">
            <form class="space-y-3" action="<?= APP_URL ?>/api/setup_admin.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input class="w-full bg-slate-800 rounded px-3 py-2" name="name" placeholder="Admin Name" required>
                <input class="w-full bg-slate-800 rounded px-3 py-2" type="email" name="email" placeholder="Admin Email" required>
                <input class="w-full bg-slate-800 rounded px-3 py-2" type="password" name="password" placeholder="Password (min 8 chars)" minlength="8" required>
                <button class="bg-emerald-600 px-4 py-2 rounded">Create Admin</button>
            </form>
        </section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/ui_footer.php'; ?>
