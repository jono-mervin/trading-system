<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$rows = db()->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 200')->fetchAll();

$title = 'Identity Management | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-accent-cyan font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-accent-cyan shadow-[0_0_8px_#00B5D8]"></span>
            User Control
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Identity Management</h1>

        <?php if (($_GET['kyc'] ?? '') === 'updated'): ?>
            <div class="mt-8 p-6 rounded-3xl bg-growth-green/10 border border-growth-green/20 flex items-center gap-5 animate-pulse max-w-2xl">
                <div class="w-12 h-12 rounded-2xl bg-growth-green/20 flex items-center justify-center text-growth-green shadow-lg shadow-growth-green/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h4 class="text-xs font-black text-growth-green uppercase tracking-widest">Protocol Synchronized</h4>
                    <p class="text-xs text-white/40 font-bold uppercase mt-1">User identity status has been updated across all nodes.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === 'invalid'): ?>
            <div class="mt-8 p-6 rounded-3xl bg-rose-500/10 border border-rose-500/20 flex items-center gap-5 animate-pulse max-w-2xl">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 flex items-center justify-center text-rose-500 shadow-lg shadow-rose-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h4 class="text-xs font-black text-rose-500 uppercase tracking-widest">Operation Failed</h4>
                    <p class="text-xs text-white/40 font-bold uppercase mt-1">Invalid User ID or Status Protocol. Request Denied.</p>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <div class="glass-card rounded-[40px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/2 border-b border-white/5">
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase">Identity</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-center">Status</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-center">Protocol Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($rows as $row): ?>
                        <tr class="hover:bg-white/2 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-xs font-black text-white/40 border border-white/5">
                                        <?= strtoupper(substr($row['name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-white tracking-wide"><?= htmlspecialchars($row['name']) ?></p>
                                        <p class="text-xs text-white/40 tracking-wider"><?= htmlspecialchars($row['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase border border-current/10 
                                    <?= $row['status'] === 'verified' ? 'bg-growth-green/10 text-growth-green' : ($row['status'] === 'suspended' ? 'bg-rose-500/10 text-rose-500' : 'bg-amber-500/10 text-amber-500') ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex justify-center gap-3">
                                    <?php if ($row['role'] !== 'admin'): ?>
                                        <form action="<?= APP_URL ?>/api/admin_update_user_status.php" method="post" class="flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                            
                                            <button name="status" value="verified" title="Verify User" class="w-8 h-8 rounded-lg bg-growth-green/10 text-growth-green flex items-center justify-center hover:bg-growth-green hover:text-deep transition-all border border-growth-green/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            
                                            <button name="status" value="pending" title="Set Pending" class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center hover:bg-amber-500 hover:text-deep transition-all border border-amber-500/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                            
                                            <button name="status" value="suspended" title="Suspend User" class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all border border-rose-500/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-[10px] font-black text-white/20 tracking-widest uppercase">Admin Protection</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
document.querySelectorAll('form[action*="admin_update_user_status.php"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const btn = e.submitter;
        if (btn) {
            btn.classList.add('opacity-50', 'animate-pulse');
            showToast('Synchronizing Protocol Change...', 'info');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
