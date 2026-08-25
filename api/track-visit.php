<?php
/**
 * Public endpoint: record a site pageview (analytics consent required on client).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/includes/SiteVisitRepository.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $body = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($body)) {
        $body = $_POST;
    }

    $ok = SiteVisitRepository::record([
        'visitorKey' => (string) ($body['visitorKey'] ?? $body['visitor_key'] ?? ''),
        'pagePath'   => (string) ($body['pagePath'] ?? $body['path'] ?? ''),
        'pageTitle'  => (string) ($body['pageTitle'] ?? $body['title'] ?? ''),
        'referrer'   => (string) ($body['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '')),
        'userAgent'  => (string) ($body['userAgent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')),
    ]);

    if (!$ok) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid visit payload.']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to record visit.']);
}
