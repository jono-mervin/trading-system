<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
http_response_code(403);
exit('Workflow payment completion is admin-only. Use admin payment review.');
