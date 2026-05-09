<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
$paymentStatus = trim((string) ($_GET['payment_status'] ?? ''));
$ledgerType = trim((string) ($_GET['ledger_type'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));

$paymentsSql = 'SELECT id, amount, payment_type, provider, external_reference, status, created_at FROM payments WHERE user_id = :user_id';
$paymentsParams = ['user_id' => $user['id']];
if ($paymentStatus !== '') { $paymentsSql .= ' AND status = :payment_status'; $paymentsParams['payment_status'] = $paymentStatus; }
if ($from !== '') { $paymentsSql .= ' AND created_at >= :from_date'; $paymentsParams['from_date'] = $from . ' 00:00:00'; }
if ($to !== '') { $paymentsSql .= ' AND created_at <= :to_date'; $paymentsParams['to_date'] = $to . ' 23:59:59'; }
if ($search !== '') { $paymentsSql .= ' AND external_reference LIKE :q'; $paymentsParams['q'] = '%' . $search . '%'; }
$paymentsSql .= ' ORDER BY created_at DESC LIMIT 100';
$paymentsStmt = db()->prepare($paymentsSql);
$paymentsStmt->execute($paymentsParams);
$payments = $paymentsStmt->fetchAll();

$ledgerSql = 'SELECT type, amount, balance_after, notes, created_at FROM wallet_ledger WHERE user_id = :user_id';
$ledgerParams = ['user_id' => $user['id']];
if ($ledgerType !== '') { $ledgerSql .= ' AND type = :ledger_type'; $ledgerParams['ledger_type'] = $ledgerType; }
if ($from !== '') { $ledgerSql .= ' AND created_at >= :ledger_from_date'; $ledgerParams['ledger_from_date'] = $from . ' 00:00:00'; }
if ($to !== '') { $ledgerSql .= ' AND created_at <= :ledger_to_date'; $ledgerParams['ledger_to_date'] = $to . ' 23:59:59'; }
$ledgerSql .= ' ORDER BY created_at DESC LIMIT 100';
$ledgerStmt = db()->prepare($ledgerSql);
$ledgerStmt->execute($ledgerParams);
$ledgerRows = $ledgerStmt->fetchAll();

$title = 'Vortex History | Transactions';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 text-accent-blue font-bold text-xs tracking-[0.2em] mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-blue shadow-[0_0_8px_#2F2FE4]"></span>
                Audit Trail
            </div>
            <h1 class="text-4xl font-black text-white tracking-tight">Transaction History</h1>
        </div>
        <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            Filters
        </button>
    </header>

    <div id="filterPanel" class="<?= ($paymentStatus || $ledgerType || $from || $to || $search) ? '' : 'hidden' ?> mb-12 animate-in fade-in slide-in-from-top-4">
        <form method="get" class="glass-card rounded-[32px] p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-white/40 tracking-widest ml-1">From</label>
                <input class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-white text-sm outline-none focus:border-accent-blue" type="date" name="from" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-white/40 tracking-widest ml-1">To</label>
                <input class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-white text-sm outline-none focus:border-accent-blue" type="date" name="to" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-white/40 tracking-widest ml-1">Status</label>
                <select name="payment_status" class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-white text-sm outline-none focus:border-accent-blue appearance-none">
                    <option value="">All Status</option>
                    <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $paymentStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-white/40 tracking-widest ml-1">Type</label>
                <select name="ledger_type" class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-white text-sm outline-none focus:border-accent-blue appearance-none">
                    <option value="">All Types</option>
                    <option value="deposit" <?= $ledgerType === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                    <option value="withdraw" <?= $ledgerType === 'withdraw' ? 'selected' : '' ?>>Withdraw</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full py-3 rounded-xl bg-accent-blue text-white font-black text-xs tracking-widest hover:shadow-lg hover:shadow-accent-blue/20 transition-all">Search</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Payments Section -->
        <div class="glass-card rounded-[40px] overflow-hidden">
            <div class="p-8 border-b border-white/5">
                <h2 class="text-xl font-black text-white tracking-tight">Payments Timeline</h2>
            </div>
            <div class="p-8 space-y-6">
                <?php foreach ($payments as $payment): ?>
                    <div class="relative pl-8 border-l border-white/10 pb-8 last:pb-0 group">
                        <div class="absolute -left-1.5 top-0 w-3 h-3 rounded-full bg-accent-blue border-2 border-deep shadow-[0_0_8px_#2F2FE4] group-hover:scale-150 transition-transform"></div>
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="text-sm font-black text-white"><?= htmlspecialchars($payment['payment_type']) ?> Deposit</h4>
                                <p class="text-[10px] text-white/30 tracking-widest"><?= htmlspecialchars($payment['created_at']) ?></p>
                            </div>
                            <span class="text-sm font-black text-white">PHP <?= number_format((float) $payment['amount'], 2) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/10 text-[8px] font-black tracking-widest text-white/40"><?= htmlspecialchars($payment['status']) ?></span>
                            <span class="text-[8px] text-white/20 font-mono">REF: <?= htmlspecialchars($payment['external_reference']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$payments): ?>
                    <p class="text-white/20 text-center py-12 italic text-sm">No payment records found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ledger Section -->
        <div class="glass-card rounded-[40px] overflow-hidden">
            <div class="p-8 border-b border-white/5">
                <h2 class="text-xl font-black text-white tracking-tight">Wallet Ledger</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-white/30 tracking-[0.2em] bg-white/2">
                            <th class="px-8 py-4">Operation</th>
                            <th class="px-8 py-4 text-right">Delta</th>
                            <th class="px-8 py-4 text-right">Balance After</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($ledgerRows as $row): ?>
                            <tr class="hover:bg-white/2 transition-colors">
                                <td class="px-8 py-5">
                                    <p class="text-xs font-black text-white"><?= htmlspecialchars((string) $row['type']) ?></p>
                                    <p class="text-[9px] text-white/30"><?= htmlspecialchars($row['created_at']) ?></p>
                                </td>
                                <td class="px-8 py-5 text-right font-black text-sm <?= (float) $row['amount'] >= 0 ? 'text-growth-green' : 'text-rose-500' ?>">
                                    <?= (float) $row['amount'] >= 0 ? '+' : '' ?><?= number_format((float) $row['amount'], 2) ?>
                                </td>
                                <td class="px-8 py-5 text-right text-xs text-white/60 font-mono">
                                    <?= number_format((float) $row['balance_after'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$ledgerRows): ?>
                    <p class="text-white/20 text-center py-12 italic text-sm">No ledger entries yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
