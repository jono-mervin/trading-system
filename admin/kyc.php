<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('admin');
$rows = db()->query('
    SELECT
        k.id, u.name, u.email,
        k.full_name, k.date_of_birth, k.nationality, k.address_line, k.city, k.province, k.postal_code,
        k.contact_number, k.occupation, k.source_of_funds,
        k.id_type, k.id_number, k.id_image, k.selfie_image, k.status
    FROM kyc_verifications k
    JOIN users u ON u.id = k.user_id
    ORDER BY k.created_at DESC
')->fetchAll();

$title = 'Identity Audit | Vortex';
require_once __DIR__ . '/../includes/ui.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pb-24 relative z-10">
    <header class="mb-12">
        <div class="flex items-center gap-3 text-accent-cyan font-bold text-sm tracking-[0.1em] mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-accent-cyan shadow-[0_0_8px_#00B5D8]"></span>
            Compliance Review
        </div>
        <h1 class="text-4xl font-black text-white tracking-tight">Identity Audit</h1>
    </header>

    <div class="space-y-6">
        <?php foreach ($rows as $row): ?>
            <div class="glass-card rounded-[40px] p-8 group relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-accent-cyan/5 rounded-full blur-[100px] transition-all"></div>
                
                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Basic Info -->
                    <div class="lg:col-span-1 border-r border-white/5 pr-8">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-xl font-black text-white/40 border border-white/5">
                                <?= strtoupper(substr($row['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white"><?= htmlspecialchars($row['name']) ?></h3>
                                <p class="text-xs text-white/40 tracking-wider"><?= htmlspecialchars($row['email']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase border border-current/10 
                                <?= $row['status'] === 'approved' ? 'bg-growth-green/10 text-growth-green' : ($row['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-500' : 'bg-amber-500/10 text-amber-500') ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Personal Details -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Legal Name</p>
                                <p class="text-sm font-bold text-white"><?= htmlspecialchars((string) $row['full_name']) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Date of Birth</p>
                                <p class="text-sm font-bold text-white"><?= htmlspecialchars((string) $row['date_of_birth']) ?></p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">Address</p>
                            <p class="text-sm font-bold text-white/80 leading-relaxed">
                                <?= htmlspecialchars((string) $row['address_line']) ?>, <?= htmlspecialchars((string) $row['city']) ?>, <?= htmlspecialchars((string) $row['province']) ?> <?= htmlspecialchars((string) $row['postal_code']) ?>
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/5">
                            <div>
                                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-1">ID Type & Number</p>
                                <p class="text-sm font-bold text-accent-cyan uppercase"><?= htmlspecialchars($row['id_type']) ?> - <?= htmlspecialchars($row['id_number']) ?></p>
                            </div>
                            <div class="flex gap-4 items-end">
                                <a href="<?= APP_URL . '/' . htmlspecialchars((string) $row['id_image']) ?>" target="_blank" class="text-[10px] font-black tracking-widest text-white/60 hover:text-white uppercase transition-colors underline underline-offset-4">ID SCAN</a>
                                <a href="<?= APP_URL . '/' . htmlspecialchars((string) $row['selfie_image']) ?>" target="_blank" class="text-[10px] font-black tracking-widest text-white/60 hover:text-white uppercase transition-colors underline underline-offset-4">SELFIE</a>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="lg:col-span-1 flex flex-col justify-center gap-4 pl-8 border-l border-white/5">
                        <?php if ($row['status'] === 'pending'): ?>
                            <form action="<?= APP_URL ?>/api/admin_review_kyc.php" method="post" class="space-y-3">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="kyc_id" value="<?= (int) $row['id'] ?>">
                                
                                <button name="status" value="approved" class="w-full py-4 rounded-2xl bg-growth-green text-deep font-black text-xs tracking-widest hover:scale-105 transition-all shadow-xl shadow-growth-green/10 uppercase">
                                    Approve Profile
                                </button>
                                
                                <button name="status" value="rejected" class="w-full py-4 rounded-2xl bg-white/5 border border-white/10 text-rose-500 font-black text-xs tracking-widest hover:bg-rose-500 hover:text-white transition-all uppercase">
                                    Reject Audit
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center p-6 rounded-3xl bg-white/2 border border-white/5">
                                <p class="text-[10px] font-black text-white/20 tracking-[0.2em] uppercase">Audit Locked</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
