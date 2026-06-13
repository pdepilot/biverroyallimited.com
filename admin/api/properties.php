<?php
/**
 * Admin API: manage property listings (list, create, update, delete).
 * Requires active PHP admin session.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/PropertyRepository.php';
require_once dirname(__DIR__, 2) . '/includes/AutomatedEmailService.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            $property = PropertyRepository::getById((int) $_GET['id']);
            if ($property === null) {
                jsonError('Property not found.', 404);
            }
            jsonOk(['property' => $property]);
        }

        $limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
        $type   = isset($_GET['type']) ? (string) $_GET['type'] : null;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;

        jsonOk(['properties' => PropertyRepository::getAll($limit, $type, $search)]);
    }

    if ($method === 'POST') {
        $body = parseJsonBody();
        $title = trim((string) ($body['title'] ?? ''));
        $location = trim((string) ($body['location'] ?? ''));

        if ($title === '' || $location === '') {
            jsonError('Title and location are required.');
        }

        $property = PropertyRepository::create(normalizePayload($body, $title, $location));
        jsonOk(['property' => $property, 'message' => 'Property created.']);
    }

    if ($method === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Invalid property ID.');
        }

        $body = parseJsonBody();
        $updates = [];

        if (isset($body['title'])) {
            $updates['title'] = trim((string) $body['title']);
        }
        if (isset($body['location'])) {
            $updates['location'] = trim((string) $body['location']);
        }
        if (isset($body['price'])) {
            $updates['price'] = (int) $body['price'];
        }
        if (isset($body['type'])) {
            $updates['type'] = (string) $body['type'];
        }
        if (array_key_exists('imageUrl', $body)) {
            $updates['imageUrl'] = trim((string) $body['imageUrl']) ?: null;
        }
        if (array_key_exists('description', $body)) {
            $updates['description'] = trim((string) $body['description']) ?: null;
        }
        $oldProperty = PropertyRepository::getById($id);
        $oldStatus = (string) ($oldProperty['approvalStatus'] ?? '');

        if (isset($body['approvalStatus'])) {
            $updates['approvalStatus'] = (string) $body['approvalStatus'];
        }
        if (isset($body['bedrooms'])) {
            $updates['bedrooms'] = (int) $body['bedrooms'];
        }
        if (isset($body['bathrooms'])) {
            $updates['bathrooms'] = (int) $body['bathrooms'];
        }
        if (isset($body['area'])) {
            $updates['area'] = (int) $body['area'];
        }

        $property = PropertyRepository::update($id, $updates);
        if ($property === null) {
            jsonError('Property not found.', 404);
        }

        if (isset($updates['approvalStatus']) && $oldProperty !== null) {
            $newStatus = (string) $updates['approvalStatus'];
            if ($newStatus !== $oldStatus) {
                AutomatedEmailService::onPropertyStatusChange(
                    $property,
                    $oldStatus,
                    $newStatus,
                    (int) ($_SESSION['admin_id'] ?? 0),
                    (string) ($property['adminNotes'] ?? $body['adminNotes'] ?? '')
                );
            }
        }

        jsonOk(['property' => $property, 'message' => 'Property updated.']);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0 || !PropertyRepository::delete($id)) {
            jsonError('Property not found.', 404);
        }

        jsonOk(['message' => 'Property deleted.']);
    }

    jsonError('Method not allowed.', 405);
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'properties') || str_contains($e->getMessage(), 'Unknown column')) {
        jsonError('Properties table needs migration. Run sql/install_properties.php or sql/property_migrate_v2.sql.', 503);
    }
    jsonError('Database error.', 500);
} catch (Throwable $e) {
    jsonError($e->getMessage() ?: 'Request failed.', 400);
}

/**
 * @return array<string, mixed>
 */
function parseJsonBody(): array
{
    $body = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($body) ? $body : [];
}

/**
 * @param array<string, mixed> $body
 * @return array<string, mixed>
 */
function normalizePayload(array $body, string $title, string $location): array
{
    return [
        'title'          => $title,
        'location'       => $location,
        'price'          => (int) ($body['price'] ?? 0),
        'type'           => (string) ($body['type'] ?? 'sale'),
        'imageUrl'       => trim((string) ($body['imageUrl'] ?? '')) ?: null,
        'description'    => trim((string) ($body['description'] ?? '')) ?: null,
        'approvalStatus' => (string) ($body['approvalStatus'] ?? 'approved'),
        'bedrooms'       => (int) ($body['bedrooms'] ?? 2),
        'bathrooms'      => (int) ($body['bathrooms'] ?? 2),
        'area'           => (int) ($body['area'] ?? 0),
    ];
}

function jsonOk(array $data): void
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
