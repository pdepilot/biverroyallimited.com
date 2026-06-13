<?php
/**
 * Install support platform tables (conversations, messages, leads).
 * http://localhost/BIVER_ROYAL_ESTATE/sql/install_support_platform.php
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$sqlFile = __DIR__ . '/support_platform.sql';
if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "support_platform.sql not found.\n";
    exit(1);
}

try {
    $pdo = getDatabaseConnection();
    $pdo->exec(file_get_contents($sqlFile));
    echo "Support platform tables installed.\n";

    try {
        $col = $pdo->query("SHOW COLUMNS FROM properties LIKE 'listing_status'")->fetch();
        if (!$col) {
            $pdo->exec(
                "ALTER TABLE properties ADD COLUMN listing_status
                 ENUM('available','sold','rented','reserved') NOT NULL DEFAULT 'available'
                 AFTER approval_status"
            );
            echo "  Added properties.listing_status column.\n";
        }
    } catch (Throwable $e) {
        echo "  Note: listing_status column skipped — " . $e->getMessage() . "\n";
    }

    foreach (['support_conversations', 'support_messages', 'chat_leads'] as $table) {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        echo "  OK: {$table}\n";
    }

    echo "\nBackfill existing chat sessions…\n";
    require_once dirname(__DIR__) . '/includes/SupportPlatformRepository.php';
    $repo = new SupportPlatformRepository($pdo);
    $count = $repo->backfillConversations();
    echo "Synced {$count} conversation(s).\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Install failed: ' . $e->getMessage() . "\n";
    exit(1);
}
