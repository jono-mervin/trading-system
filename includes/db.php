<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if ((int) $e->getCode() !== 1049) {
            throw $e;
        }

        $bootstrapDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $bootstrap = new PDO($bootstrapDsn, DB_USER, DB_PASS, $options);
        $bootstrap->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    ensure_schema($pdo);
    return $pdo;
}

function ensure_schema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $hasUsers = (bool) $stmt->fetch();

    $schemaPath = __DIR__ . '/../sql/schema.sql';
    if (!is_file($schemaPath)) {
        return;
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false || trim($sql) === '') {
        return;
    }

    if (!$hasUsers) {
        // Bootstrap full schema for first-run local setup.
        $pdo->exec($sql);
        return;
    }

    // Lightweight migration path for existing local databases.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payment_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(120) NOT NULL UNIQUE,
            event_type VARCHAR(120) NOT NULL,
            source_id VARCHAR(120) NOT NULL,
            payload_json LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_payment_logs_source (source_id)
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bank_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            bank_name VARCHAR(100) NOT NULL,
            account_name VARCHAR(100) NOT NULL,
            account_number VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    $hasBankAccountId = (bool) $pdo->query("SHOW COLUMNS FROM withdrawals LIKE 'bank_account_id'")->fetch();
    if (!$hasBankAccountId) {
        $pdo->exec("ALTER TABLE withdrawals ADD COLUMN bank_account_id INT NULL AFTER destination_value");
    }
    $hasProfileImage = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetch();
    if (!$hasProfileImage) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER password");
    }

    $kycColumns = [
        'full_name' => "ALTER TABLE kyc_verifications ADD COLUMN full_name VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id",
        'date_of_birth' => "ALTER TABLE kyc_verifications ADD COLUMN date_of_birth DATE NULL AFTER full_name",
        'nationality' => "ALTER TABLE kyc_verifications ADD COLUMN nationality VARCHAR(80) NOT NULL DEFAULT '' AFTER date_of_birth",
        'address_line' => "ALTER TABLE kyc_verifications ADD COLUMN address_line TEXT NULL AFTER nationality",
        'city' => "ALTER TABLE kyc_verifications ADD COLUMN city VARCHAR(80) NOT NULL DEFAULT '' AFTER address_line",
        'province' => "ALTER TABLE kyc_verifications ADD COLUMN province VARCHAR(80) NOT NULL DEFAULT '' AFTER city",
        'postal_code' => "ALTER TABLE kyc_verifications ADD COLUMN postal_code VARCHAR(20) NOT NULL DEFAULT '' AFTER province",
        'contact_number' => "ALTER TABLE kyc_verifications ADD COLUMN contact_number VARCHAR(30) NOT NULL DEFAULT '' AFTER postal_code",
        'occupation' => "ALTER TABLE kyc_verifications ADD COLUMN occupation VARCHAR(120) NOT NULL DEFAULT '' AFTER contact_number",
        'source_of_funds' => "ALTER TABLE kyc_verifications ADD COLUMN source_of_funds VARCHAR(255) NOT NULL DEFAULT '' AFTER occupation",
        'region' => "ALTER TABLE kyc_verifications ADD COLUMN region VARCHAR(80) NOT NULL DEFAULT '' AFTER nationality",
        'barangay' => "ALTER TABLE kyc_verifications ADD COLUMN barangay VARCHAR(80) NOT NULL DEFAULT '' AFTER city",
    ];
    foreach ($kycColumns as $column => $ddl) {
        $exists = (bool) $pdo->query("SHOW COLUMNS FROM kyc_verifications LIKE '" . $column . "'")->fetch();
        if (!$exists) {
            $pdo->exec($ddl);
        }
    }
    $dobNullable = $pdo->query("SHOW COLUMNS FROM kyc_verifications LIKE 'date_of_birth'")->fetch();
    if (is_array($dobNullable) && ($dobNullable['Null'] ?? 'YES') === 'YES') {
        $pdo->exec("UPDATE kyc_verifications SET date_of_birth = '1970-01-01' WHERE date_of_birth IS NULL");
        $pdo->exec("ALTER TABLE kyc_verifications MODIFY date_of_birth DATE NOT NULL");
    }
}
