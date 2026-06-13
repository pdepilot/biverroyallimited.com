<?php
/**
 * Public API: published service areas for homepage.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=120');

require_once dirname(__DIR__) . '/includes/ServiceAreaRepository.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    echo json_encode([
        'success' => true,
        'areas'   => ServiceAreaRepository::getAll(true),
        'section' => ServiceAreaRepository::getSection(),
        'count'   => count(ServiceAreaRepository::getAll(true)),
    ]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Service areas unavailable.',
        'areas'   => [],
        'section' => ServiceAreaRepository::sectionDefaults(),
    ]);
}
