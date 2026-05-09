<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ai_client.php';
require_once __DIR__ . '/includes/config.php';

$user = require_login();
$health = ai_health_check();
$title = 'AI Health Check';
require_once __DIR__ . '/includes/ui.php';
?>
<main class="max-w-4xl mx-auto px-4 py-12 pb-24">
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
        <div>
            <div class="flex items-center gap-3 text-accent-blue font-black text-[10px] tracking-[0.3em] uppercase mb-4">
                <span class="w-2 h-2 rounded-full bg-accent-blue shadow-[0_0_12px_#2F2FE4] <?= $health['ok'] ? 'animate-pulse' : '' ?>"></span>
                Sentinel Intelligence
            </div>
            <h1 class="text-5xl font-black text-white tracking-tighter mb-2">AI Health Status</h1>
            <p class="text-white/40 text-sm font-medium">Real-time diagnostics of the risk assessment neural engine.</p>
        </div>
        <a href="<?= APP_URL . (($user['role'] ?? 'trader') === 'admin' ? '/admin/dashboard.php' : '/trader/dashboard.php') ?>" class="px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 transition-all flex items-center gap-2">
            Back to Command
        </a>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Diagnostic Status -->
        <div class="lg:col-span-2 space-y-8">
            <div class="glass-card rounded-[40px] p-10 border border-white/5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-accent-blue/5 rounded-full blur-3xl"></div>
                
                <div class="flex items-center justify-between mb-10">
                    <h2 class="text-xl font-bold text-white flex items-center gap-3">
                        <svg class="w-6 h-6 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Engine Connectivity
                    </h2>
                    <div class="flex items-center gap-3 px-4 py-2 rounded-full <?= $health['ok'] ? 'bg-growth-green/10 text-growth-green border-growth-green/20' : 'bg-rose-500/10 text-rose-500 border-rose-500/20' ?> border text-xs font-black tracking-widest uppercase">
                        <?= $health['ok'] ? 'Online' : 'Offline' ?>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="p-6 rounded-3xl bg-black/20 border border-white/5">
                        <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-2">Neural Endpoint</p>
                        <code class="text-sm text-accent-cyan font-mono break-all"><?= htmlspecialchars($health['service_url']) ?></code>
                    </div>

                    <?php if ($health['ok']): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-4">Risk Calibration</p>
                                <div class="flex items-end gap-3 mb-4">
                                    <span class="text-5xl font-black text-white"><?= number_format((float)$health['probe']['risk_score'] * 100, 1) ?>%</span>
                                    <span class="text-xs font-bold text-white/40 mb-2">Score</span>
                                </div>
                                <div class="w-full h-3 bg-white/5 rounded-full overflow-hidden border border-white/5">
                                    <div class="h-full bg-gradient-to-r from-growth-green to-accent-blue" style="width: <?= $health['probe']['risk_score'] * 100 ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-white/20 tracking-widest uppercase mb-4">Confidence Level</p>
                                <div class="text-3xl font-black text-accent-cyan uppercase tracking-tighter mb-2"><?= htmlspecialchars((string)$health['probe']['risk_level']) ?></div>
                                <p class="text-xs text-white/40 leading-relaxed italic">Neural engine has successfully calibrated the risk parameters for the test transaction.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-10 rounded-3xl bg-rose-500/5 border border-rose-500/10 text-center">
                            <svg class="w-16 h-16 text-rose-500/40 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <h3 class="text-xl font-black text-white mb-2 uppercase tracking-tight">Intelligence Dropout</h3>
                            <p class="text-sm text-white/40 mb-8"><?= htmlspecialchars($health['reason'] ?: 'The AI Risk Engine is currently unreachable. System-wide fraud protection is operating in fail-safe mode.') ?></p>
                            <a href="<?= APP_URL ?>/ai_health.php" class="inline-flex items-center gap-2 text-xs font-black text-accent-blue uppercase tracking-widest hover:text-white transition-colors">
                                Attempt Reconnection
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-8 rounded-[40px] bg-white/5 border border-white/5">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-black text-white/60 uppercase tracking-widest">Raw Network Probe</h3>
                    <a href="<?= APP_URL ?>/api/ai_health.php" target="_blank" class="text-[10px] font-black text-accent-cyan uppercase tracking-widest hover:underline">Open JSON Feed</a>
                </div>
                <pre class="text-[10px] text-accent-cyan font-mono leading-relaxed bg-black/40 p-6 rounded-2xl border border-white/5 overflow-auto max-h-48"><?= htmlspecialchars(json_encode($health['probe'], JSON_PRETTY_PRINT)) ?></pre>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-8">
            <div class="glass-card rounded-[40px] p-8 border border-white/5">
                <h3 class="text-sm font-black text-white uppercase tracking-widest mb-6">Neural Protocol</h3>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-1 h-10 rounded-full bg-accent-blue"></div>
                        <div>
                            <p class="text-xs font-black text-white mb-1 uppercase tracking-wider">Pattern Matching</p>
                            <p class="text-[10px] text-white/40 leading-relaxed">System analyzes 124+ data points per transaction to detect velocity anomalies.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-1 h-10 rounded-full bg-accent-cyan"></div>
                        <div>
                            <p class="text-xs font-black text-white mb-1 uppercase tracking-wider">Neural Calibration</p>
                            <p class="text-[10px] text-white/40 leading-relaxed">Real-time risk scoring based on historical user behavior and global fraud trends.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 rounded-[40px] bg-gradient-to-br from-accent-blue/20 to-transparent border border-accent-blue/20">
                <h3 class="text-xs font-black text-accent-blue uppercase tracking-[0.2em] mb-4">Fail-Safe Active</h3>
                <p class="text-[10px] text-white/60 leading-relaxed">If the neural engine becomes unavailable, the system automatically enforces a strict risk threshold (0.50) to protect platform liquidity.</p>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/ui_footer.php'; ?>
