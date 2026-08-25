<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/PropertyRepository.php';

try {
    $pdo = getDatabaseConnection();
    $stats = $pdo->query(
        "SELECT COUNT(*) AS total,
                SUM(approval_status = 'approved') AS approved
         FROM properties"
    )->fetch();
    echo "DB stats: " . json_encode($stats) . PHP_EOL;

    $properties = PropertyRepository::getPublic(5);
    echo "getPublic count: " . count($properties) . PHP_EOL;
    if ($properties !== []) {
        echo "first id: " . ($properties[0]['id'] ?? '?') . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
