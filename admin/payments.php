<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');

$rows = db()->query('
    SELECT p.id, u.name, u.email, p.amount, p.payment_type, p.provider, p.status, p.external_reference, p.created_at
    FROM payments p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT 100
')->fetchAll();

$title = 'Inbound Ledger | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-growth-green font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-growth-green shadow-[0_0_8px_#6FCF97]"></span>
            Financial Surveillance
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Inbound Ledger</h1>
    </header>

    <div class="glass-card rounded-[40px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/2 border-b border-white/5">
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase">Transaction</th>
                        <th class="px-8 py-6 text-xs font-black text-white/40 tracking-widest uppercase text-center">Protocol</th>
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
                                        <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-white tracking-wide"><?= htmlspecialchars($row['name'] ?? 'Trader') ?></p>
                                        <p class="text-xs text-white/40 tracking-wider">Ref: <?= htmlspecialchars($row['external_reference'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="text-xs font-black text-white tracking-widest uppercase"><?= htmlspecialchars($row['payment_type']) ?></span>
                                <p class="text-[10px] text-white/20 tracking-tighter"><?= htmlspecialchars($row['provider']) ?></p>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <span class="text-sm font-black text-white">PHP <?= number_format((float) $row['amount'], 2) ?></span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase border border-current/10 
                                    <?= $row['status'] === 'completed' ? 'bg-growth-green/10 text-growth-green' : ($row['status'] === 'failed' ? 'bg-rose-500/10 text-rose-500' : 'bg-amber-500/10 text-amber-500') ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex justify-center gap-2">
                                    <button onclick="viewPaymentDetail(<?= (int) $row['id'] ?>)" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-[10px] font-black tracking-widest text-white/60 hover:text-white hover:bg-white/10 transition-all uppercase">
                                        Details
                                    </button>
                                    <?php if ($row['provider'] === 'workflow' && $row['status'] === 'pending'): ?>
                                        <form action="<?= APP_URL ?>/api/admin_review_payment.php" method="post" class="flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="payment_id" value="<?= (int) $row['id'] ?>">
                                            <button name="status" value="completed" class="w-8 h-8 rounded-lg bg-growth-green text-deep flex items-center justify-center hover:scale-110 transition-all shadow-lg shadow-growth-green/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button name="status" value="failed" class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center hover:scale-110 transition-all shadow-lg shadow-rose-500/20">
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

<!-- Payment Detail Modal -->
<div id="paymentModal" class="modal-hidden">
    <div onclick="toggleModal('paymentModal')" class="modal-overlay cursor-pointer"></div>
    <div class="modal-content !max-w-2xl">
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden">
            <header class="mb-8 border-b border-white/5 pb-6">
                <h3 class="text-2xl font-black text-white mb-2 tracking-tight">Payment Detail #<span id="detailId"></span></h3>
                <p class="text-xs text-white/40 tracking-widest uppercase">Audit Trail & Identity</p>
            </header>

            <div id="modalLoading" class="py-20 text-center">
                <div class="animate-spin w-8 h-8 border-2 border-accent-cyan border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase">Fetching Protocol Data...</p>
            </div>

            <div id="modalContent" class="hidden space-y-8">
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">User Identity</p>
                        <p id="detailUser" class="text-sm font-bold text-white"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Value Transferred</p>
                        <p id="detailAmount" class="text-sm font-bold text-growth-green"></p>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">External Reference</p>
                    <p id="detailRef" class="text-xs font-black text-white/40 break-all"></p>
                </div>

                <div class="border-t border-white/5 pt-6">
                    <h4 class="text-xs font-black text-white tracking-widest uppercase mb-4">Event Timeline</h4>
                    <div id="detailLogs" class="space-y-3"></div>
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

async function viewPaymentDetail(id) {
    toggleModal('paymentModal');
    document.getElementById('detailId').textContent = id;
    document.getElementById('modalLoading').classList.remove('hidden');
    document.getElementById('modalContent').classList.add('hidden');
    
    try {
        const response = await fetch(`<?= APP_URL ?>/api/admin_get_payment_detail.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('detailUser').textContent = `${data.payment.name} (${data.payment.email})`;
            document.getElementById('detailAmount').textContent = `PHP ${parseFloat(data.payment.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            document.getElementById('detailRef').textContent = data.payment.external_reference || 'NO REFERENCE';
            
            const logsContainer = document.getElementById('detailLogs');
            logsContainer.innerHTML = '';
            
            if (data.logs.length === 0) {
                logsContainer.innerHTML = '<p class="text-[10px] text-white/20 italic">No events logged for this transaction.</p>';
            } else {
                data.logs.forEach(log => {
                    const logEl = document.createElement('div');
                    logEl.className = 'p-4 rounded-2xl bg-white/5 border border-white/5';
                    logEl.innerHTML = `
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[10px] font-black text-white tracking-widest uppercase">${log.event_type}</span>
                            <span class="text-[10px] text-white/20">${log.created_at}</span>
                        </div>
                        <pre class="text-[10px] text-white/40 whitespace-pre-wrap overflow-hidden">${JSON.stringify(JSON.parse(log.payload_json), null, 2)}</pre>
                    `;
                    logsContainer.appendChild(logEl);
                });
            }
            
            document.getElementById('modalLoading').classList.add('hidden');
            document.getElementById('modalContent').classList.remove('hidden');
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
