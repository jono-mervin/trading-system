<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$rows = db()->query('
    SELECT f.id, u.name, u.email, f.reason, f.severity, f.status, f.created_at
    FROM fraud_flags f
    JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
')->fetchAll();

$title = 'Security Surveillance | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-red-500 font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-[0_0_8px_#EF4444]"></span>
            Threat Detection
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Security Surveillance</h1>
    </header>

    <div class="space-y-6">
        <?php foreach ($rows as $row): ?>
            <div class="glass-card rounded-[40px] p-8 border-l-4 <?= $row['severity'] === 'high' ? 'border-red-500' : ($row['severity'] === 'medium' ? 'border-amber-500' : 'border-accent-blue') ?>">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex-grow">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-sm font-black text-white"><?= htmlspecialchars($row['name'] ?: $row['email']) ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-black tracking-tighter uppercase <?= $row['severity'] === 'high' ? 'bg-red-500/20 text-red-500' : 'bg-amber-500/20 text-amber-500' ?>">
                                <?= htmlspecialchars($row['severity']) ?> SEVERITY
                            </span>
                        </div>
                        <p class="text-sm text-white/60 mb-4 leading-relaxed"><?= htmlspecialchars($row['reason']) ?></p>
                        <div class="flex items-center gap-4">
                            <span class="text-[10px] font-black text-white/20 tracking-widest uppercase">Status: <?= htmlspecialchars($row['status']) ?></span>
                            <span class="text-[10px] font-black text-white/20 tracking-widest uppercase"><?= date('M d, H:i', strtotime($row['created_at'])) ?></span>
                        </div>
                    </div>
                    
                    <form action="<?= APP_URL ?>/api/admin_update_fraud_flag.php" method="post" class="flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="flag_id" value="<?= (int) $row['id'] ?>">
                        
                        <button name="status" value="reviewed" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-[10px] font-black tracking-widest text-white/60 hover:text-white transition-all uppercase">
                            Reviewed
                        </button>
                        <button name="status" value="closed" class="px-4 py-2 rounded-xl bg-growth-green/10 border border-growth-green/20 text-growth-green text-[10px] font-black tracking-widest hover:bg-growth-green hover:text-deep transition-all uppercase">
                            Resolve
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (!$rows): ?>
            <div class="glass-card rounded-[40px] p-20 text-center">
                <svg class="w-16 h-16 text-white/10 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <p class="text-sm font-black text-white/20 tracking-[0.2em] uppercase">No active security threats detected</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
