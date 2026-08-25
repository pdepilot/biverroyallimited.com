<?php
/**
 * Public API: FAQs for contact page and site.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once dirname(__DIR__) . '/includes/FaqRepository.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    FaqRepository::ensureSchema();
    $faqs = FaqRepository::getPublic();
    echo json_encode([
        'success'    => true,
        'faqs'       => $faqs,
        'count'      => count($faqs),
        'categories' => FaqRepository::CATEGORIES,
        'stats'      => FaqRepository::getStats(),
    ]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'FAQs unavailable.', 'faqs' => []]);
}
