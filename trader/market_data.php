<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

require_role('trader');
$assets = db()->query('SELECT symbol, name, price FROM assets ORDER BY symbol')->fetchAll();

$title = 'Market Data';
require_once __DIR__ . '/../includes/ui.php';
?>
<main class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Market Data</h1>
        <span id="market-updated-at" class="text-xs text-slate-400">Updating...</span>
    </div>
    <div id="market-list" class="space-y-2">
        <?php foreach ($assets as $asset): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex justify-between" data-symbol="<?= htmlspecialchars($asset['symbol']) ?>">
                <span><?= htmlspecialchars($asset['symbol']) ?> - <?= htmlspecialchars($asset['name']) ?></span>
                <span>
                    <span class="live-price">PHP <?= number_format((float) $asset['price'], 2) ?></span>
                    <span class="text-xs ml-2 change text-slate-400">+0.00</span>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<script>
(() => {
  const update = async () => {
    try {
      const response = await fetch("<?= APP_URL ?>/api/market_prices.php", { cache: "no-store" });
      if (!response.ok) return;
      const data = await response.json();
      (data.assets || []).forEach((asset) => {
        const row = document.querySelector(`[data-symbol="${asset.symbol}"]`);
        if (!row) return;
        const priceEl = row.querySelector(".live-price");
        const changeEl = row.querySelector(".change");
        if (priceEl) priceEl.textContent = `PHP ${Number(asset.live_price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (changeEl) {
          const positive = Number(asset.change) >= 0;
          changeEl.textContent = `${positive ? "+" : ""}${Number(asset.change).toFixed(2)}`;
          changeEl.className = `text-xs ml-2 change ${positive ? "text-emerald-400" : "text-rose-400"}`;
        }
      });
      const updatedAt = document.getElementById("market-updated-at");
      if (updatedAt) updatedAt.textContent = `Updated: ${data.updated_at || ""}`;
    } catch (_err) {
      // silent polling failure
    }
  };
  update();
  setInterval(update, 5000);
})();
</script>
<?php require_once __DIR__ . '/../includes/ui_footer.php'; ?>
