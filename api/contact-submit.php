<?php
/**
 * Public API: receive contact form submissions from contact.php.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/includes/ContactRepository.php';
require_once dirname(__DIR__) . '/includes/AutomatedEmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$name    = trim((string) ($payload['name'] ?? $payload['fullName'] ?? ''));
$email   = trim((string) ($payload['email'] ?? ''));
$phone   = trim((string) ($payload['phone'] ?? ''));
$type    = trim((string) ($payload['inquiryType'] ?? $payload['inquiry_type'] ?? 'general'));
$message = trim((string) ($payload['message'] ?? ''));

$allowedTypes = ['general', 'buying', 'renting', 'selling', 'partnership'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'general';
}

if ($name === '' || $email === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (mb_strlen($name) > 120 || mb_strlen($message) > 5000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Input exceeds allowed length.']);
    exit;
}

try {
    $id = ContactRepository::createInquiry([
        'full_name'    => $name,
        'email'        => $email,
        'phone'        => $phone !== '' ? $phone : null,
        'inquiry_type' => $type,
        'message'      => $message,
        'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'   => isset($_SERVER['HTTP_USER_AGENT'])
            ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 512)
            : null,
    ]);

    try {
        AutomatedEmailService::onContactSubmitted([
            'id'            => $id,
            'full_name'     => $name,
            'email'         => $email,
            'phone'         => $phone !== '' ? $phone : 'Not provided',
            'inquiry_type'  => $type,
            'message'       => $message,
        ]);
    } catch (Throwable $mailEx) {
        error_log('Contact auto-email failed: ' . $mailEx->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been received. We will get back to you within 24 hours.',
        'id'      => $id,
    ]);
} catch (Throwable $e) {
    error_log('Contact submit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to send your message right now. Please try again or call us directly.',
    ]);
}
