<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/flash.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$sessionUser = $_SESSION[SESSION_KEY_USER] ?? null;
$flash = pop_flash();

$headerCash = 0;
$headerPortfolio = 0;

if ($sessionUser) {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/ledger.php';
    $headerCash = get_wallet_balance((int) $sessionUser['id']);
    
    // Fetch portfolio value
    $pStmt = db()->prepare('SELECT SUM(p.quantity * a.price) as total FROM portfolios p JOIN assets a ON a.id = p.asset_id WHERE p.user_id = :uid');
    $pStmt->execute(['uid' => $sessionUser['id']]);
    $headerPortfolio = (float) ($pStmt->fetch()['total'] ?? 0);
}
?>
<!doctype html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>
    <!-- Satoshi Font -->
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,300,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        deep: '#080616',
                        card: '#141225',
                        main: '#FFFFFF',
                        sub: '#E5E7EB',
                        'accent-blue': '#2F2FE4',
                        'accent-cyan': '#00B5D8',
                        'growth-green': '#6FCF97',
                    },
                    fontFamily: {
                        sans: ['Satoshi', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #080616;
            color: #FFFFFF;
            overflow-x: hidden;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #080616; }
        ::-webkit-scrollbar-thumb { background: #141225; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #2F2FE4; }

        /* Smoother Global Transitions */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glow-border { 
            position: relative; 
            z-index: 1; 
        }
        .glow-border::before {
            content: ''; position: absolute; inset: -1px;
            background: linear-gradient(45deg, #2F2FE4, #00B5D8, #2F2FE4);
            z-index: -1; border-radius: inherit; opacity: 0.3; transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glow-border:hover::before { opacity: 1; }
        
        .modal-overlay { 
            backdrop-filter: blur(16px); 
            background: rgba(4, 3, 10, 0.9);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: absolute;
            inset: 0;
            z-index: -1;
        }
        [id*="Modal"] {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal-content {
            position: relative;
            z-index: 10001;
            width: 100%;
            max-width: 500px;
            animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-hidden { opacity: 0; pointer-events: none; visibility: hidden; }
        .modal-hidden .modal-content { 
            transform: translateY(20px) scale(0.95); 
        }

        .glass-card {
            background: #141225; /* Solid background for visibility */
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes grid-glide {
            from { background-position: 0 0; }
            to { background-position: 0 100px; }
        }

        @keyframes particle-float {
            0%, 100% { transform: translate3d(0, 0, 0); opacity: 0; }
            50% { transform: translate3d(100px, -100px, 0); opacity: 0.3; }
        }

        .bg-grid-3d {
            position: absolute;
            inset: -100% -50%;
            background-image: 
                linear-gradient(to right, rgba(47, 47, 228, 0.25) 1.5px, transparent 1.5px),
                linear-gradient(to bottom, rgba(0, 181, 216, 0.25) 1.5px, transparent 1.5px);
            background-size: 100px 100px;
            transform: perspective(1000px) rotateX(55deg) translate3d(0, 0, 0);
            pointer-events: none;
            z-index: -1;
            animation: grid-glide 3s linear infinite;
            will-change: background-position;
            filter: blur(0.5px);
        }

        .scanlines {
            position: fixed;
            inset: 0;
            background: linear-gradient(
                to bottom,
                rgba(255, 255, 255, 0.015) 50%,
                transparent 50%
            );
            background-size: 100% 3px;
            pointer-events: none;
            z-index: 10002;
            opacity: 0.4;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: #00B5D8;
            border-radius: 50%;
            filter: blur(2px);
            box-shadow: 0 0 15px #00B5D8;
            animation: particle-float 12s infinite linear;
            will-change: transform;
        }

        .profile-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .profile-group:hover .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }


        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
    <style>
        .page-fade {
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans antialiased selection:bg-accent-blue selection:text-white flex flex-col min-h-screen page-fade relative">
    <!-- Next-Gen 3D Background Atmosphere -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden bg-deep">
        <div class="bg-grid-3d" style="mask-image: radial-gradient(circle at 50% 40%, black, transparent 70%);"></div>
        <div class="absolute inset-x-0 top-0 h-96 bg-gradient-to-b from-deep via-deep/80 to-transparent"></div>
        <div class="scanlines"></div>
        
        <!-- Floating Data Particles -->
        <div class="particle" style="top: 20%; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="top: 60%; left: 80%; animation-delay: -5s;"></div>
        <div class="particle" style="top: 40%; left: 40%; animation-delay: -10s;"></div>
        <div class="particle" style="top: 80%; left: 30%; animation-delay: -15s;"></div>
    </div>
    
    <div id="toast-container" class="fixed bottom-10 right-10 z-[10001] space-y-4 pointer-events-none"></div>

    <script>
    function toggleModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.toggle('modal-hidden');
        document.body.style.overflow = modal.classList.contains('modal-hidden') ? '' : 'hidden';
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        const colors = {
            success: 'bg-growth-green/10 border-growth-green/50 text-growth-green shadow-growth-green/20',
            error: 'bg-rose-500/10 border-rose-500/50 text-rose-500 shadow-rose-500/20',
            info: 'bg-accent-blue/10 border-accent-blue/50 text-accent-blue shadow-accent-blue/20'
        };

        const icons = {
            success: 'M5 13l4 4L19 7',
            error: 'M6 18L18 6M6 6l12 12',
            info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };

        toast.className = `flex items-center gap-4 px-6 py-4 rounded-2xl border backdrop-blur-xl ${colors[type]} shadow-2xl transition-all duration-500 translate-x-full pointer-events-auto cursor-pointer`;
        toast.innerHTML = `
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icons[type]}"></path></svg>
            <span class="text-sm font-black tracking-widest uppercase">${message}</span>
        `;

        toast.onclick = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 500);
        };

        container.appendChild(toast);
        
        // Animate in
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });

        // Auto remove
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 500);
            }
        }, 5000);
    }
    
    // Check for flash messages
    window.addEventListener('load', () => {
        <?php if ($flash): ?>
            showToast(<?= json_encode((string)($flash['message'] ?? '')) ?>, <?= json_encode((string)($flash['type'] ?? 'info')) ?>);
        <?php endif; ?>
    });
    </script>
    
    <nav class="fixed w-full z-[9999] top-0 start-0 border-b border-white/5 bg-deep/95 backdrop-blur-2xl shadow-[0_8px_40px_rgba(0,0,0,0.8)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="<?= APP_URL ?>/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-accent-blue to-accent-cyan flex items-center justify-center shadow-[0_0_20px_rgba(47,47,228,0.4)] group-hover:shadow-[0_0_30px_rgba(47,47,228,0.6)] transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-2xl font-black tracking-tighter bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-400"><?= htmlspecialchars(APP_NAME) ?></span>
                </a>

                <div class="hidden md:flex items-center gap-10 text-base font-black tracking-[0.05em] text-white/70">
                    <?php if (!$sessionUser || ($sessionUser['role'] ?? 'trader') === 'trader'): ?>
                        <a href="<?= $sessionUser ? APP_URL . '/trader/trade.php?type=crypto' : APP_URL . '/index.php?type=crypto' ?>" class="hover:text-white transition-colors">Crypto</a>
                        <a href="<?= $sessionUser ? APP_URL . '/trader/trade.php?type=bonds' : APP_URL . '/index.php?type=bonds' ?>" class="hover:text-white transition-colors">Bonds</a>
                        <a href="<?= $sessionUser ? APP_URL . '/trader/trade.php?type=stocks' : APP_URL . '/index.php?type=stocks' ?>" class="hover:text-white transition-colors">Stocks</a>
                        <a href="<?= $sessionUser ? APP_URL . '/trader/trade.php?type=trends' : APP_URL . '/index.php?type=trends' ?>" class="hover:text-white transition-colors">Trends</a>
                    <?php elseif ($sessionUser && ($sessionUser['role'] ?? 'trader') === 'admin'): ?>
                        <a href="<?= APP_URL ?>/admin/dashboard.php" class="hover:text-white transition-colors">Dash</a>
                        <a href="<?= APP_URL ?>/admin/users.php" class="hover:text-white transition-colors">Users</a>
                        <a href="<?= APP_URL ?>/admin/kyc.php" class="hover:text-white transition-colors">KYC</a>
                        <a href="<?= APP_URL ?>/admin/withdrawals.php" class="hover:text-white transition-colors">Payouts</a>
                        <a href="<?= APP_URL ?>/admin/fraud.php" class="hover:text-white transition-colors">Fraud</a>
                        <a href="<?= APP_URL ?>/admin/payments.php" class="hover:text-white transition-colors">Payments</a>
                        <a href="<?= APP_URL ?>/admin/reports.php" class="hover:text-white transition-colors">Reports</a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-6">
                    <?php if ($sessionUser): ?>
                        <!-- Portfolio & Cash (Vertical Stacked Clickable) -->
                        <div class="hidden lg:flex items-center gap-10 mr-6">
                            <a href="<?= APP_URL ?>/trader/portfolio.php" class="group/nav">
                                <p class="text-[9px] font-black text-white/30 tracking-[0.2em] uppercase mb-1 group-hover/nav:text-white/50 transition-colors text-center">Portfolio</p>
                                <p class="text-sm font-black text-growth-green transition-all text-center">PHP <?= number_format($headerPortfolio, 2) ?></p>
                            </a>
                            <a href="<?= APP_URL ?>/trader/wallet.php" class="group/nav">
                                <p class="text-[9px] font-black text-white/30 tracking-[0.2em] uppercase mb-1 group-hover/nav:text-white/50 transition-colors text-center">Cash</p>
                                <p class="text-sm font-black text-growth-green transition-all text-center">PHP <?= number_format($headerCash, 2) ?></p>
                            </a>
                        </div>

                        <!-- Profile Dropdown Container -->
                        <div class="relative profile-group py-4">
                            <button class="flex items-center gap-3 pl-4 border-l border-white/10 group">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-cyan-500 p-[2px]">
                                    <div class="w-full h-full rounded-full bg-deep flex items-center justify-center text-xs text-white font-black overflow-hidden relative">
                                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
                                        <?= strtoupper(substr($sessionUser['name'] ?? $sessionUser['email'] ?? 'U', 0, 1)) ?>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-white/20 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <!-- The Dropdown -->
                            <div class="absolute right-0 top-full pt-2 w-72 profile-dropdown">
                                <div class="glass-card rounded-[32px] p-8 border border-white/10 shadow-2xl overflow-hidden relative">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-accent-blue/10 rounded-full blur-3xl"></div>
                                    
                                    <header class="mb-8 relative z-10 border-b border-white/5 pb-6">
                                        <h3 class="text-lg font-black text-white leading-tight"><?= htmlspecialchars($sessionUser['name'] ?? 'Trader') ?></h3>
                                        <p class="text-xs text-white/40 truncate font-medium"><?= htmlspecialchars($sessionUser['email'] ?? '') ?></p>
                                    </header>

                                    <div class="grid grid-cols-2 gap-2 relative z-10">
                                        <?php 
                                        $navItems = [
                                            ['Overview', '/trader/dashboard.php', 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'accent-blue'],
                                            ['Execution', '/trader/trade.php', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', 'accent-cyan'],
                                            ['Portfolio', '/trader/portfolio.php', 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4', 'accent-blue'],
                                            ['Wallet', '/trader/wallet.php', 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'growth-green'],
                                            ['History', '/trader/transactions.php', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'accent-blue'],
                                            ['Settings', '/trader/profile.php', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'accent-cyan'],
                                        ];
                                        foreach ($navItems as $item):
                                        ?>
                                            <a href="<?= APP_URL . $item[1] ?>" class="flex flex-col items-center gap-2 p-3 rounded-2xl hover:bg-white/5 transition-all group/item border border-transparent hover:border-white/5">
                                                <div class="w-8 h-8 rounded-lg bg-<?= $item[3] ?>/10 flex items-center justify-center text-<?= $item[3] ?> group-hover/item:bg-<?= $item[3] ?> group-hover/item:text-white transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item[2] ?>"></path></svg>
                                                </div>
                                                <span class="text-[10px] font-black text-white/50 group-hover/item:text-white transition-colors uppercase tracking-wider"><?= $item[0] ?></span>
                                            </a>
                                        <?php endforeach; ?>

                                        <div class="col-span-2 pt-4 mt-2 border-t border-white/5">
                                            <a href="<?= APP_URL ?>/api/logout.php" class="flex items-center justify-center gap-3 w-full py-3 rounded-xl bg-rose-500/10 text-rose-500 font-black text-[10px] tracking-[0.2em] hover:bg-rose-500 hover:text-white transition-all uppercase">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                                Sign Out
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <button onclick="toggleAuthModal('login')" class="text-base font-bold text-white hover:text-accent-cyan transition-colors">Login</button>
                        <button onclick="toggleAuthModal('register')" class="px-8 py-3 rounded-full bg-gradient-to-r from-accent-blue to-accent-cyan text-base font-bold text-white shadow-[0_0_20px_rgba(47,47,228,0.3)] hover:shadow-[0_0_30px_rgba(47,47,228,0.5)] transition-all transform hover:-translate-y-0.5">Sign Up</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="pt-24 flex-grow relative z-10">

    <!-- Checkout Slip Modal (Receipt-style) -->
    <div id="checkoutSlip" class="modal-hidden fixed inset-0 z-[110] flex items-start justify-center p-6 pt-32 overflow-y-auto">
        <div onclick="toggleCheckoutSlip()" class="modal-overlay absolute inset-0 bg-black/80 backdrop-blur-xl cursor-pointer"></div>
        <div class="relative w-full max-w-lg glass-card rounded-[48px] overflow-hidden flex flex-col shadow-[0_0_120px_rgba(0,0,0,0.9)] border border-white/10 animate-in zoom-in duration-300">
            <div class="absolute top-0 right-0 w-96 h-96 bg-accent-cyan/10 rounded-full blur-[120px]"></div>
            
            <header class="p-10 border-b border-white/5 relative z-10 text-center">
                <div class="w-20 h-20 rounded-full bg-growth-green/10 flex items-center justify-center text-growth-green mx-auto mb-8 shadow-[0_0_40px_rgba(111,207,151,0.2)]">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-3xl font-black text-white tracking-tighter">Transaction Slip</h3>
                <p class="text-xs text-white/40 tracking-[0.4em] uppercase mt-2">Vortex Capital Injection</p>
            </header>

            <div id="checkoutContent" class="p-12 space-y-10 relative z-10">
                <!-- Data will be injected here -->
            </div>

            <footer class="p-10 border-t border-white/5 relative z-10 bg-white/2">
                <button onclick="toggleCheckoutSlip()" class="w-full py-6 rounded-3xl bg-white text-deep font-black text-sm tracking-[0.3em] uppercase transition-all hover:scale-[1.02] active:scale-95 shadow-2xl">
                    Acknowledge
                </button>
            </footer>
        </div>
    </div>

    <script>
    function toggleCheckoutSlip(data = null) {
        const modal = document.getElementById('checkoutSlip');
        const content = document.getElementById('checkoutContent');
        
        if (data) {
            content.innerHTML = `
                <div class="space-y-8">
                    <div class="flex justify-between items-end border-b border-white/5 pb-6">
                        <span class="text-[10px] font-black text-white/20 tracking-widest uppercase">Value</span>
                        <span class="text-3xl font-black text-growth-green">PHP ${parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-10">
                        <div class="space-y-2">
                            <p class="text-[9px] font-black text-white/20 tracking-widest uppercase">Protocol</p>
                            <p class="text-sm font-black text-white uppercase">${data.method}</p>
                        </div>
                        <div class="space-y-2 text-right">
                            <p class="text-[9px] font-black text-white/20 tracking-widest uppercase">Status</p>
                            <p class="text-sm font-black text-amber-500 uppercase">Pending Review</p>
                        </div>
                    </div>
                    <div class="p-6 rounded-3xl bg-white/5 border border-white/5 italic text-center">
                        <p class="text-xs text-white/40 leading-relaxed">"Your transaction has been queued. A system administrator will verify the capital injection before it is reflected in your available balance."</p>
                    </div>
                </div>
            `;
            modal.classList.remove('modal-hidden');
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('modal-hidden');
            document.body.style.overflow = '';
        }
    }
    </script>
