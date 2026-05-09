<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
$balance = get_wallet_balance((int) $user['id']);
$bankStmt = db()->prepare('SELECT id, bank_name, account_name, account_number FROM bank_accounts WHERE user_id = :user_id ORDER BY created_at DESC');
$bankStmt->execute(['user_id' => $user['id']]);
$bankAccounts = $bankStmt->fetchAll();

$title = 'Vortex Wallet | Digital Assets';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 text-accent-blue font-bold text-sm tracking-[0.1em] mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-blue shadow-[0_0_8px_#2F2FE4]"></span>
                Financial Hub
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Wallet</h1>
        </div>
        <div class="flex gap-4">
            <button onclick="toggleModal('depositModal')"
                class="px-8 py-4 rounded-2xl bg-white text-deep font-black text-sm tracking-widest hover:bg-gray-100 transition-all shadow-xl">DEPOSIT</button>
            <button onclick="toggleModal('withdrawModal')"
                class="px-8 py-4 rounded-2xl bg-accent-cyan text-deep font-black text-sm tracking-widest hover:bg-opacity-90 transition-all shadow-xl shadow-accent-cyan/20">WITHDRAW</button>
        </div>
    </header>

    <!-- Balance Highlight -->
    <div class="glass-card rounded-[40px] p-10 mb-12 relative overflow-hidden group">
        <div
            class="absolute -top-24 -right-24 w-64 h-64 bg-accent-blue/10 rounded-full blur-[100px] group-hover:bg-accent-blue/20 transition-all">
        </div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
            <div>
                <p class="text-xs font-black text-white/40 tracking-[0.2em] mb-4">Available Liquidity</p>
                <h2 class="text-6xl font-black text-white tracking-tighter">PHP <?= number_format($balance, 2) ?></h2>
            </div>
            <div class="flex items-center gap-4">
                <?php if (($user['status'] ?? '') === 'verified'): ?>
                    <div
                        class="px-6 py-3 rounded-2xl bg-growth-green/10 border border-growth-green/20 text-growth-green font-black text-sm tracking-widest">
                        Verified
                    </div>
                <?php elseif (($user['status'] ?? '') === 'pending'): ?>
                    <div
                        class="px-6 py-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 font-black text-sm tracking-widest">
                        Pending
                    </div>
                <?php else: ?>
                    <div
                        class="px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white/40 font-black text-sm tracking-widest">
                        Unverified
                    </div>
                <?php endif; ?>
                <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Section (Restored) -->
    <section class="glass-card rounded-[40px] overflow-hidden mb-12">
        <div class="p-8 border-b border-white/5 flex justify-between items-center">
            <h2 class="text-xl font-black text-white tracking-tight">Recent Activity</h2>
            <a href="<?= APP_URL ?>/trader/transactions.php" class="text-xs font-black text-accent-blue tracking-[0.2em] uppercase hover:underline">Full Audit</a>
        </div>
        <div class="p-8 space-y-6">
            <?php
            $recentStmt = db()->prepare('SELECT amount, payment_type, status, created_at FROM payments WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5');
            $recentStmt->execute(['user_id' => $user['id']]);
            $recentPayments = $recentStmt->fetchAll();
            
            foreach ($recentPayments as $p): 
            ?>
                <div class="flex justify-between items-center group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 group-hover:bg-accent-blue/10 group-hover:text-accent-blue transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-white uppercase"><?= htmlspecialchars((string)$p['payment_type']) ?> Deposit</p>
                            <p class="text-[10px] text-white/30 tracking-widest"><?= htmlspecialchars((string)$p['created_at']) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-white">PHP <?= number_format((float)$p['amount'], 2) ?></p>
                        <p class="text-[9px] font-black tracking-widest uppercase <?= $p['status'] === 'completed' ? 'text-growth-green' : 'text-amber-500' ?>"><?= htmlspecialchars((string)$p['status']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$recentPayments): ?>
                <p class="text-white/20 text-center py-10 italic text-sm">No recent transactions.</p>
            <?php endif; ?>
        </div>
    </section>

</main>

<!-- Deposit Modal -->
<div id="depositModal" class="modal-hidden">
    <div onclick="toggleModal('depositModal')" class="modal-overlay cursor-pointer"></div>
    <div class="modal-content">
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden">
            <h3 class="text-2xl font-black text-white mb-2 tracking-tight">Deposit Capital</h3>
            <p class="text-sm text-white/40 mb-8 tracking-widest">Fund your trading account</p>

            <form id="depositForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                <div class="space-y-3">
                    <label class="text-xs font-black tracking-widest text-white/60 ml-1">Capital Amount (PHP)</label>
                    <input type="number" step="0.01" min="100" name="amount" id="deposit_amount" required placeholder="0.00"
                        class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-blue outline-none text-white font-black text-xl placeholder:text-white/10 transition-all">
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black tracking-widest text-white/60 ml-1">Payment Gateway</label>
                    <select name="payment_type" id="deposit_type"
                        class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-blue outline-none text-white font-bold transition-all appearance-none">
                        <option value="gcash">GCash</option>
                        <option value="bank">InstaPay / Pesonet</option>
                    </select>
                </div>

                <button type="submit" id="depositSubmit"
                    class="w-full py-6 rounded-2xl bg-white text-deep font-black text-lg hover:bg-gray-100 transition-all transform hover:-translate-y-1 shadow-2xl">
                    <?= PAYMENT_MODE === 'workflow' ? 'Deposit' : 'Proceed to Gateway' ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="modal-hidden">
    <div onclick="toggleModal('withdrawModal')" class="modal-overlay cursor-pointer"></div>
    <div class="modal-content">
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden">
            <h3 class="text-2xl font-black text-white mb-2 tracking-tight">Withdrawal Hub</h3>
            <p class="text-sm text-white/40 mb-8 tracking-widest">Off-ramp your profits securely</p>

            <form class="space-y-6" action="<?= APP_URL ?>/api/withdraw_request.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                <div class="space-y-3">
                    <label class="text-xs font-black tracking-widest text-white/60 ml-1">Extraction Amount (PHP)</label>
                    <input type="number" step="0.01" min="100" name="amount" required placeholder="0.00"
                        class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-black text-xl placeholder:text-white/10 transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <label class="text-xs font-black tracking-widest text-white/60 ml-1">Destination</label>
                        <select id="destination_type" name="destination_type"
                            class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold transition-all appearance-none">
                            <option value="bank">BANK ACCOUNT</option>
                            <option value="ewallet">E-WALLET</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="text-xs font-black tracking-widest text-white/60 ml-1">Saved Methods</label>
                        <select id="bank_account_select" name="bank_account_id"
                            class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold transition-all appearance-none text-xs">
                            <option value="">Select Method...</option>
                            <?php foreach ($bankAccounts as $account): ?>
                                <option value="<?= (int) $account['id'] ?>">
                                    <?= htmlspecialchars($account['bank_name']) ?>
                                    (****<?= htmlspecialchars(substr((string) $account['account_number'], -4)) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="destination_value_container" class="space-y-3 hidden">
                    <label class="text-xs font-black tracking-widest text-white/60 ml-1">Receiver Identifier
                        (Phone/Email)</label>
                    <input id="destination_value" name="destination_value" placeholder="Enter identifier..."
                        class="w-full px-6 py-5 rounded-2xl bg-black/40 border border-white/5 focus:border-accent-cyan outline-none text-white font-bold transition-all">
                </div>

                <button
                    class="w-full py-6 rounded-2xl bg-accent-cyan text-deep font-black text-lg hover:bg-opacity-90 transition-all transform hover:-translate-y-1 shadow-2xl shadow-accent-cyan/20">
                    Request Extraction
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(id) {
        const modal = document.getElementById(id);
        modal.classList.toggle('modal-hidden');
        if (!modal.classList.contains('modal-hidden')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    (() => {
        const depositForm = document.getElementById('depositForm');
        if (depositForm) {
            depositForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('depositSubmit');
                const amount = document.getElementById('deposit_amount').value;
                const method = document.getElementById('deposit_type').value;
                
                btn.disabled = true;
                btn.innerHTML = '<span class="flex items-center justify-center gap-2"><div class="w-4 h-4 border-2 border-deep border-t-transparent rounded-full animate-spin"></div> PROCESSING...</span>';
                
                try {
                    const formData = new FormData(depositForm);
                    const response = await fetch('<?= APP_URL ?>/api/deposit_create.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    // Since the API currently redirects or exits with text, we might need to handle it.
                    // But if it's workflow mode, we want the modal.
                    toggleModal('depositModal');
                    toggleCheckoutSlip({ amount, method });
                } catch (err) {
                    alert('Transaction failed. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = 'Deposit';
                }
            });
        }

        const typeSelect = document.getElementById('destination_type');
        const bankSelect = document.getElementById('bank_account_select');
        const valueContainer = document.getElementById('destination_value_container');
        const valueInput = document.getElementById('destination_value');

        if (!typeSelect || !bankSelect || !valueInput) return;

        const syncFields = () => {
            const type = typeSelect.value;
            if (type === 'bank') {
                bankSelect.parentElement.classList.remove('hidden');
                bankSelect.required = true;
                valueContainer.classList.add('hidden');
                valueInput.required = false;
            } else {
                bankSelect.parentElement.classList.add('hidden');
                bankSelect.required = false;
                valueContainer.classList.remove('hidden');
                valueInput.required = true;
            }
        };

        typeSelect.addEventListener('change', syncFields);
        syncFields();

        // Subtle Card Hover Effect (Pure CSS now)
    })();
</script>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>