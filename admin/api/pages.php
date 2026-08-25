<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/PageContentService.php';
require_once dirname(__DIR__, 2) . '/includes/MediaUploadService.php';

AdminPermissions::require(AdminPermissions::PERM_CONTENT);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $page = (string) ($_GET['page'] ?? 'all');
        if ($page === 'all') {
            jsonOk(['content' => PageContentService::get()]);
        }
        jsonOk(['page' => $page, 'content' => PageContentService::getPage($page)]);
    }

    if ($method === 'POST') {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'multipart/form-data') || !empty($_FILES)) {
            handleMultipartSave();
        }

        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $page = (string) ($body['page'] ?? '');
        $content = $body['content'] ?? null;
        if ($page === '' || !is_array($content)) {
            jsonError('Page slug and content are required.');
        }
        PageContentService::savePage($page, $content);
        jsonOk(['message' => ucfirst($page) . ' page saved.', 'content' => PageContentService::getPage($page)]);
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage(), 400);
}

function handleMultipartSave(): void
{
    $page = (string) ($_POST['page'] ?? '');
    $raw = (string) ($_POST['content'] ?? '');
    $content = json_decode($raw, true);

    if ($page === '' || !is_array($content)) {
        jsonError('Page slug and content are required.');
    }

    if ($page === 'about') {
        $content = applyAboutUploads($content);
    }

    PageContentService::savePage($page, $content);
    jsonOk(['message' => ucfirst($page) . ' page saved.', 'content' => PageContentService::getPage($page)]);
}

/**
 * @param array<string, mixed> $content
 * @return array<string, mixed>
 */
function applyAboutUploads(array $content): array
{
    $existing = PageContentService::getPage('about');

    if (!isset($content['narrative']) || !is_array($content['narrative'])) {
        $content['narrative'] = [];
    }
    if (!isset($content['values']) || !is_array($content['values'])) {
        $content['values'] = [];
    }
    if (!isset($content['team']) || !is_array($content['team'])) {
        $content['team'] = [];
    }
    if (!isset($content['team']['members']) || !is_array($content['team']['members'])) {
        $content['team']['members'] = [];
    }

    $currentMain = (string) ($existing['narrative']['mainImage'] ?? $content['narrative']['mainImage'] ?? '');
    $currentFloat = (string) ($existing['narrative']['floatImage'] ?? $content['narrative']['floatImage'] ?? '');
    $currentValues = (string) ($existing['values']['image'] ?? $content['values']['image'] ?? '');

    if (MediaUploadService::hasUpload($_FILES, 'narrative_mainImage')) {
        $content['narrative']['mainImage'] = MediaUploadService::storeNamedImage(
            'about',
            'narrative-main',
            $_FILES['narrative_mainImage'],
            $currentMain !== '' ? $currentMain : null
        );
    }

    if (MediaUploadService::hasUpload($_FILES, 'narrative_floatImage')) {
        $content['narrative']['floatImage'] = MediaUploadService::storeNamedImage(
            'about',
            'narrative-float',
            $_FILES['narrative_floatImage'],
            $currentFloat !== '' ? $currentFloat : null
        );
    }

    if (MediaUploadService::hasUpload($_FILES, 'values_image')) {
        $content['values']['image'] = MediaUploadService::storeNamedImage(
            'about',
            'values',
            $_FILES['values_image'],
            $currentValues !== '' ? $currentValues : null
        );
    }

    $existingMembers = is_array($existing['team']['members'] ?? null) ? $existing['team']['members'] : [];
    foreach ($content['team']['members'] as $i => $member) {
        if (!is_array($member)) {
            continue;
        }
        $key = 'team_image_' . $i;
        if (!MediaUploadService::hasUpload($_FILES, $key)) {
            continue;
        }
        $prev = (string) ($existingMembers[$i]['image'] ?? $member['image'] ?? '');
        $content['team']['members'][$i]['image'] = MediaUploadService::storeNamedImage(
            'about',
            'team-' . $i,
            $_FILES[$key],
            $prev !== '' ? $prev : null
        );
    }

    return $content;
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
