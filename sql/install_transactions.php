<?php
/**
 * One-time installer: transactions table (receipts & certificates).
 * Run: http://localhost/BIVER_ROYAL_ESTATE/sql/install_transactions.php
 * (The table is also created automatically on first use.)
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$sqlFile = __DIR__ . '/transaction_tables.sql';

if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "transaction_tables.sql not found.\n";
    exit(1);
}

try {
    $pdo = getDatabaseConnection();
    $pdo->exec((string) file_get_contents($sqlFile));
    echo "Transactions table installed successfully.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
