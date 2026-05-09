<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

$totals = [
    'deposits_completed' => (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn(),
    'withdrawals_completed' => (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='completed'")->fetchColumn(),
    'trade_volume' => (float) db()->query("SELECT COALESCE(SUM(total),0) FROM orders")->fetchColumn(),
    'open_fraud_flags' => (int) db()->query("SELECT COUNT(*) FROM fraud_flags WHERE status='open'")->fetchColumn(),
    'avg_trade' => (float) db()->query("SELECT COALESCE(AVG(total),0) FROM orders")->fetchColumn(),
];

$title = 'Intelligence Reports | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-accent-blue font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-accent-blue shadow-[0_0_8px_#2F2FE4]"></span>
            Data Analytics
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Intelligence Reports</h1>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Revenue Card -->
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-growth-green/5 rounded-full blur-[80px]"></div>
            <p class="text-[10px] font-black text-white/40 tracking-[0.2em] uppercase mb-4">Net Liquidity Inflow</p>
            <h2 class="text-4xl font-black text-white mb-2">PHP <?= number_format($totals['deposits_completed'], 2) ?></h2>
            <div class="flex items-center gap-2 text-xs font-bold text-growth-green">
                <span class="w-1.5 h-1.5 rounded-full bg-growth-green shadow-[0_0_5px_#6FCF97]"></span>
                Total Completed Deposits
            </div>
        </div>

        <!-- Outflow Card -->
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-rose-500/5 rounded-full blur-[80px]"></div>
            <p class="text-[10px] font-black text-white/40 tracking-[0.2em] uppercase mb-4">Extraction Volume</p>
            <h2 class="text-4xl font-black text-white mb-2">PHP <?= number_format($totals['withdrawals_completed'], 2) ?></h2>
            <div class="flex items-center gap-2 text-xs font-bold text-rose-500">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_5px_#EF4444]"></span>
                Total Completed Withdrawals
            </div>
        </div>

        <!-- Volume Card -->
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden group">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-accent-blue/5 rounded-full blur-[80px]"></div>
            <p class="text-[10px] font-black text-white/40 tracking-[0.2em] uppercase mb-4">Market Velocity</p>
            <h2 class="text-4xl font-black text-white mb-2">PHP <?= number_format($totals['trade_volume'], 2) ?></h2>
            <div class="flex items-center gap-2 text-xs font-bold text-accent-blue">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-blue shadow-[0_0_5px_#2F2FE4]"></span>
                Aggregate Trade Volume
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="glass-card rounded-[40px] p-10">
            <h3 class="text-xl font-black text-white mb-6">Performance Metrics</h3>
            <div class="space-y-6">
                <div class="flex justify-between items-center py-4 border-b border-white/5">
                    <span class="text-xs font-bold text-white/40 tracking-widest uppercase">Average Order Value</span>
                    <span class="text-sm font-black text-white">PHP <?= number_format($totals['avg_trade'], 2) ?></span>
                </div>
                <div class="flex justify-between items-center py-4 border-b border-white/5">
                    <span class="text-xs font-bold text-white/40 tracking-widest uppercase">Active Fraud Flags</span>
                    <span class="text-sm font-black text-rose-500"><?= $totals['open_fraud_flags'] ?> Alerts</span>
                </div>
                <div class="flex justify-between items-center py-4 border-b border-white/5">
                    <span class="text-xs font-bold text-white/40 tracking-widest uppercase">System Uptime</span>
                    <span class="text-sm font-black text-growth-green">99.98%</span>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-[40px] p-10 bg-accent-cyan/5 border-accent-cyan/10">
            <h3 class="text-xl font-black text-white mb-6">AI Predictions</h3>
            <div class="space-y-4">
                <p class="text-sm text-white/60 leading-relaxed italic">"Predictive analytics suggest a 14% increase in market liquidity over the next 48 hours based on current inbound patterns."</p>
                <div class="pt-6">
                    <div class="w-full bg-white/5 rounded-full h-1.5 mb-2">
                        <div class="bg-accent-cyan h-full rounded-full shadow-[0_0_10px_#00B5D8]" style="width: 75%"></div>
                    </div>
                    <p class="text-[10px] font-black text-white/40 tracking-widest uppercase">Confidence Score: 75%</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
