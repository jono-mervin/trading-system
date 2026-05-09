<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
$balance = get_wallet_balance((int) $user['id']);
$kycStatus = (string) ($user['status'] ?? 'pending');

$ordersStmt = db()->prepare('
    SELECT o.side, a.symbol, o.quantity, o.total, o.created_at
    FROM orders o
    JOIN assets a ON a.id = o.asset_id
    WHERE o.user_id = :user_id
    ORDER BY o.created_at DESC
    LIMIT 10
');
$ordersStmt->execute(['user_id' => $user['id']]);
$orders = $ordersStmt->fetchAll();

$pendingDepositsStmt = db()->prepare("SELECT COUNT(*) FROM payments WHERE user_id = :user_id AND status = 'pending'");
$pendingDepositsStmt->execute(['user_id' => $user['id']]);
$pendingDeposits = (int) $pendingDepositsStmt->fetchColumn();

$pendingKycStmt = db()->prepare("SELECT COUNT(*) FROM kyc_verifications WHERE user_id = :user_id AND status = 'pending'");
$pendingKycStmt->execute(['user_id' => $user['id']]);
$pendingKyc = (int) $pendingKycStmt->fetchColumn();

// Calculate dynamic Vortex Index (mock logic based on real asset counts/values)
$assetCount = (int) db()->query('SELECT COUNT(*) FROM assets')->fetchColumn();
$vortexIndex = 2500 + ($assetCount * 12.5) + (date('i') * 0.5);
$indexChange = 1.2 + (date('s') * 0.01);

$title = 'Dashboard | ' . APP_NAME;
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24">
    <!-- Dashboard Header -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div>
            <div class="flex items-center gap-3 text-accent-cyan font-bold text-sm tracking-[0.1em] mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-cyan shadow-[0_0_8px_#00B5D8]"></span>
                Market is Live
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Overview</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= APP_URL ?>/trader/wallet.php" class="px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Deposit
            </a>
            <a href="<?= APP_URL ?>/trader/trade.php" class="px-6 py-3 rounded-2xl bg-accent-blue text-white font-bold text-sm shadow-xl shadow-accent-blue/20 hover:shadow-accent-blue/40 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                New Trade
            </a>
        </div>
    </header>

    <!-- Main Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <!-- Wallet Card -->
        <div class="glass-card rounded-[32px] p-8 group relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent-blue/10 rounded-full blur-3xl group-hover:bg-accent-blue/20 transition-all"></div>
            <div class="relative z-10">
                <p class="text-sm font-bold text-white/50 tracking-widest mb-4">Total Wallet Balance</p>
                <div class="flex items-baseline gap-2 mb-2">
                    <span class="text-3xl font-black text-white">PHP <?= number_format($balance, 2) ?></span>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-growth-green bg-growth-green/10 px-3 py-1 rounded-full w-max">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    0.00%
                </div>
            </div>
        </div>

        <!-- Market Index Card -->
        <div class="glass-card rounded-[32px] p-8 group relative overflow-hidden hover:border-accent-cyan/30 transition-all">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent-cyan/10 rounded-full blur-3xl group-hover:bg-accent-cyan/20 transition-all"></div>
            <div class="relative z-10">
                <p class="text-sm font-bold text-white/50 tracking-widest mb-4">Vortex Index</p>
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-3xl font-black text-white"><?= number_format($vortexIndex, 2) ?></span>
                    <span class="text-xs font-black text-growth-green bg-growth-green/10 px-2 py-1 rounded-md">+<?= number_format($indexChange, 2) ?>%</span>
                </div>
                <p class="text-xs font-bold text-sub">Global Liquidity Score</p>
            </div>
        </div>

        <!-- Pending Deposits -->
        <div class="glass-card rounded-[32px] p-8 group relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent-blue/10 rounded-full blur-3xl transition-all"></div>
            <div class="relative z-10">
                <p class="text-sm font-bold text-white/50 tracking-widest mb-4">Pending Deposits</p>
                <div class="text-3xl font-black text-white mb-2"><?= $pendingDeposits ?></div>
                <p class="text-xs font-bold text-sub">Awaiting approval</p>
            </div>
        </div>

        <!-- Notifications/Alerts -->
        <div class="glass-card rounded-[32px] p-8 group relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent-cyan/10 rounded-full blur-3xl transition-all"></div>
            <div class="relative z-10">
                <p class="text-sm font-bold text-white/50 tracking-widest mb-4">Account Alerts</p>
                <div class="text-3xl font-black text-white mb-2"><?= $pendingKyc > 0 ? '1' : '0' ?></div>
                <p class="text-xs font-bold text-sub">Actions required</p>
            </div>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-12">
        <?php            $navItems = [
                ['Portfolio', 'portfolio.php', 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4', 'accent-blue'],
                ['Execution', 'trade.php', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'accent-cyan'],
                ['Wallet', 'wallet.php', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'growth-green'],
                ['History', 'transactions.php', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'accent-blue'],
                ['Settings', 'profile.php', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'accent-cyan'],
                ['Security', 'profile.php', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'growth-green'],
            ];
        foreach ($navItems as $item): 
        ?>
            <a href="<?= APP_URL ?>/trader/<?= $item[1] ?>" class="glass-card p-4 rounded-2xl flex flex-col items-center gap-3 hover:bg-white/5 transition-all group">
                <div class="w-10 h-10 rounded-xl bg-<?= $item[3] ?>/10 flex items-center justify-center text-<?= $item[3] ?> group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item[2] ?>"></path></svg>
                </div>
                <span class="text-sm font-bold text-white/70 group-hover:text-white transition-colors"><?= $item[0] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 glass-card rounded-[32px] overflow-hidden">
            <div class="p-8 border-b border-white/5 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Recent Orders</h2>
                <a href="<?= APP_URL ?>/trader/transactions.php" class="text-xs font-bold text-accent-cyan tracking-widest hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-sm font-bold text-white/30 tracking-widest bg-white/2">
                            <th class="px-8 py-4">Asset</th>
                            <th class="px-8 py-4">Side</th>
                            <th class="px-8 py-4">Quantity</th>
                            <th class="px-8 py-4 text-right">Total (PHP)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-white/2 transition-colors">
                                <td class="px-8 py-5 text-sm font-bold text-white"><?= htmlspecialchars($order['symbol']) ?></td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 rounded-full text-xs font-black tracking-widest <?= $order['side'] === 'buy' ? 'bg-growth-green/10 text-growth-green' : 'bg-rose-500/10 text-rose-500' ?>">
                                        <?= htmlspecialchars($order['side']) ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm text-sub"><?= htmlspecialchars($order['quantity']) ?></td>
                                <td class="px-8 py-5 text-sm font-black text-white text-right"><?= number_format((float) $order['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-sm text-white/30 italic">No orders found in your history.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notifications Panel -->
        <div class="glass-card rounded-[32px] overflow-hidden flex flex-col">
            <div class="p-8 border-b border-white/5">
                <h2 class="text-xl font-bold text-white">Security & Alerts</h2>
            </div>
            <div class="p-8 space-y-6 flex-grow">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-accent-blue/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white mb-1">Awaiting Review</h4>
                        <p class="text-xs text-sub leading-relaxed">You have <strong><?= $pendingDeposits ?></strong> pending deposits awaiting admin confirmation.</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-accent-cyan/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-accent-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white mb-1">KYC Verification</h4>
                        <?php if ($kycStatus === 'verified'): ?>
                            <p class="text-xs text-growth-green font-bold">Your identity has been fully verified.</p>
                        <?php else: ?>
                            <p class="text-xs text-sub leading-relaxed">Identity verification is required for full account access.</p>
                            <a href="<?= APP_URL ?>/trader/kyc.php" class="inline-block mt-2 text-[10px] font-black tracking-widest text-accent-cyan hover:underline">Complete KYC Now</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-growth-green/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-growth-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white mb-1">Service Status</h4>
                        <a href="<?= APP_URL ?>/ai_health.php" class="text-xs text-growth-green hover:underline">All AI services operational.</a>
                    </div>
                </div>
            </div>
            <div class="p-8 bg-white/2 border-t border-white/5">
                <p class="text-xs font-bold text-white/30 tracking-[0.2em] text-center">Protected by Vortex Shield v2</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
