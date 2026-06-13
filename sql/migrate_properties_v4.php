<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SHOW COLUMNS FROM properties LIKE 'gallery_urls'");
    if ($stmt->fetch()) {
        echo "Column gallery_urls already exists.\n";
        exit(0);
    }

    $pdo->exec('ALTER TABLE properties ADD COLUMN gallery_urls TEXT DEFAULT NULL AFTER video_url');
    echo "Added column gallery_urls.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Migration failed: ' . $e->getMessage() . "\n";
    exit(1);
}
