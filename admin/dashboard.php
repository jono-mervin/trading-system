<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

$stats = [
    'users' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'total_deposits' => (float) db()->query("SELECT SUM(amount) FROM payments WHERE status='completed'")->fetchColumn(),
    'total_withdrawals' => (float) db()->query("SELECT SUM(amount) FROM withdrawals WHERE status='completed'")->fetchColumn(),
    'pending_actions' => (int) db()->query("
        SELECT (SELECT COUNT(*) FROM kyc_verifications WHERE status='pending') + 
               (SELECT COUNT(*) FROM withdrawals WHERE status='pending') + 
               (SELECT COUNT(*) FROM payments WHERE status='pending')
    ")->fetchColumn(),
    'active_flags' => (int) db()->query("SELECT COUNT(*) FROM fraud_flags WHERE status='open'")->fetchColumn(),
];

$cashflow = $stats['total_deposits'] + $stats['total_withdrawals'];
$netProfit = $stats['total_deposits'] - $stats['total_withdrawals'];

$alerts = db()->query("
    SELECT 'kyc' AS type, COUNT(*) AS cnt FROM kyc_verifications WHERE status='pending'
    UNION ALL
    SELECT 'withdrawals' AS type, COUNT(*) AS cnt FROM withdrawals WHERE status='pending'
    UNION ALL
    SELECT 'payments' AS type, COUNT(*) AS cnt FROM payments WHERE status='pending'
")->fetchAll();

$title = 'System Command | ' . APP_NAME;
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24">
    <!-- Admin Header -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div>
            <div class="flex items-center gap-3 text-red-500 font-black text-[10px] tracking-[0.3em] uppercase mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_12px_#EF4444] animate-pulse"></span>
                Strategic Command Center
            </div>
            <h1 class="text-5xl font-black text-white tracking-tighter mb-2">System Analytics</h1>
            <p class="text-white/40 text-sm font-medium">Real-time oversight of global liquidity and user growth.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= APP_URL ?>/admin/ai_health.php" class="px-6 py-4 rounded-2xl bg-accent-blue/10 border border-accent-blue/20 text-accent-blue font-black text-xs tracking-widest hover:bg-accent-blue hover:text-white transition-all flex items-center gap-3 uppercase">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Intelligence Health
            </a>
        </div>
    </header>

    <!-- Admin Hero Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="glass-card rounded-[40px] p-8 border border-white/5 relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent-blue/5 rounded-full blur-3xl group-hover:bg-accent-blue/10 transition-all"></div>
            <p class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase mb-4">Total Membership</p>
            <div class="flex items-end gap-3">
                <span class="text-4xl font-black text-white"><?= number_format($stats['users']) ?></span>
                <span class="text-xs font-bold text-growth-green mb-2">Users</span>
            </div>
        </div>

        <div class="glass-card rounded-[40px] p-8 border border-white/5 relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-growth-green/5 rounded-full blur-3xl group-hover:bg-growth-green/10 transition-all"></div>
            <p class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase mb-4">Global Cashflow</p>
            <div class="flex items-end gap-2">
                <span class="text-xs font-bold text-white/40 mb-2">$</span>
                <span class="text-4xl font-black text-white"><?= number_format($cashflow / 1000, 1) ?>k</span>
            </div>
        </div>

        <div class="glass-card rounded-[40px] p-8 border border-white/5 relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent-cyan/5 rounded-full blur-3xl group-hover:bg-accent-cyan/10 transition-all"></div>
            <p class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase mb-4">Net Performance (P/L)</p>
            <div class="flex items-end gap-2">
                <span class="text-xs font-bold text-white/40 mb-2">$</span>
                <span class="text-4xl font-black text-growth-green"><?= number_format($netProfit / 1000, 1) ?>k</span>
            </div>
        </div>

        <div class="glass-card rounded-[40px] p-8 border border-white/5 relative overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-rose-500/5 rounded-full blur-3xl group-hover:bg-rose-500/10 transition-all"></div>
            <p class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase mb-4">Pending Operations</p>
            <div class="flex items-end gap-3">
                <span class="text-4xl font-black text-white"><?= $stats['pending_actions'] ?></span>
                <span class="text-xs font-bold text-rose-500 mb-2">Alerts</span>
            </div>
        </div>
    </div>

    <!-- Management Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Actions -->
        <div class="lg:col-span-2 space-y-8">
            <div class="glass-card rounded-[32px] p-8">
                <h2 class="text-xl font-bold text-white mb-8 flex items-center gap-3">
                    <svg class="w-6 h-6 text-accent-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    System Management
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php 
                    $adminNav = [
                        ['User Directory', 'users.php', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'Manage accounts and view trader profiles.', 'accent-blue'],
                        ['Identity (KYC)', 'kyc.php', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'Verify legal documents and identity status.', 'accent-cyan'],
                        ['Payout Ledger', 'withdrawals.php', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Process withdrawal requests and fund exits.', 'rose-500'],
                        ['Fraud Sentinel', 'fraud.php', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'Monitor suspicious trades and flag activity.', 'red-500'],
                        ['Payment Flow', 'payments.php', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'Audit incoming deposits and gateway health.', 'growth-green'],
                        ['Webhooks/API', 'webhook_logs.php', 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'Technical logs for external service hooks.', 'white'],
                        ['Audit Trail', 'audit_logs.php', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01m-.01 4h.01', 'Complete history of administrative actions.', 'white'],
                        ['Financial Reports', 'reports.php', 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'Download CSV and PDF performance audits.', 'white'],
                    ];
                    foreach ($adminNav as $item):
                    ?>
                        <a href="<?= APP_URL ?>/admin/<?= $item[1] ?>" class="flex items-center gap-6 p-6 rounded-[32px] bg-white/5 border border-white/5 hover:bg-white/10 transition-all group">
                            <div class="w-14 h-14 rounded-2xl bg-<?= $item[4] ?>/10 flex items-center justify-center text-<?= $item[4] ?> group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item[2] ?>"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-white mb-1 uppercase tracking-wider"><?= $item[0] ?></h4>
                                <p class="text-xs text-white/30 font-medium"><?= $item[3] ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Action Center -->
        <div class="glass-card rounded-[32px] overflow-hidden flex flex-col">
            <div class="p-8 border-b border-white/5 bg-white/2">
                <h2 class="text-xl font-bold text-white">Action Center</h2>
            </div>
            <div class="p-8 space-y-8 flex-grow">
                <?php foreach ($alerts as $alert): ?>
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center shrink-0">
                            <?php if ($alert['type'] === 'kyc'): ?>
                                <svg class="w-6 h-6 text-accent-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <?php elseif ($alert['type'] === 'withdrawals'): ?>
                                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <?php else: ?>
                                <svg class="w-6 h-6 text-growth-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow">
                            <h4 class="text-sm font-bold text-white mb-1 tracking-widest"><?= htmlspecialchars($alert['type']) ?></h4>
                            <p class="text-xs text-sub mb-3">You have <strong><?= (int) $alert['cnt'] ?></strong> pending items.</p>
                            <a href="<?= APP_URL ?>/admin/<?= $alert['type'] === 'payments' ? 'payments.php' : ($alert['type'] === 'kyc' ? 'kyc.php' : 'withdrawals.php') ?>" class="inline-block text-[10px] font-black tracking-widest text-white px-4 py-2 rounded-lg bg-white/5 border border-white/10 hover:bg-white/10 transition-all">Review All</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="p-8 border-t border-white/5 bg-white/2 text-center">
                <span class="text-[10px] font-bold text-white/30 tracking-[0.2em]">Secure Admin Gateway</span>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
