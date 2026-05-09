<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
$paymentId = (int) ($_GET['payment_id'] ?? 0);

$stmt = db()->prepare('SELECT id, amount, payment_type, status, provider FROM payments WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute(['id' => $paymentId, 'user_id' => $user['id']]);
$payment = $stmt->fetch();
if (!$payment) {
    exit('Payment not found.');
}

$title = 'Deposit Checkout';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-growth-green font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-growth-green shadow-[0_0_8px_#6FCF97]"></span>
            Finalizing Protocol
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Deposit Checkout</h1>
    </header>

    <div class="glass-card rounded-[40px] p-10 max-w-3xl mx-auto relative overflow-hidden group">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-growth-green/5 rounded-full blur-[100px]"></div>
        
        <div class="relative z-10 space-y-8">
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-2">Transaction Mode</p>
                    <p class="text-lg font-black text-white"><?= htmlspecialchars(strtoupper((string) $payment['provider'])) ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-2">Capital Value</p>
                    <p class="text-lg font-black text-growth-green">PHP <?= number_format((float) $payment['amount'], 2) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-2">Payment Protocol</p>
                    <p class="text-lg font-black text-white uppercase"><?= htmlspecialchars((string) $payment['payment_type']) ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-2">Protocol Status</p>
                    <span class="px-3 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-500 text-[10px] font-black tracking-widest uppercase">
                        <?= htmlspecialchars((string) $payment['status']) ?>
                    </span>
                </div>
            </div>

            <?php if (PAYMENT_MODE === 'workflow' && $payment['status'] === 'pending'): ?>
                <div class="p-6 rounded-3xl bg-amber-500/5 border border-amber-500/10">
                    <p class="text-sm text-amber-200/70 leading-relaxed font-medium italic">
                        "Your transaction has been queued. A system administrator will verify the capital injection before it is reflected in your available balance."
                    </p>
                </div>
            <?php endif; ?>

            <div class="pt-8 border-t border-white/5">
                <a href="<?= APP_URL ?>/trader/wallet.php" class="inline-flex items-center gap-3 text-xs font-black tracking-widest text-white/40 hover:text-white transition-colors group">
                    <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    RETURN TO WALLET
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
