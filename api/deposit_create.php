<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/paymongo.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/rate_limit.php';

$user = require_role('trader');
rate_limit_or_fail('deposit_create_' . (string) $user['id'], 10, 60);
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$amount = (float) ($_POST['amount'] ?? 0);
$paymentType = $_POST['payment_type'] ?? 'gcash';
if ($amount < 100) {
    exit('Minimum deposit is PHP 100.');
}

if (!in_array($paymentType, ['gcash', 'bank'], true)) {
    exit('Invalid payment type.');
}

$idempotencyKey = bin2hex(random_bytes(16));
$methodId = $paymentType === 'bank' ? 2 : 1;
$provider = PAYMENT_MODE === 'paymongo' ? 'paymongo' : 'workflow';
$externalReference = 'wf_' . time() . '_' . bin2hex(random_bytes(4));
$checkoutUrl = APP_URL . '/trader/deposit_checkout.php';

if (PAYMENT_MODE === 'paymongo') {
    if ($paymentType === 'bank') {
        exit('Bank checkout source is not configured yet. Use GCash for now.');
    }

    try {
        $source = paymongo_create_source(
            $amount,
            $paymentType,
            APP_URL . '/trader/wallet.php?deposit=success',
            APP_URL . '/trader/wallet.php?deposit=failed'
        );
    } catch (Throwable $e) {
        exit($e->getMessage());
    }

    $sourceId = (string) ($source['data']['id'] ?? '');
    $checkoutUrl = (string) ($source['data']['attributes']['redirect']['checkout_url'] ?? '');
    if ($sourceId === '' || $checkoutUrl === '') {
        exit('Unable to create PayMongo checkout source.');
    }
    $externalReference = $sourceId;
}

$stmt = db()->prepare('
    INSERT INTO payments (user_id, amount, method_id, provider, external_reference, payment_type, status, idempotency_key)
    VALUES (:user_id, :amount, :method_id, :provider, :external_reference, :payment_type, :status, :idempotency_key)
');
$stmt->execute([
    'user_id' => $user['id'],
    'amount' => $amount,
    'method_id' => $methodId,
    'provider' => $provider,
    'external_reference' => $externalReference,
    'payment_type' => $paymentType,
    'status' => 'pending',
    'idempotency_key' => $idempotencyKey,
]);

$paymentId = (int) db()->lastInsertId();
log_audit((int) $user['id'], 'deposit_source_created', 'Reference: ' . $externalReference);

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    echo json_encode([
        'status' => 'success',
        'payment_id' => $paymentId,
        'checkout_url' => $checkoutUrl
    ]);
    exit;
}

if (PAYMENT_MODE === 'workflow') {
    header('Location: ' . $checkoutUrl . '?payment_id=' . $paymentId);
    exit;
}

header('Location: ' . $checkoutUrl);
