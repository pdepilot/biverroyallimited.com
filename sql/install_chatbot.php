<?php
/**
 * One-time installer: chatbot tables + seed data.
 * Run: http://localhost/BIVER_ROYAL_ESTATE/sql/install_chatbot.php
 * Repair: http://localhost/BIVER_ROYAL_ESTATE/sql/install_chatbot.php?repair=1
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$sqlFile = dirname(__DIR__) . '/chatbot/chatbot-database.sql';

if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "chatbot-database.sql not found.\n";
    exit(1);
}

/** Tables created by chatbot schema (drop order respects foreign keys). */
const CHATBOT_TABLES = [
    'chat_messages',
    'support_tickets',
    'visitor_logs',
    'notification_queue',
    'chat_sessions',
    'chatbot_responses',
    'chatbot_faqs',
    'chatbot_knowledgebase',
    'chatbot_intents',
    'agents',
    'users',
];

/**
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';

    foreach (preg_split('/\R/', $sql) as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }

        $buffer .= $line . "\n";

        if (str_ends_with(rtrim($line), ';')) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
}

function chatbotMysqlDataDir(): ?string
{
    $candidates = [
        'C:/xampp/mysql/data/' . DB_NAME,
        'C:\\xampp\\mysql\\data\\' . DB_NAME,
        dirname(__DIR__) . '/../mysql/data/' . DB_NAME,
    ];

    foreach ($candidates as $dir) {
        if (is_dir($dir)) {
            return str_replace('\\', '/', $dir);
        }
    }

    return null;
}

function removeOrphanTableFiles(string $table): void
{
    $dataDir = chatbotMysqlDataDir();
    if ($dataDir === null) {
        return;
    }

    foreach ([$table . '.ibd', $table . '.cfg', $table . '.frm'] as $file) {
        $path = $dataDir . '/' . $file;
        if (is_file($path) && @unlink($path)) {
            echo "Removed orphan file: {$file}\n";
        }
    }
}

function dropChatbotTables(PDO $pdo): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (CHATBOT_TABLES as $table) {
        try {
            $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        } catch (PDOException $e) {
            echo "Drop note ({$table}): " . $e->getMessage() . "\n";
        }
        removeOrphanTableFiles($table);
        echo "Dropped table (if existed): {$table}\n";
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $repair = isset($_GET['repair']) || isset($_GET['fresh'])
        || (PHP_SAPI === 'cli' && in_array('repair', $argv ?? [], true));

    if ($repair) {
        echo "Repair mode: removing broken chatbot tables…\n";
        dropChatbotTables($pdo);
    } else {
        try {
            $pdo->query('SELECT 1 FROM chat_sessions LIMIT 1');
            echo "Chatbot tables already present. Add ?repair=1 to rebuild if chat is broken.\n";
            echo "Health: OK\n";
            exit(0);
        } catch (PDOException) {
            echo "Tables missing or broken — installing…\n";
        }
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new RuntimeException('Could not read SQL file.');
    }

    $statements = splitSqlStatements($sql);
    $executed = 0;

    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $executed++;
    }

    $intentCount = (int) $pdo->query('SELECT COUNT(*) FROM chatbot_intents')->fetchColumn();
    $agentCount = (int) $pdo->query('SELECT COUNT(*) FROM agents')->fetchColumn();

    echo "\nChatbot installation complete.\n";
    echo "Statements executed: {$executed}\n";
    echo "Intents: {$intentCount}\n";
    echo "Agents: {$agentCount}\n";
    echo "\nNext steps:\n";
    echo "1. Refresh your site and test the chat widget\n";
    echo "2. Admin: /chatbot/chatbot-admin.php\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Installation failed: ' . $e->getMessage() . "\n";
    echo "\nTry repair mode:\n";
    echo "http://localhost/BIVER_ROYAL_ESTATE/sql/install_chatbot.php?repair=1\n";
    echo "\nTip: Start MySQL in XAMPP Control Panel, then reload.\n";
    exit(1);
}
