<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$columns = [
    'bedrooms'  => 'TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER `location`',
    'bathrooms' => 'TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER `bedrooms`',
    'area'      => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER `bathrooms`',
];

try {
    $pdo = getDatabaseConnection();

    foreach ($columns as $name => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM properties LIKE " . $pdo->quote($name));
        if ($stmt->fetch()) {
            echo "Column {$name} already exists.\n";
            continue;
        }

        $pdo->exec("ALTER TABLE properties ADD COLUMN `{$name}` {$definition}");
        echo "Added column {$name}.\n";
    }

    echo "Migration complete.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Migration failed: ' . $e->getMessage() . "\n";
    exit(1);
}
