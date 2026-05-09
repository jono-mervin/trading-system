<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/config.php';

$admin = require_role('admin');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid CSRF token.');
    header('Location: ' . APP_URL . '/admin/kyc.php');
    exit;
}

$kycId = (int) ($_POST['kyc_id'] ?? 0);
$status = $_POST['status'] ?? 'rejected';
if (!in_array($status, ['approved', 'rejected'], true)) {
    set_flash('error', 'Invalid KYC status.');
    header('Location: ' . APP_URL . '/admin/kyc.php');
    exit;
}

$stmt = db()->prepare('UPDATE kyc_verifications SET status = :status, reviewed_by = :reviewed_by WHERE id = :id');
$stmt->execute([
    'status' => $status,
    'reviewed_by' => $admin['id'],
    'id' => $kycId,
]);

if ($status === 'approved') {
    $uStmt = db()->prepare('
        UPDATE users u
        JOIN kyc_verifications k ON k.user_id = u.id
        SET u.status = :verified
        WHERE k.id = :kyc_id
    ');
    $uStmt->execute(['verified' => 'verified', 'kyc_id' => $kycId]);
}

log_audit((int) $admin['id'], 'kyc_' . $status, 'KYC ID: ' . $kycId);
set_flash('success', 'KYC review completed.');
header('Location: ' . APP_URL . '/admin/kyc.php');
