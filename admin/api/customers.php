<?php
/**
 * Admin API: manage customer (CRM) records.
 * Requires an active admin session with the customers permission.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/CustomerRepository.php';

AdminPermissions::require(AdminPermissions::PERM_CUSTOMERS);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            $customer = CustomerRepository::getById((int) $_GET['id']);
            if ($customer === null) {
                jsonError('Customer not found.', 404);
            }
            jsonOk(['customer' => $customer]);
        }

        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $type   = isset($_GET['type']) ? (string) $_GET['type'] : null;

        jsonOk([
            'customers' => CustomerRepository::getAll($search, $status, $type),
            'stats'     => CustomerRepository::getStats(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0 || !CustomerRepository::delete($id)) {
                jsonError('Customer not found.', 404);
            }
            jsonOk(['message' => 'Customer deleted.']);
        }

        $id = (int) ($body['id'] ?? 0);
        if (trim((string) ($body['name'] ?? '')) === '') {
            jsonError('Customer name is required.');
        }

        $email = trim((string) ($body['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Please enter a valid email address.');
        }

        if ($id > 0) {
            CustomerRepository::update($id, $body);
            jsonOk(['message' => 'Customer updated.', 'customer' => CustomerRepository::getById($id)]);
        }

        $newId = CustomerRepository::create($body);
        jsonOk(['message' => 'Customer added.', 'customer' => CustomerRepository::getById($newId)]);
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage() ?: 'Request failed.', 400);
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
