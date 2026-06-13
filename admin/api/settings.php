<?php
/**
 * Admin API: profile, password, site settings, export.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__, 2) . '/includes/SiteSettingsService.php';
require_once dirname(__DIR__, 2) . '/includes/AdminDashboardService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$adminId = (int) ($_SESSION['admin_id'] ?? 0);

try {
    if ($method === 'GET') {
        $action = (string) ($_GET['action'] ?? 'all');
        $admin  = AuthSecurity::getCurrentAdmin();
        if (!$admin) {
            jsonError('Admin not found.', 404);
        }

        if ($action === 'export') {
            jsonOk(['export' => AdminDashboardService::exportAll()]);
        }

        $settings = SiteSettingsService::get();
        jsonOk([
            'profile' => [
                'id'        => (int) $admin['id'],
                'name'      => (string) ($admin['full_name'] ?? $_SESSION['admin_name'] ?? ''),
                'email'     => (string) ($admin['email'] ?? $_SESSION['admin_email'] ?? ''),
                'phone'     => (string) ($settings['adminPhone'] ?? ''),
                'createdAt' => (string) ($admin['created_at'] ?? ''),
            ],
            'site' => $settings,
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? '');

        if ($action === 'update_profile') {
            AuthSecurity::updateAdminProfile(
                $adminId,
                (string) ($body['name'] ?? ''),
                (string) ($body['email'] ?? '')
            );
            $settings = SiteSettingsService::get();
            $settings['adminPhone'] = trim((string) ($body['phone'] ?? ''));
            SiteSettingsService::save($settings);
            jsonOk(['message' => 'Profile updated.', 'profile' => AuthSecurity::getCurrentAdmin()]);
        }

        if ($action === 'change_password') {
            AuthSecurity::changeAdminPassword(
                $adminId,
                (string) ($body['currentPassword'] ?? ''),
                (string) ($body['newPassword'] ?? '')
            );
            jsonOk(['message' => 'Password changed successfully.']);
        }

        if ($action === 'save_site') {
            SiteSettingsService::save([
                'siteName'     => $body['siteName'] ?? '',
                'contactEmail' => $body['contactEmail'] ?? '',
                'contactPhone' => $body['contactPhone'] ?? '',
                'address'      => $body['address'] ?? '',
                'aboutText'    => $body['aboutText'] ?? '',
                'adminPhone'   => SiteSettingsService::get()['adminPhone'] ?? '',
            ]);
            jsonOk(['message' => 'Site settings saved.', 'site' => SiteSettingsService::get()]);
        }

        if ($action === 'deactivate_account') {
            if (($body['confirm'] ?? '') !== 'DELETE ADMIN') {
                jsonError('Confirmation phrase required.');
            }
            AuthSecurity::deactivateAdmin($adminId);
            jsonOk(['message' => 'Account deactivated.', 'redirect' => 'admin-login.php']);
        }

        jsonError('Unknown action.');
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
