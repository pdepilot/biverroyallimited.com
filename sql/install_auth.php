<?php
/**
 * One-time installer: creates auth tables and seeds admin user.
 * Run from browser: http://localhost/BIVER_ROYAL_ESTATE/sql/install_auth.php
 * Delete this file after successful installation in production.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$sqlFile = __DIR__ . '/auth_tables.sql';

if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "auth_tables.sql not found.\n";
    exit(1);
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    echo "Authentication schema installed successfully.\n";
    echo "Admin email: admin@biverroyalty.com\n";
    echo "DELETE this install_auth.php file before going live.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
