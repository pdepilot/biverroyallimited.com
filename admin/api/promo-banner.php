<?php
/**
 * Admin API: homepage promotional banner settings & flier uploads.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/BannerService.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        jsonOk(BannerService::adminState());
    }

    if ($method === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save') {
            $saved = BannerService::savePromoConfig([
                'enabled'     => $_POST['enabled'] ?? '0',
                'altText'     => $_POST['altText'] ?? '',
                'linkPage'    => $_POST['linkPage'] ?? 'property',
                'headline'    => $_POST['headline'] ?? '',
                'subheadline' => $_POST['subheadline'] ?? '',
                'ctaLabel'    => $_POST['ctaLabel'] ?? '',
                'eyebrow'     => $_POST['eyebrow'] ?? '',
                'badgeText'   => $_POST['badgeText'] ?? '',
                'showBadge'   => $_POST['showBadge'] ?? '0',
            ]);

            if (!$saved) {
                jsonError('Could not save banner settings.', 500);
            }

            jsonOk([
                'message' => 'Banner settings saved.',
                'state'   => BannerService::adminState(),
            ]);
        }

        if ($action === 'upload') {
            $slot = (string) ($_POST['slot'] ?? '');
            $field = $slot === 'mobile' ? 'flierMobile' : 'flierDesktop';

            if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
                jsonError('No file uploaded.');
            }

            $path = BannerService::uploadPromoFlier($slot === 'mobile' ? 'mobile' : 'desktop', $_FILES[$field]);

            jsonOk([
                'message' => ucfirst($slot ?: 'desktop') . ' flier uploaded.',
                'path'    => $path,
                'state'   => BannerService::adminState(),
            ]);
        }

        if ($action === 'remove') {
            $slot = (string) ($_POST['slot'] ?? '');
            BannerService::removePromoFlier($slot === 'mobile' ? 'mobile' : 'desktop');

            jsonOk([
                'message' => 'Flier removed.',
                'state'   => BannerService::adminState(),
            ]);
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
