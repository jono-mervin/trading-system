<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');

$stmt = db()->prepare('
    SELECT p.quantity, p.avg_price, a.symbol, a.price, a.name
    FROM portfolios p
    JOIN assets a ON a.id = p.asset_id
    WHERE p.user_id = :user_id AND p.quantity > 0
');
$stmt->execute(['user_id' => $user['id']]);
$rows = $stmt->fetchAll();

$totalMarketValue = 0;
$totalCost = 0;
foreach ($rows as $row) {
    $totalMarketValue += (float) $row['quantity'] * (float) $row['price'];
    $totalCost += (float) $row['quantity'] * (float) $row['avg_price'];
}
$totalPnL = $totalMarketValue - $totalCost;
$pnlPercent = $totalCost > 0 ? ($totalPnL / $totalCost) * 100 : 0;

$title = 'Vortex Portfolio | Holdings';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 text-growth-green font-bold text-sm tracking-[0.1em] mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-growth-green shadow-[0_0_8px_#6FCF97]"></span>
                Asset Management
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Portfolio</h1>
        </div>
        <div class="text-right">
            <p class="text-xs font-black text-white/40 tracking-[0.2em] mb-2">Net Unrealized P/L</p>
            <h2 class="text-3xl font-black <?= $totalPnL >= 0 ? 'text-growth-green' : 'text-rose-500' ?>">
                <?= $totalPnL >= 0 ? '+' : '' ?>PHP <?= number_format($totalPnL, 2) ?>
                <span class="text-sm ml-1">(<?= number_format($pnlPercent, 2) ?>%)</span>
            </h2>
        </div>
    </header>

    <!-- Total Value Card -->
    <div class="glass-card rounded-[40px] p-10 mb-12 relative overflow-hidden group">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-growth-green/10 rounded-full blur-[100px] group-hover:bg-growth-green/20 transition-all"></div>
        <div class="relative z-10">
            <p class="text-xs font-black text-white/40 tracking-[0.2em] mb-4">Total Portfolio Value</p>
            <h2 class="text-6xl font-black text-white tracking-tighter">PHP <?= number_format($totalMarketValue, 2) ?></h2>
        </div>
    </div>

    <!-- Holdings List -->
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($rows as $row): ?>
            <?php
            $marketValue = (float) $row['quantity'] * (float) $row['price'];
            $cost = (float) $row['quantity'] * (float) $row['avg_price'];
            $pnl = $marketValue - $cost;
            $percent = $cost > 0 ? ($pnl / $cost) * 100 : 0;
            ?>
            <div class="glass-card rounded-[32px] p-8 flex flex-col md:flex-row md:items-center justify-between gap-8 group hover:border-white/20 transition-all">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center text-white font-black text-xl">
                        <?= substr($row['symbol'], 0, 1) ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white"><?= htmlspecialchars($row['symbol']) ?></h3>
                        <p class="text-xs text-white/40 tracking-widest"><?= htmlspecialchars($row['name']) ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-12 text-right">
                    <div class="hidden md:block">
                        <p class="text-xs font-black text-white/40 tracking-widest mb-1">Quantity</p>
                        <p class="text-sm font-bold text-white"><?= htmlspecialchars($row['quantity']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-black text-white/40 tracking-widest mb-1">Market Value</p>
                        <p class="text-sm font-bold text-white">PHP <?= number_format($marketValue, 2) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-black text-white/40 tracking-widest mb-1">Profit / Loss</p>
                        <p class="text-sm font-black <?= $pnl >= 0 ? 'text-growth-green' : 'text-rose-500' ?>">
                            <?= $pnl >= 0 ? '+' : '' ?><?= number_format($percent, 2) ?>%
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
            <div class="glass-card rounded-[40px] p-20 text-center border-dashed">
                <div class="w-20 h-20 rounded-full bg-white/5 flex items-center justify-center mx-auto mb-6 text-white/20">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                </div>
                <h3 class="text-xl font-black text-white mb-2">No active holdings</h3>
                <p class="text-sub text-sm mb-8">Execute your first trade to populate your portfolio.</p>
                <a href="<?= APP_URL ?>/trader/trade.php" class="inline-block px-10 py-4 rounded-2xl bg-accent-blue text-white font-black text-sm tracking-widest hover:shadow-2xl hover:shadow-accent-blue/40 transition-all">Go to Terminal</a>
            </div>
        <?php endif; ?>
    </div>
</main>


<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
