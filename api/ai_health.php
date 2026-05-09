<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_client.php';

require_login();

header('Content-Type: application/json');
echo json_encode(ai_health_check(), JSON_PRETTY_PRINT);
