<?php
/**
 * Public API: newsletter subscription with welcome email and country detection.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/includes/EmailRepository.php';
require_once dirname(__DIR__) . '/includes/AutomatedEmailService.php';
require_once dirname(__DIR__) . '/includes/GeoIpService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$email = strtolower(trim((string) ($payload['email'] ?? '')));
$name = trim((string) ($payload['name'] ?? ''));
$timezone = EmailRepository::normalizeTimezone((string) ($payload['timezone'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (mb_strlen($name) > 120) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Name is too long.']);
    exit;
}

try {
    $geo = GeoIpService::lookup();
    EmailRepository::addSubscriber(
        $email,
        $name !== '' ? $name : null,
        'website',
        $geo['country_code'] ?? null,
        $geo['country_name'] ?? null,
        $geo['ip'] ?? null,
        $timezone
    );
    AutomatedEmailService::onNewsletterSubscribed($email, $name !== '' ? $name : null);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for subscribing to our newsletter!',
        'country' => $geo['country_name'] ?? null,
        'subscribed_at' => date('c'),
    ]);
} catch (Throwable $e) {
    error_log('Newsletter subscribe error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to subscribe right now. Please try again.']);
}
