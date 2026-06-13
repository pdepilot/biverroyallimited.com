<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$columns = [
    'source'             => "ENUM('admin','public') NOT NULL DEFAULT 'admin' AFTER `approval_status`",
    'owner_name'         => 'VARCHAR(120) DEFAULT NULL AFTER `source`',
    'owner_email'        => 'VARCHAR(255) DEFAULT NULL AFTER `owner_name`',
    'owner_phone'        => 'VARCHAR(30) DEFAULT NULL AFTER `owner_email`',
    'contact_method'     => 'VARCHAR(20) DEFAULT NULL AFTER `owner_phone`',
    'listing_purpose'    => 'VARCHAR(20) DEFAULT NULL AFTER `contact_method`',
    'property_category'  => 'VARCHAR(50) DEFAULT NULL AFTER `listing_purpose`',
    'property_address'   => 'TEXT DEFAULT NULL AFTER `property_category`',
    'property_features'  => 'TEXT DEFAULT NULL AFTER `property_address`',
    'ownership_status'   => 'VARCHAR(30) DEFAULT NULL AFTER `property_features`',
    'property_size'      => 'VARCHAR(80) DEFAULT NULL AFTER `ownership_status`',
    'video_url'          => 'VARCHAR(512) DEFAULT NULL AFTER `image_url`',
    'admin_notes'        => 'TEXT DEFAULT NULL AFTER `description`',
];

try {
    $pdo = getDatabaseConnection();

    foreach ($columns as $name => $definition) {
        $stmt = $pdo->query('SHOW COLUMNS FROM properties LIKE ' . $pdo->quote($name));
        if ($stmt->fetch()) {
            echo "Column {$name} already exists.\n";
            continue;
        }
        $pdo->exec("ALTER TABLE properties ADD COLUMN `{$name}` {$definition}");
        echo "Added column {$name}.\n";
    }

    echo "Migration v3 complete.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Migration failed: ' . $e->getMessage() . "\n";
    exit(1);
}
