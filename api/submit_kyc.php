<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

function save_kyc_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error.');
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('Invalid file type.');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid('kyc_', true) . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
    $target = __DIR__ . '/../uploads/kyc/' . $name;
    move_uploaded_file($file['tmp_name'], $target);
    return 'uploads/kyc/' . $name;
}

$idImage = save_kyc_image($_FILES['id_image'] ?? []);
$selfieImage = save_kyc_image($_FILES['selfie_image'] ?? []);

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$dateOfBirth = trim((string) ($_POST['date_of_birth'] ?? ''));
$nationality = trim((string) ($_POST['nationality'] ?? ''));
$region = trim((string) ($_POST['region'] ?? ''));
$city = trim((string) ($_POST['city'] ?? ''));
$barangay = trim((string) ($_POST['barangay'] ?? ''));
$addressLine = trim((string) ($_POST['address_line'] ?? ''));
$idType = trim((string) ($_POST['id_type'] ?? ''));
$idNumber = trim((string) ($_POST['id_number'] ?? ''));

if (
    $fullName === '' || $dateOfBirth === '' || $nationality === '' || 
    $region === '' || $city === '' || $barangay === '' || $addressLine === '' ||
    $idType === '' || $idNumber === ''
) {
    exit('All KYC credential fields are required.');
}

try {
    $idImage = save_kyc_image($_FILES['id_image'] ?? []);
    $selfieImage = save_kyc_image($_FILES['selfie_image'] ?? []);

    $stmt = db()->prepare('
        INSERT INTO kyc_verifications
        (user_id, full_name, date_of_birth, nationality, region, address_line, city, barangay, id_type, id_number, id_image, selfie_image, status)
        VALUES
        (:user_id, :full_name, :date_of_birth, :nationality, :region, :address_line, :city, :barangay, :id_type, :id_number, :id_image, :selfie_image, :status)
    ');
    $stmt->execute([
        'user_id' => $user['id'],
        'full_name' => $fullName,
        'date_of_birth' => $dateOfBirth,
        'nationality' => $nationality,
        'region' => $region,
        'address_line' => $addressLine,
        'city' => $city,
        'barangay' => $barangay,
        'id_type' => $idType,
        'id_number' => $idNumber,
        'id_image' => $idImage,
        'selfie_image' => $selfieImage,
        'status' => 'pending',
    ]);

    log_audit((int) $user['id'], 'kyc_submitted', 'KYC application submitted');
    header('Location: ' . APP_URL . '/trader/profile.php?kyc=success');
} catch (Exception $e) {
    header('Location: ' . APP_URL . '/trader/profile.php?error=kyc_failed');
}
exit;
