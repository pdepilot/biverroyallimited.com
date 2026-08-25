<?php
/**
 * Admin API: lightweight notification snapshot for live alerts.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/ContactRepository.php';
require_once dirname(__DIR__, 2) . '/includes/PropertyRepository.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $contacts = ContactRepository::getAlertSnapshot();
    $subs = PropertyRepository::getPublicSubmissionStats();

    echo json_encode([
        'success' => true,
        'contacts' => $contacts,
        'submissions' => [
            'pending' => (int) ($subs['pending'] ?? 0),
        ],
        'serverTime' => date('c'),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
