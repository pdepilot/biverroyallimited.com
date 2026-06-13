<?php
/**
 * Install email center tables (newsletter, templates, logs, queue).
 * Run once: php sql/install_email.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$sqlFile = __DIR__ . '/email_tables.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "Missing {$sqlFile}\n");
    exit(1);
}

$pdo = getDatabaseConnection();
$raw = file_get_contents($sqlFile);
$statements = array_filter(
    array_map('trim', preg_split('/;\s*\n/', $raw) ?: []),
    static fn (string $s): bool => $s !== '' && !preg_match('/^USE\s/i', $s)
);

foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Email tables installed successfully.\n";
