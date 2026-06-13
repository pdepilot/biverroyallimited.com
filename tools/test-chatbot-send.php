<?php
declare(strict_types=1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/chatbot/chatbot-config.php';
require_once dirname(__DIR__) . '/chatbot/includes/ChatbotRepository.php';
require_once dirname(__DIR__) . '/chatbot/includes/ChatbotEngine.php';

$messages = [
    'Hi',
    'Hello',
    'Do you have houses for rent in Owerri?',
    'What is Certificate of Occupancy?',
    'asdfghjkl random gibberish xyz',
];

try {
    $repo = new ChatbotRepository();
    $user = $repo->findOrCreateVisitor(chatbotGenerateUuid(), ['name' => 'Test']);
    $session = $repo->createSession((int) $user['id'], ['page_url' => '/test']);
    $engine = new ChatbotEngine($repo);

    foreach ($messages as $msg) {
        echo "\n--- User: {$msg}\n";
        $result = $engine->processMessage($msg, ['session' => $session]);
        echo 'Source: ' . ($result['source'] ?? '?') . "\n";
        echo 'Escalate: ' . (($result['escalate'] ?? false) ? 'yes' : 'no') . "\n";
        echo 'Response: ' . mb_substr($result['response'] ?? '', 0, 200) . "\n";
    }
    echo "\nOK\n";
} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
