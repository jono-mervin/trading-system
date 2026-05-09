<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('trader');
$assets = db()->query('SELECT id, symbol, name, price FROM assets ORDER BY symbol')->fetchAll();

$title = 'Vortex Terminal | Trade';
require_once __DIR__ . '/../includes/ui.php';
?>

<?php
$type = $_GET['type'] ?? 'crypto';
$modules = [
    'crypto' => ['title' => 'Crypto Terminal', 'color' => 'accent-cyan'],
    'bonds' => ['title' => 'Bond Market', 'color' => 'growth-green'],
    'stocks' => ['title' => 'Stock Exchange', 'color' => 'accent-blue'],
    'trends' => ['title' => 'Trending Assets', 'color' => 'rose-500']
];
$active = $modules[$type] ?? $modules['crypto'];

// Filter assets based on type for demonstration
// In a real app, this would be a DB query: WHERE type = $type
$displayAssets = array_filter($assets, function($a) use ($type) {
    if ($type === 'crypto') return in_array($a['symbol'], ['BTC', 'ETH', 'SOL', 'AVAX']);
    if ($type === 'bonds') return str_contains($a['symbol'], '10Y') || str_contains($a['symbol'], 'BOND');
    if ($type === 'stocks') return !in_array($a['symbol'], ['BTC', 'ETH']) && !str_contains($a['symbol'], '10Y');
    return true;
});
if (empty($displayAssets)) $displayAssets = array_slice($assets, 0, 4);
?>

<main class="relative overflow-hidden min-h-[90vh]">
    <!-- Abstract Background Glows -->
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-accent-blue/10 rounded-full blur-[150px] animate-pulse"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-accent-cyan/10 rounded-full blur-[150px] animate-pulse" style="animation-delay: 2.5s"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-24 relative z-10">
        
        <!-- Module Title Section -->
        <header class="mb-10">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-<?= $active['color'] ?> shadow-[0_0_8px_currentColor] animate-pulse"></span>
                <span class="text-[9px] font-black tracking-[0.3em] text-<?= $active['color'] ?> uppercase">Live Execution</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight"><?= $active['title'] ?></h1>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- 1. Order Book (3 Columns) -->
            <div class="lg:col-span-3 glass-card rounded-[32px] p-8 border border-white/5 relative overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 w-32 h-32 bg-rose-500/5 rounded-full blur-[80px]"></div>
                <h4 class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase mb-8">Live Order Book</h4>
                <div class="flex-grow space-y-3">
                    <?php for($i=0; $i<8; $i++): ?>
                        <div class="flex justify-between items-center text-xs font-bold">
                            <span class="text-rose-500"><?= number_format(rand(58000, 58500), 2) ?></span>
                            <span class="text-white/20"><?= rand(1, 10) / 10 ?></span>
                        </div>
                    <?php endfor; ?>
                    <div class="py-6 border-y border-white/5 flex flex-col items-center">
                        <span class="text-2xl font-black text-white tracking-tighter"><?= number_format((float)reset($displayAssets)['price'] ?? 58241.20, 2) ?></span>
                        <span class="text-[9px] text-growth-green font-black tracking-[0.1em] mt-1">MARKET PRICE</span>
                    </div>
                    <?php for($i=0; $i<8; $i++): ?>
                        <div class="flex justify-between items-center text-xs font-bold">
                            <span class="text-growth-green"><?= number_format(rand(57500, 58000), 2) ?></span>
                            <span class="text-white/20"><?= rand(1, 10) / 10 ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- 2. Market Overview & Assets (6 Columns) -->
            <div class="lg:col-span-6 space-y-6 flex flex-col">
                <div class="glass-card rounded-[32px] p-8 min-h-[450px] border border-white/5 flex flex-col relative overflow-hidden group/chart flex-grow">
                    <div class="absolute inset-0 bg-gradient-to-b from-accent-blue/5 to-transparent"></div>
                    <div class="flex justify-between items-center mb-8 relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-accent-cyan/10 flex items-center justify-center text-accent-cyan shadow-lg shadow-accent-cyan/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight uppercase"><?= $active['title'] ?> Overview</h3>
                                <p class="text-[9px] text-white/20 tracking-widest uppercase">Real-time Data Stream</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-[9px] font-black text-white/40 uppercase cursor-pointer">1H</span>
                            <span class="px-4 py-2 rounded-xl bg-accent-blue text-white text-[9px] font-black uppercase shadow-xl shadow-accent-blue/20 cursor-pointer">1D</span>
                            <span class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-[9px] font-black text-white/40 uppercase cursor-pointer">1W</span>
                        </div>
                    </div>

                    <!-- Simulated Chart -->
                    <div class="flex-grow flex items-end gap-1.5 px-1 pb-8 relative z-10">
                        <?php for($i=0; $i<45; $i++): 
                            $h = rand(15, 90);
                            $c = rand(0, 1) ? 'bg-growth-green' : 'bg-rose-500';
                        ?>
                            <div class="flex-1 <?= $c ?>/10 rounded-t-sm relative group cursor-pointer hover:<?= $c ?>/40 transition-all" style="height: <?= $h ?>%">
                                <div class="absolute inset-x-0 bottom-0 <?= $c ?> h-0.5 rounded-full shadow-[0_0_10px_<?= $c==='bg-growth-green'?'#6FCF97':'#EF4444' ?>]"></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="pt-6 border-t border-white/5 flex justify-between text-[9px] font-black text-white/20 tracking-[0.3em] uppercase">
                        <span>04:00</span>
                        <span>12:00</span>
                        <span class="text-<?= $active['color'] ?> animate-pulse">Live Feed</span>
                    </div>
                </div>

                <!-- Asset Grid -->
                <div class="grid grid-cols-4 gap-4">
                    <?php foreach (array_slice($displayAssets, 0, 4) as $asset): ?>
                        <div class="glass-card p-6 rounded-[24px] border border-white/5 hover:border-accent-cyan/30 transition-all cursor-pointer text-center group">
                            <p class="text-[9px] font-black text-white/20 mb-2 tracking-widest uppercase"><?= htmlspecialchars($asset['symbol']) ?></p>
                            <h4 class="text-lg font-black text-white leading-none"><?= number_format((float) $asset['price'], ($type==='crypto'?0:2)) ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Place Order (3 Columns) -->
            <div class="lg:col-span-3 glass-card rounded-[32px] p-8 border border-accent-blue/30 relative overflow-hidden bg-accent-blue/5 flex flex-col h-full group/trade">
                <div class="absolute top-0 right-0 w-32 h-32 bg-accent-blue/10 rounded-full blur-[60px]"></div>
                <h3 class="text-lg font-black text-white mb-8 tracking-tight">Place Order</h3>

                <form class="space-y-6" action="<?= APP_URL ?>/api/place_order.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="space-y-4">
                        <label class="text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">Asset</label>
                        <select name="asset_id" required
                            class="w-full px-5 py-4 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold transition-all appearance-none text-xs">
                            <?php foreach ($displayAssets as $asset): ?>
                                <option value="<?= (int) $asset['id'] ?>">
                                    <?= htmlspecialchars($asset['symbol']) ?> - PHP <?= number_format((float) $asset['price'], ($type==='crypto'?0:2)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">Mode</label>
                        <div class="grid grid-cols-2 gap-3 p-1.5 rounded-2xl bg-black/40 border border-white/5">
                            <button type="button" onclick="setSide('buy')" id="buyBtn"
                                class="py-2.5 rounded-xl bg-growth-green text-deep text-[10px] font-black text-center uppercase tracking-widest shadow-lg shadow-growth-green/20">Buy</button>
                            <button type="button" onclick="setSide('sell')" id="sellBtn"
                                class="py-2.5 rounded-xl text-white/40 text-[10px] font-black text-center uppercase tracking-widest hover:text-white transition-colors">Sell</button>
                        </div>
                    </div>
                    <input type="hidden" name="side" id="orderSide" value="buy">

                    <div class="space-y-4">
                        <label class="text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">Quantity</label>
                        <div class="relative">
                            <input type="number" min="0.0001" step="0.0001" name="quantity" required placeholder="0.0000"
                                class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-black text-xl placeholder:text-white/10 transition-all">
                        </div>
                    </div>

                    <div class="p-6 rounded-[32px] bg-white/5 border border-white/5 space-y-4">
                        <div class="flex justify-between text-[10px] font-black">
                            <span class="text-white/20 tracking-widest uppercase">Est. Total</span>
                            <span class="text-accent-cyan" id="estTotal">PHP 0</span>
                        </div>
                        <div class="flex justify-between text-[10px] font-black">
                            <span class="text-white/20 tracking-widest uppercase">Fee (0.1%)</span>
                            <span class="text-white/60">PHP 0</span>
                        </div>
                    </div>

                    <button id="submitOrderBtn"
                        class="mt-auto w-full py-5 rounded-2xl bg-accent-blue text-white font-black text-center tracking-[0.2em] uppercase text-[10px] shadow-2xl shadow-accent-blue/20 hover:bg-white hover:text-deep transition-all duration-300">
                        Confirm Buy
                    </button>
                </form>
            </div>

        </div>
    </div>
</main>

<script>
    function setSide(side) {
        const buyBtn = document.getElementById('buyBtn');
        const sellBtn = document.getElementById('sellBtn');
        const orderSide = document.getElementById('orderSide');
        const submitBtn = document.getElementById('submitOrderBtn');

        if (side === 'buy') {
            buyBtn.classList.add('bg-growth-green', 'text-deep', 'shadow-growth-green/20');
            buyBtn.classList.remove('text-white/40');
            sellBtn.classList.remove('bg-rose-500', 'text-white', 'shadow-rose-500/20');
            sellBtn.classList.add('text-white/40');
            orderSide.value = 'buy';
            submitBtn.innerHTML = 'Confirm Buy';
        } else {
            sellBtn.classList.add('bg-rose-500', 'text-white', 'shadow-rose-500/20');
            sellBtn.classList.remove('text-white/40');
            buyBtn.classList.remove('bg-growth-green', 'text-deep', 'shadow-growth-green/20');
            buyBtn.classList.add('text-white/40');
            orderSide.value = 'sell';
            submitBtn.innerHTML = 'Confirm Sell';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>