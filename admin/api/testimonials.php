<?php
/**
 * Admin API: manage testimonials.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/TestimonialRepository.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        jsonOk([
            'testimonials' => TestimonialRepository::getAll(),
            'stats'        => TestimonialRepository::getStats(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid testimonial ID.');
            }
            TestimonialRepository::delete($id);
            jsonOk(['message' => 'Testimonial deleted.']);
        }

        $id = (int) ($body['id'] ?? 0);
        $payload = [
            'name'         => $body['name'] ?? '',
            'message'      => $body['message'] ?? '',
            'rating'       => $body['rating'] ?? 5,
            'initials'     => $body['initials'] ?? '',
            'roleLabel'    => $body['roleLabel'] ?? 'Happy Client',
            'sortOrder'    => $body['sortOrder'] ?? 0,
            'isPublished'  => $body['isPublished'] ?? '1',
        ];

        if (trim((string) ($body['name'] ?? '')) === '' || trim((string) ($body['message'] ?? '')) === '') {
            jsonError('Name and message are required.');
        }

        if ($id > 0) {
            TestimonialRepository::update($id, $payload);
            jsonOk(['message' => 'Testimonial updated.', 'testimonial' => TestimonialRepository::getById($id)]);
        }

        $newId = TestimonialRepository::create($payload);
        jsonOk(['message' => 'Testimonial created.', 'testimonial' => TestimonialRepository::getById($newId)]);
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage(), 400);
}

/** @param array<string, mixed> $data */
function jsonOk(array $data): void
{
    echo json_encode(['success' => true] + $data);
    exit;
}

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
