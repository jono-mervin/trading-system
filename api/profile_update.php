<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/config.php';

$user = require_role('trader');
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    exit('Invalid CSRF token.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
if ($name === '' || $email === '') {
    set_flash('error', 'Name and email are required.');
    header('Location: ' . APP_URL . '/trader/profile.php');
    exit;
}

$profileImagePath = null;
if (isset($_FILES['profile_image']) && (int) ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ((int) $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Profile image upload failed.');
        header('Location: ' . APP_URL . '/trader/profile.php');
        exit;
    }
    $mime = mime_content_type($_FILES['profile_image']['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        set_flash('error', 'Invalid profile image type.');
        header('Location: ' . APP_URL . '/trader/profile.php');
        exit;
    }
    $dir = __DIR__ . '/../uploads/profile';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $ext = pathinfo((string) $_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
    $target = $dir . '/' . $filename;
    move_uploaded_file($_FILES['profile_image']['tmp_name'], $target);
    $profileImagePath = 'uploads/profile/' . $filename;
}

if ($profileImagePath) {
    $stmt = db()->prepare('UPDATE users SET name = :name, email = :email, profile_image = :profile_image WHERE id = :id');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'profile_image' => $profileImagePath,
        'id' => $user['id'],
    ]);
    $_SESSION[SESSION_KEY_USER]['profile_image'] = $profileImagePath;
} else {
    $stmt = db()->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'id' => $user['id'],
    ]);
}

$_SESSION[SESSION_KEY_USER]['name'] = $name;
$_SESSION[SESSION_KEY_USER]['email'] = $email;
log_audit((int) $user['id'], 'profile_updated', 'Trader profile updated');
set_flash('success', 'Profile updated successfully.');
header('Location: ' . APP_URL . '/trader/profile.php');
