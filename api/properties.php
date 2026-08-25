<?php
/**
 * Public API: read approved property listings for property.php and property-detail.php.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=60');

require_once dirname(__DIR__) . '/includes/PropertyRepository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    if (!empty($_GET['id'])) {
        $property = PropertyRepository::getPublicById((int) $_GET['id']);
        if ($property === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Property not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'property' => $property]);
        exit;
    }

    $limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $type   = isset($_GET['type']) ? (string) $_GET['type'] : null;
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
    $sort   = isset($_GET['sort']) ? (string) $_GET['sort'] : 'newest';

    $properties = PropertyRepository::getPublic($limit, $type, $search, $sort);

    echo json_encode([
        'success'    => true,
        'properties' => $properties,
        'count'      => count($properties),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $e) {
    error_log('Properties API DB error: ' . $e->getMessage());
    http_response_code(503);
    $code = (string) $e->getCode();
    $hint = 'Property database unavailable. Check config/database.local.php on the server (Hostinger MySQL name, user, and password).';
    if (str_contains($e->getMessage(), "doesn't exist") || $code === '42S02') {
        $hint = 'Property tables are missing. Import the SQL install scripts on Hostinger.';
    }
    echo json_encode([
        'success' => false,
        'message' => $hint,
    ]);
} catch (Throwable $e) {
    error_log('Properties API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load properties.']);
}
