<?php
/**
 * Admin API: manage admin users, roles, and permissions.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/AdminUserRepository.php';

AdminPermissions::require(AdminPermissions::PERM_ADMINS);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$actorId = (int) ($_SESSION['admin_id'] ?? 0);

function requireCsrf(array $body): void
{
    $token = (string) ($body['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!AuthSecurity::validateCsrfToken($token)) {
        jsonError('Invalid or expired security token. Refresh the page.', 403);
    }
}

try {
    AdminUserRepository::ensureSchema();

    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            $user = AdminUserRepository::getById((int) $_GET['id']);
            if (!$user) {
                jsonError('Admin user not found.', 404);
            }
            jsonOk(['user' => $user]);
        }

        jsonOk([
            'users'        => AdminUserRepository::listAll(),
            'roles'        => AdminPermissions::roles(),
            'permissions'  => AdminPermissions::allPermissions(),
            'csrf_token'   => AuthSecurity::generateCsrfToken(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        requireCsrf($body);
        $action = (string) ($body['action'] ?? '');

        if ($action === 'create') {
            $id = AdminUserRepository::create([
                'email'                   => $body['email'] ?? '',
                'full_name'               => $body['full_name'] ?? $body['name'] ?? '',
                'password'                => $body['password'] ?? '',
                'role'                    => $body['role'] ?? 'viewer',
                'permissions'             => $body['permissions'] ?? [],
                'use_custom_permissions'  => !empty($body['use_custom_permissions']),
            ], $actorId);
            jsonOk(['message' => 'Admin user created.', 'id' => $id]);
        }

        if ($action === 'update') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid user ID.');
            }
            AdminUserRepository::update($id, [
                'email'                   => $body['email'] ?? '',
                'full_name'               => $body['full_name'] ?? $body['name'] ?? '',
                'password'                => $body['password'] ?? '',
                'role'                    => $body['role'] ?? '',
                'permissions'             => $body['permissions'] ?? [],
                'use_custom_permissions'  => !empty($body['use_custom_permissions']),
            ], $actorId);

            if ($id === $actorId) {
                AdminPermissions::loadSessionPermissions($actorId);
            }

            jsonOk(['message' => 'Admin user updated.']);
        }

        if ($action === 'suspend') {
            $id = (int) ($body['id'] ?? 0);
            AdminUserRepository::suspend($id, $actorId);
            jsonOk(['message' => 'Admin user suspended.']);
        }

        if ($action === 'reactivate') {
            $id = (int) ($body['id'] ?? 0);
            AdminUserRepository::reactivate($id, $actorId);
            jsonOk(['message' => 'Admin user reactivated.']);
        }

        jsonError('Unknown action.');
    }

    if ($method === 'DELETE') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_GET;
        requireCsrf($body);
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Invalid user ID.');
        }
        AdminUserRepository::delete($id, $actorId);
        jsonOk(['message' => 'Admin user removed.']);
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
