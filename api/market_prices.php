<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('trader');
header('Content-Type: application/json');

$assets = db()->query('SELECT symbol, name, price FROM assets ORDER BY symbol')->fetchAll();
$out = [];
foreach ($assets as $asset) {
    $base = (float) $asset['price'];
    $wave = sin((float) time() / 15.0);
    $factor = 1 + ($wave * 0.005);
    $live = round($base * $factor, 2);
    $change = round($live - $base, 2);
    $out[] = [
        'symbol' => $asset['symbol'],
        'name' => $asset['name'],
        'base_price' => $base,
        'live_price' => $live,
        'change' => $change,
    ];
}

echo json_encode(['updated_at' => date('Y-m-d H:i:s'), 'assets' => $out], JSON_PRETTY_PRINT);
