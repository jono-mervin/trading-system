<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function paymongo_request(string $method, string $path, array $payload = []): array
{
    if (PAYMONGO_SECRET_KEY === '') {
        throw new RuntimeException('PayMongo secret key is not configured.');
    }

    $url = rtrim(PAYMONGO_API_BASE, '/') . $path;
    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
        'Content-Type: application/json',
    ];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
    ];

    if ($payload !== []) {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || $response === false) {
        throw new RuntimeException('PayMongo request failed: ' . $error);
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid PayMongo response.');
    }

    if ($httpCode >= 400) {
        $message = $decoded['errors'][0]['detail'] ?? 'Unknown PayMongo error.';
        throw new RuntimeException('PayMongo error: ' . $message);
    }

    return $decoded;
}

function paymongo_create_source(float $amount, string $type, string $successUrl, string $failedUrl): array
{
    $sourceType = $type === 'bank' ? 'grab_pay' : 'gcash';
    $centavos = (int) round($amount * 100);

    $payload = [
        'data' => [
            'attributes' => [
                'amount' => $centavos,
                'redirect' => [
                    'success' => $successUrl,
                    'failed' => $failedUrl,
                ],
                'type' => $sourceType,
                'currency' => 'PHP',
            ],
        ],
    ];

    return paymongo_request('POST', '/sources', $payload);
}

function paymongo_create_payment_from_source(string $sourceId, int $amountCentavos, string $currency = 'PHP'): array
{
    $payload = [
        'data' => [
            'attributes' => [
                'amount' => $amountCentavos,
                'currency' => strtoupper($currency),
                'source' => [
                    'id' => $sourceId,
                    'type' => 'source',
                ],
            ],
        ],
    ];

    return paymongo_request('POST', '/payments', $payload);
}

function paymongo_verify_signature(string $rawPayload, string $signatureHeader): bool
{
    if (PAYMONGO_WEBHOOK_SECRET === '' || $signatureHeader === '') {
        return false;
    }

    // Expected format: t=<timestamp>,te=<test_mode>,v1=<hmac>
    $parts = [];
    foreach (explode(',', $signatureHeader) as $piece) {
        $item = explode('=', trim($piece), 2);
        if (count($item) === 2) {
            $parts[$item[0]] = $item[1];
        }
    }

    $timestamp = $parts['t'] ?? '';
    $v1 = $parts['v1'] ?? '';
    if ($timestamp === '' || $v1 === '') {
        return false;
    }

    $signedPayload = $timestamp . '.' . $rawPayload;
    $expected = hash_hmac('sha256', $signedPayload, PAYMONGO_WEBHOOK_SECRET);
    return hash_equals($expected, $v1);
}
