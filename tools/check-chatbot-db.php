<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = getDatabaseConnection();
    $tables = $pdo->query("SHOW TABLES LIKE 'chat%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "chat tables: " . (count($tables) ? implode(', ', $tables) : '(none)') . "\n";
    $pdo->query('SELECT 1 FROM chat_sessions LIMIT 1');
    echo "chat_sessions: OK\n";
    $pdo->query('SELECT 1 FROM users LIMIT 1');
    echo "users: OK\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
