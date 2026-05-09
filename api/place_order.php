<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/rate_limit.php';

$user = require_role('trader');
rate_limit_or_fail('place_order_' . (string) $user['id'], 20, 60);
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$assetId = (int) ($_POST['asset_id'] ?? 0);
$side = $_POST['side'] ?? 'buy';
$quantity = (float) ($_POST['quantity'] ?? 0);
if ($assetId <= 0 || $quantity <= 0 || !in_array($side, ['buy', 'sell'], true)) {
    exit('Invalid order input.');
}

$assetStmt = db()->prepare('SELECT id, price FROM assets WHERE id = :id LIMIT 1');
$assetStmt->execute(['id' => $assetId]);
$asset = $assetStmt->fetch();
if (!$asset) {
    exit('Asset not found.');
}

$price = (float) $asset['price'];
$total = $price * $quantity;
$pdo = db();
$pdo->beginTransaction();

try {
    if ($side === 'buy') {
        if (get_wallet_balance((int) $user['id']) < $total) {
            throw new RuntimeException('Insufficient balance for buy order.');
        }
    } else {
        $hStmt = $pdo->prepare('SELECT quantity FROM portfolios WHERE user_id = :user_id AND asset_id = :asset_id LIMIT 1');
        $hStmt->execute(['user_id' => $user['id'], 'asset_id' => $assetId]);
        $holding = (float) ($hStmt->fetchColumn() ?: 0);
        if ($holding < $quantity) {
            throw new RuntimeException('Insufficient holdings for sell order.');
        }
    }

    $orderStmt = $pdo->prepare('
        INSERT INTO orders (user_id, asset_id, side, quantity, price, total, status)
        VALUES (:user_id, :asset_id, :side, :quantity, :price, :total, :status)
    ');
    $orderStmt->execute([
        'user_id' => $user['id'],
        'asset_id' => $assetId,
        'side' => $side,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total,
        'status' => 'filled',
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $portfolioStmt = $pdo->prepare('SELECT id, quantity, avg_price FROM portfolios WHERE user_id = :user_id AND asset_id = :asset_id LIMIT 1');
    $portfolioStmt->execute(['user_id' => $user['id'], 'asset_id' => $assetId]);
    $portfolio = $portfolioStmt->fetch();

    if ($side === 'buy') {
        add_ledger_entry((int) $user['id'], 'trade_buy', -$total, $orderId, 'Buy order');
        if ($portfolio) {
            $newQty = (float) $portfolio['quantity'] + $quantity;
            $newAvg = (((float) $portfolio['quantity'] * (float) $portfolio['avg_price']) + ($quantity * $price)) / $newQty;
            $upd = $pdo->prepare('UPDATE portfolios SET quantity = :qty, avg_price = :avg WHERE id = :id');
            $upd->execute(['qty' => $newQty, 'avg' => $newAvg, 'id' => $portfolio['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO portfolios (user_id, asset_id, quantity, avg_price) VALUES (:user_id, :asset_id, :qty, :avg)');
            $ins->execute(['user_id' => $user['id'], 'asset_id' => $assetId, 'qty' => $quantity, 'avg' => $price]);
        }
    } else {
        add_ledger_entry((int) $user['id'], 'trade_sell', $total, $orderId, 'Sell order');
        $newQty = (float) $portfolio['quantity'] - $quantity;
        $upd = $pdo->prepare('UPDATE portfolios SET quantity = :qty WHERE id = :id');
        $upd->execute(['qty' => max(0, $newQty), 'id' => $portfolio['id']]);
    }

    log_audit((int) $user['id'], 'trade_' . $side, 'Order ID: ' . $orderId);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    exit($e->getMessage());
}

header('Location: ' . APP_URL . '/trader/dashboard.php');
