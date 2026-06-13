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
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Property database unavailable. Please try again later.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load properties.']);
}
