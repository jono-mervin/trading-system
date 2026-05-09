<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function request_risk_score(array $payload): array
{
    $ch = curl_init(AI_SERVICE_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || !$response || $httpCode >= 400) {
        return ['risk_score' => 0.5, 'risk_level' => 'unknown', 'reason' => 'AI service unavailable'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['risk_score' => 0.5, 'risk_level' => 'unknown', 'reason' => 'Invalid AI response'];
    }

    return $decoded;
}

function ai_health_check(): array
{
    $probe = request_risk_score([
        'user_id' => 0,
        'transaction_type' => 'deposit',
        'amount' => 1000,
        'transactions_last_hour' => 1,
    ]);

    $ok = isset($probe['risk_score'], $probe['risk_level']);
    $reason = (string) ($probe['reason'] ?? '');
    if ($reason === 'AI service unavailable' || $reason === 'Invalid AI response') {
        $ok = false;
    }

    return [
        'ok' => $ok,
        'service_url' => AI_SERVICE_URL,
        'probe' => $probe,
        'reason' => $reason
    ];
}
