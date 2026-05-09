<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$user = current_user();
if ($user) {
    $target = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/trader/dashboard.php';
    header('Location: ' . APP_URL . $target);
    exit;
}

$title = 'Vortex | Next-Gen Trading Platform';
require_once __DIR__ . '/includes/ui.php';
?>

<?php
$type = $_GET['type'] ?? 'crypto';
$modules = [
    'crypto' => ['title' => 'Crypto Terminal', 'assets' => [['BTC', '58,241'], ['ETH', '3,124'], ['SOL', '142'], ['AVAX', '38']], 'price' => '58,241.20'],
    'bonds' => ['title' => 'Bond Market', 'assets' => [['US10Y', '4.21%'], ['UK10Y', '3.85%'], ['DE10Y', '2.34%'], ['JP10Y', '0.72%']], 'price' => '4.21'],
    'stocks' => ['title' => 'Stock Exchange', 'assets' => [['AAPL', '189.42'], ['TSLA', '175.20'], ['NVDA', '892.40'], ['MSFT', '412.10']], 'price' => '412.10'],
    'trends' => ['title' => 'Trending Assets', 'assets' => [['PEPE', '0.000008'], ['WIF', '3.42'], ['ORDI', '62.40'], ['BONK', '0.00002']], 'price' => '3.42']
];
$active = $modules[$type] ?? $modules['crypto'];
?>

<main class="relative overflow-hidden min-h-[90vh]">
    <!-- Abstract Background Glows -->
    <div class="absolute top-1/4 -left-20 w-96 h-96 bg-accent-blue/10 rounded-full blur-[150px] animate-pulse"></div>
    <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-accent-cyan/10 rounded-full blur-[150px] animate-pulse"
        style="animation-delay: 2.5s"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-24 relative z-10 w-full">

        <!-- Module Title Section -->
        <header class="mb-10">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-cyan shadow-[0_0_8px_#00B5D8] animate-pulse"></span>
                <span class="text-[9px] font-black tracking-[0.3em] text-accent-cyan uppercase">Live Execution</span>
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight"><?= $active['title'] ?></h1>
        </header>

        <!-- Simulated Terminal Interface -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- 1. Order Book (3 Columns) -->
            <div onclick="toggleAuthModal('register')"
                class="lg:col-span-3 glass-card rounded-[32px] p-8 border border-white/5 relative overflow-hidden transition-all duration-500 hover:scale-[1.01] hover:border-white/20 hover:shadow-2xl cursor-pointer flex flex-col h-full group/orderbook">
                <div class="absolute top-0 right-0 w-32 h-32 bg-rose-500/5 rounded-full blur-[80px]"></div>
                <h4 class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase mb-8">Live Order Book</h4>
                <div class="flex-grow space-y-3">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="flex justify-between items-center text-xs font-bold">
                            <span class="text-rose-500"><?= number_format(rand(58000, 58500), 2) ?></span>
                            <span class="text-white/20"><?= rand(1, 10) / 10 ?></span>
                        </div>
                    <?php endfor; ?>
                    <div class="py-6 border-y border-white/5 flex flex-col items-center">
                        <span class="text-2xl font-black text-white tracking-tighter"><?= $active['price'] ?></span>
                        <span class="text-[9px] text-growth-green font-black tracking-[0.1em] mt-1">MARKET PRICE</span>
                    </div>
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="flex justify-between items-center text-xs font-bold">
                            <span class="text-growth-green"><?= number_format(rand(57500, 58000), 2) ?></span>
                            <span class="text-white/20"><?= rand(1, 10) / 10 ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- 2. Market Overview & Assets (6 Columns) -->
            <div class="lg:col-span-6 space-y-6 flex flex-col">
                <div onclick="toggleAuthModal('register')"
                    class="glass-card rounded-[32px] p-8 min-h-[450px] border border-white/5 flex flex-col relative overflow-hidden transition-all duration-500 hover:scale-[1.01] hover:border-white/20 hover:shadow-2xl cursor-pointer group/chart flex-grow">
                    <div class="absolute inset-0 bg-gradient-to-b from-accent-blue/5 to-transparent"></div>
                    <div class="flex justify-between items-center mb-8 relative z-10">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-accent-cyan/10 flex items-center justify-center text-accent-cyan shadow-lg shadow-accent-cyan/10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight uppercase">
                                    <?= $active['title'] ?> Overview
                                </h3>
                                <p class="text-[9px] text-white/20 tracking-widest uppercase">Real-time Data Stream</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <span
                                class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-[9px] font-black text-white/40 uppercase">Depth</span>
                            <span
                                class="px-4 py-2 rounded-xl bg-accent-blue text-white text-[9px] font-black uppercase">Candles</span>
                        </div>
                    </div>

                    <div class="flex-grow flex items-end gap-1.5 px-1 pb-8 relative z-10">
                        <?php for ($i = 0; $i < 45; $i++):
                            $h = rand(15, 90);
                            $color = rand(0, 1) ? 'bg-growth-green' : 'bg-rose-500';
                            ?>
                            <div class="flex-1 <?= $color ?>/10 rounded-t-sm relative" style="height: <?= $h ?>%">
                                <div class="absolute inset-x-0 bottom-0 <?= $color ?> h-1 rounded-full"></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div
                        class="pt-6 border-t border-white/5 flex justify-between text-[9px] font-black text-white/20 tracking-[0.2em] uppercase">
                        <span>04:00</span>
                        <span>12:00</span>
                        <span class="text-accent-cyan animate-pulse">Live Feed</span>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-4">
                    <?php foreach ($active['assets'] as $pa): ?>
                        <div onclick="toggleAuthModal('register')"
                            class="glass-card p-5 rounded-[24px] border border-white/5 transition-all duration-300 hover:border-white/20 cursor-pointer text-center group/asset">
                            <p class="text-[9px] font-black text-white/20 mb-2 tracking-widest uppercase"><?= $pa[0] ?></p>
                            <h4 class="text-lg font-black text-white leading-none"><?= $pa[1] ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Trade Action (3 Columns) -->
            <div onclick="toggleAuthModal('register')"
                class="lg:col-span-3 glass-card rounded-[32px] p-8 border border-accent-blue/30 relative overflow-hidden transition-all duration-500 hover:border-accent-blue hover:shadow-2xl cursor-pointer bg-accent-blue/5 flex flex-col h-full group/trade">
                <div class="absolute top-0 right-0 w-32 h-32 bg-accent-blue/10 rounded-full blur-[60px]"></div>
                <h3 class="text-lg font-black text-white mb-8 tracking-tight">Quick Trade</h3>

                <div class="space-y-6 relative z-10 flex-grow">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">Asset</label>
                        <div
                            class="w-full px-5 py-4 rounded-2xl bg-black/40 border border-white/5 text-white font-bold text-xs flex justify-between items-center">
                            <span><?= $active['assets'][0][0] ?> - <?= $active['assets'][0][1] ?></span>
                            <svg class="w-4 h-4 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">Mode</label>
                        <div class="grid grid-cols-2 gap-3 p-1.5 rounded-2xl bg-black/40 border border-white/5">
                            <div
                                class="py-2.5 rounded-xl bg-growth-green text-deep text-[10px] font-black text-center uppercase tracking-widest">
                                Buy</div>
                            <div
                                class="py-2.5 rounded-xl text-white/40 text-[10px] font-black text-center uppercase tracking-widest">
                                Sell</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[10px] font-black tracking-[0.2em] text-white/30 uppercase">Quantity</label>
                        <div
                            class="px-6 py-5 rounded-2xl bg-black/40 border border-white/5 text-white/10 font-black text-xl">
                            0.0000</div>
                    </div>

                    <div class="p-6 rounded-[32px] bg-white/5 border border-white/5 space-y-4">
                        <div class="flex justify-between text-[10px] font-black">
                            <span class="text-white/20 tracking-widest uppercase">Est. Total</span>
                            <span class="text-accent-cyan">PHP 0</span>
                        </div>
                        <div class="flex justify-between text-[10px] font-black">
                            <span class="text-white/20 tracking-widest uppercase">Fee (0.1%)</span>
                            <span class="text-white/60">PHP 0</span>
                        </div>
                    </div>

                    <div
                        class="mt-auto w-full py-5 rounded-2xl bg-accent-blue text-white font-black text-center tracking-[0.2em] uppercase text-[10px] shadow-2xl shadow-accent-blue/20">
                        Buy
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- Unified Auth Modal -->
<div id="authModal" class="modal-hidden">
    <div onclick="toggleAuthModal()" class="modal-overlay cursor-pointer"></div>
    <div class="modal-content">
        <div
            class="glass-card rounded-[40px] overflow-hidden shadow-[0_0_100px_rgba(0,0,0,0.9)] border border-white/10">
            <!-- Modal Tabs -->
            <div class="flex border-b border-white/5 p-2.5 gap-2.5 bg-white/5">
                <button onclick="switchAuthTab('login')" id="loginTab"
                    class="flex-1 py-4 rounded-2xl text-base font-black transition-all bg-accent-blue text-white shadow-2xl shadow-accent-blue/30">Log
                    In</button>
                <button onclick="switchAuthTab('register')" id="registerTab"
                    class="flex-1 py-4 rounded-2xl text-base font-black text-sub hover:text-white transition-all tracking-widest">Register</button>
            </div>

            <div class="p-8">
                <!-- Login Form -->
                <div id="loginForm" class="space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-black text-white mb-2">Vortex Access</h2>
                        <p class="text-sm text-sub">Secure gateway for authorized traders.</p>
                    </div>

                    <?php if (($_GET['error'] ?? '') === 'invalid'): ?>
                        <div
                            class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center gap-4 animate-pulse">
                            <div
                                class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-500 shadow-lg shadow-rose-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-[15px] font-black text-rose-500 tracking-widest">Authentication
                                    Failed</h4>
                                <p class="text-[12px] text-white/40 font-bold mt-0.5">Invalid Email or
                                    Password</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form class="space-y-5" action="<?= APP_URL ?>/api/login.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-[0.1em] text-white/60 ml-1">Email</label>
                            <input type="email" name="email" required placeholder="trader@vortex.com"
                                class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-blue focus:ring-1 focus:ring-accent-blue outline-none text-white transition-all font-medium placeholder:text-white/30">
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-[0.1em] text-white/60 ml-1">Password</label>
                            <input type="password" name="password" required placeholder="••••••••"
                                class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-blue focus:ring-1 focus:ring-accent-blue outline-none text-white transition-all font-medium placeholder:text-white/30">
                        </div>
                        <button type="submit"
                            class="w-full py-6 rounded-2xl bg-accent-blue text-white font-black text-lg shadow-2xl shadow-accent-blue/30 hover:shadow-accent-blue/50 transition-all transform hover:-translate-y-1.5 mt-6">
                            Login
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="registerForm" class="space-y-6 hidden">
                    <div class="text-center">
                        <h2 class="text-2xl font-black text-white mb-2">Join the Fleet</h2>
                        <p class="text-sm text-sub">Initialize your digital asset portfolio.</p>
                    </div>

                    <?php if (($_GET['error'] ?? '') === 'reg_invalid'): ?>
                        <div
                            class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center gap-4 animate-pulse">
                            <div
                                class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-500 shadow-lg shadow-rose-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-black text-rose-500 uppercase tracking-widest">Registration
                                    Failed</h4>
                                <p class="text-[10px] text-white/40 font-bold uppercase mt-0.5">Please ensure all fields are
                                    valid. Password must be 8+ characters.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form class="space-y-5" action="<?= APP_URL ?>/api/register.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-[0.1em] text-white/60 ml-1">Full Name</label>
                            <input type="text" name="name" required placeholder="Enter name..."
                                class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-cyan focus:ring-1 focus:ring-accent-cyan outline-none text-white transition-all font-medium placeholder:text-white/30">
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-[0.1em] text-white/60 ml-1">Email</label>
                            <input type="email" name="email" required placeholder="name@domain.com"
                                class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-cyan focus:ring-1 focus:ring-accent-cyan outline-none text-white transition-all font-medium placeholder:text-white/30">
                        </div>
                        <div class="space-y-3">
                            <label class="text-xs font-black tracking-[0.1em] text-white/60 ml-1">Create
                                Password</label>
                            <input type="password" name="password" minlength="8" required placeholder="Minimum 8 chars"
                                class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/10 focus:border-accent-cyan focus:ring-1 focus:ring-accent-cyan outline-none text-white transition-all font-medium placeholder:text-white/30">
                        </div>
                        <button type="submit"
                            class="w-full py-6 rounded-2xl bg-accent-cyan text-deep font-black text-lg shadow-2xl shadow-accent-cyan/30 hover:shadow-accent-cyan/50 transition-all transform hover:-translate-y-1.5 mt-6">
                            Register
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAuthModal(initialTab = 'login') {
        const modal = document.getElementById('authModal');
        modal.classList.toggle('modal-hidden');
        document.body.style.overflow = modal.classList.contains('modal-hidden') ? '' : 'hidden';
        if (!modal.classList.contains('modal-hidden')) {
            switchAuthTab(initialTab);
        }
    }

    function switchAuthTab(tab) {
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (tab === 'login') {
            loginTab.className = "flex-1 py-4 rounded-2xl text-base font-black transition-all bg-accent-blue text-white shadow-2xl shadow-accent-blue/30";
            registerTab.className = "flex-1 py-4 rounded-2xl text-base font-black text-sub hover:text-white transition-all tracking-widest";
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        } else {
            registerTab.className = "flex-1 py-4 rounded-2xl text-base font-black transition-all bg-accent-cyan text-deep shadow-2xl shadow-accent-cyan/30";
            loginTab.className = "flex-1 py-4 rounded-2xl text-base font-black text-sub hover:text-white transition-all tracking-widest";
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        }
    }

    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error === 'invalid') {
            toggleAuthModal('login');
        } else if (error === 'reg_invalid') {
            toggleAuthModal('register');
        }
    });
</script>

<?php require_once __DIR__ . '/includes/ui_footer.php'; ?>