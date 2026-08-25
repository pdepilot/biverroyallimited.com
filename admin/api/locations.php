<?php
/**
 * Admin API: manage homepage service areas.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/ServiceAreaRepository.php';
require_once dirname(__DIR__, 2) . '/includes/MediaUploadService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        jsonOk([
            'areas'   => ServiceAreaRepository::getAll(),
            'section' => ServiceAreaRepository::getSection(),
            'stats'   => ServiceAreaRepository::getStats(),
        ]);
    }

    if ($method === 'POST') {
        // Area cards are saved as multipart form data (image upload).
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'multipart/form-data') || !empty($_FILES)) {
            handleAreaForm();
        }

        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');

        if ($action === 'save_section') {
            ServiceAreaRepository::saveSection([
                'intro'    => $body['intro'] ?? '',
                'ctaText'  => $body['ctaText'] ?? '',
                'ctaLink'  => $body['ctaLink'] ?? 'contact.php',
                'ctaLabel' => $body['ctaLabel'] ?? '',
            ]);
            jsonOk(['message' => 'Section text saved.', 'section' => ServiceAreaRepository::getSection()]);
        }

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid area ID.');
            }
            $existing = ServiceAreaRepository::getById($id);
            ServiceAreaRepository::delete($id);
            if ($existing !== null) {
                MediaUploadService::delete($existing['storedImage'] ?? null);
            }
            jsonOk(['message' => 'Area deleted.']);
        }

        $id = (int) ($body['id'] ?? 0);
        $payload = [
            'title'       => $body['title'] ?? '',
            'tag'         => $body['tag'] ?? '',
            'imageUrl'    => $body['imageUrl'] ?? '',
            'description' => $body['description'] ?? '',
            'meta1Icon'   => $body['meta1Icon'] ?? 'home-outline',
            'meta1Text'   => $body['meta1Text'] ?? '',
            'meta2Icon'   => $body['meta2Icon'] ?? 'star-outline',
            'meta2Text'   => $body['meta2Text'] ?? '',
            'linkUrl'     => $body['linkUrl'] ?? 'property.php',
            'sortOrder'   => $body['sortOrder'] ?? 0,
            'isPublished' => $body['isPublished'] ?? '1',
        ];

        if (trim((string) $payload['title']) === '' || trim((string) $payload['description']) === '') {
            jsonError('Title and description are required.');
        }
        if (trim((string) $payload['imageUrl']) === '') {
            jsonError('Image URL is required.');
        }

        if ($id > 0) {
            ServiceAreaRepository::update($id, $payload);
            jsonOk(['message' => 'Area updated.', 'area' => ServiceAreaRepository::getById($id)]);
        }

        $newId = ServiceAreaRepository::create($payload);
        jsonOk(['message' => 'Area created.', 'area' => ServiceAreaRepository::getById($newId)]);
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage(), 400);
}

/**
 * Create/update a service area submitted as multipart form data (image upload).
 */
function handleAreaForm(): void
{
    $id = (int) ($_POST['id'] ?? 0);

    $payload = [
        'title'       => (string) ($_POST['title'] ?? ''),
        'tag'         => (string) ($_POST['tag'] ?? ''),
        'description' => (string) ($_POST['description'] ?? ''),
        'meta1Icon'   => (string) ($_POST['meta1Icon'] ?? 'home-outline'),
        'meta1Text'   => (string) ($_POST['meta1Text'] ?? ''),
        'meta2Icon'   => (string) ($_POST['meta2Icon'] ?? 'star-outline'),
        'meta2Text'   => (string) ($_POST['meta2Text'] ?? ''),
        'linkUrl'     => (string) ($_POST['linkUrl'] ?? 'property.php'),
        'sortOrder'   => (int) ($_POST['sortOrder'] ?? 0),
        'isPublished' => (string) ($_POST['isPublished'] ?? '1'),
    ];

    if (trim((string) $payload['title']) === '' || trim((string) $payload['description']) === '') {
        jsonError('Title and description are required.');
    }

    $hasNewImage = MediaUploadService::hasUpload($_FILES, 'areaImage');

    if ($id > 0) {
        $existing = ServiceAreaRepository::getById($id);
        if ($existing === null) {
            jsonError('Service area not found.', 404);
        }

        $imagePath = (string) ($existing['storedImage'] ?? '');
        if ($hasNewImage) {
            $imagePath = MediaUploadService::storeImage('areas', $id, $_FILES['areaImage'], $existing['storedImage'] ?? null);
        }
        if ($imagePath === '') {
            jsonError('Please upload an image for this area.');
        }

        $payload['imageUrl'] = $imagePath;
        ServiceAreaRepository::update($id, $payload);
        jsonOk(['message' => 'Area updated.', 'area' => ServiceAreaRepository::getById($id)]);
    }

    if (!$hasNewImage) {
        jsonError('Please upload an image for this area.');
    }

    // Create the record first to obtain an ID, then attach the uploaded image.
    $payload['imageUrl'] = '';
    $newId = ServiceAreaRepository::create($payload);
    $imagePath = MediaUploadService::storeImage('areas', $newId, $_FILES['areaImage']);
    $payload['imageUrl'] = $imagePath;
    ServiceAreaRepository::update($newId, $payload);

    jsonOk(['message' => 'Area created.', 'area' => ServiceAreaRepository::getById($newId)]);
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
