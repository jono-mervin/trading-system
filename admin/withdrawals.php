<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

$rows = db()->query('
    SELECT w.id, u.name, w.amount, w.destination_type, w.destination_value, w.bank_account_id, w.status, w.created_at,
           b.bank_name, b.account_name, b.account_number
    FROM withdrawals w
    JOIN users u ON u.id = w.user_id
    LEFT JOIN bank_accounts b ON b.id = w.bank_account_id
    ORDER BY w.created_at DESC
')->fetchAll();

$title = 'Outbound Hub | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-rose-500 font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_8px_#EF4444]"></span>
            Liquidity Extraction
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Outbound Hub</h1>
    </header>

    <div class="glass-card rounded-[40px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/2 border-b border-white/5">
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase">Requestor</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-center">Destination</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-right">Value</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-center">Status</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-center">Audit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($rows as $row): ?>
                        <tr class="hover:bg-white/2 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-xs font-black text-white/40 border border-white/5">
                                        <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-white tracking-wide"><?= htmlspecialchars($row['name']) ?></p>
                                        <p class="text-[10px] text-white/20 tracking-tighter uppercase"><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <?php if ($row['destination_type'] === 'bank' && $row['bank_account_id']): ?>
                                    <span class="text-xs font-black text-white tracking-widest uppercase"><?= htmlspecialchars((string) $row['bank_name']) ?></span>
                                    <p class="text-[10px] text-white/40 tracking-tighter">****<?= htmlspecialchars(substr((string) $row['account_number'], -4)) ?></p>
                                <?php else: ?>
                                    <span class="text-xs font-black text-white tracking-widest uppercase"><?= htmlspecialchars($row['destination_type']) ?></span>
                                    <p class="text-[10px] text-white/40 tracking-tighter"><?= htmlspecialchars($row['destination_value']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <span class="text-sm font-black text-white">PHP <?= number_format((float) $row['amount'], 2) ?></span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase border border-current/10 
                                    <?= $row['status'] === 'completed' ? 'bg-growth-green/10 text-growth-green' : ($row['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-500' : 'bg-amber-500/10 text-amber-500') ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex justify-center gap-2">
                                    <button onclick="viewWithdrawalDetail(<?= (int) $row['id'] ?>)" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-[10px] font-black tracking-widest text-white/60 hover:text-white hover:bg-white/10 transition-all uppercase">
                                        Details
                                    </button>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form action="<?= APP_URL ?>/api/admin_review_withdrawal.php" method="post" class="flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="withdrawal_id" value="<?= (int) $row['id'] ?>">
                                            <button name="status" value="completed" class="w-8 h-8 rounded-lg bg-growth-green text-deep flex items-center justify-center hover:scale-110 transition-all shadow-lg shadow-growth-green/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button name="status" value="rejected" class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center hover:scale-110 transition-all shadow-lg shadow-rose-500/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Withdrawal Detail Modal -->
<div id="withdrawalModal" class="modal-hidden">
    <div onclick="toggleModal('withdrawalModal')" class="modal-overlay cursor-pointer"></div>
    <div class="modal-content !max-w-2xl">
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden">
            <header class="mb-8 border-b border-white/5 pb-6">
                <h3 class="text-2xl font-black text-white mb-2 tracking-tight">Withdrawal Detail #<span id="wDetailId"></span></h3>
                <p class="text-xs text-white/40 tracking-widest uppercase">Extraction Audit</p>
            </header>

            <div id="wModalLoading" class="py-20 text-center">
                <div class="animate-spin w-8 h-8 border-2 border-rose-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase">Fetching Audit Data...</p>
            </div>

            <div id="wModalContent" class="hidden space-y-8">
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Requestor Identity</p>
                        <p id="wDetailUser" class="text-sm font-bold text-white"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Extraction Value</p>
                        <p id="wDetailAmount" class="text-sm font-bold text-rose-500"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Destination Protocol</p>
                        <p id="wDetailType" class="text-sm font-bold text-white uppercase"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Timestamp</p>
                        <p id="wDetailTime" class="text-sm font-bold text-white/40"></p>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-white/5 border border-white/5">
                    <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-2">Destination Credentials</p>
                    <p id="wDetailValue" class="text-sm font-black text-white tracking-widest"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleModal(id) {
    const modal = document.getElementById(id);
    modal.classList.toggle('modal-hidden');
    document.body.style.overflow = modal.classList.contains('modal-hidden') ? '' : 'hidden';
}

async function viewWithdrawalDetail(id) {
    toggleModal('withdrawalModal');
    document.getElementById('wDetailId').textContent = id;
    document.getElementById('wModalLoading').classList.remove('hidden');
    document.getElementById('wModalContent').classList.add('hidden');
    
    try {
        const response = await fetch(`<?= APP_URL ?>/api/admin_get_withdrawal_detail.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const w = data.withdrawal;
            document.getElementById('wDetailUser').textContent = `${w.name} (${w.email})`;
            document.getElementById('wDetailAmount').textContent = `PHP ${parseFloat(w.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            document.getElementById('wDetailType').textContent = w.destination_type;
            document.getElementById('wDetailTime').textContent = w.created_at;
            
            let destVal = '';
            if (w.destination_type === 'bank' && w.bank_account_id) {
                destVal = `${w.bank_name} - ${w.account_name} (${w.account_number})`;
            } else {
                destVal = w.destination_value;
            }
            document.getElementById('wDetailValue').textContent = destVal;
            
            document.getElementById('wModalLoading').classList.add('hidden');
            document.getElementById('wModalContent').classList.remove('hidden');
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
