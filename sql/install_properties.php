<?php
/**
 * One-time installer: creates properties table and seeds sample listings.
 * Run from browser: http://localhost/BIVER_ROYAL_ESTATE/sql/install_properties.php
 * Delete this file after successful installation in production.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$sqlFile = __DIR__ . '/property_tables.sql';

if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "property_tables.sql not found.\n";
    exit(1);
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec(file_get_contents($sqlFile));

    echo "Properties schema installed successfully.\n";
    echo "DELETE this install_properties.php file before going live.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
