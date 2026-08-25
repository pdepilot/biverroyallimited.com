<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/BlogRepository.php';
require_once dirname(__DIR__, 2) . '/includes/MediaUploadService.php';

AdminPermissions::require(AdminPermissions::PERM_BLOG);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    BlogRepository::ensureSchema();

    if ($method === 'GET') {
        jsonOk([
            'posts'      => BlogRepository::getAll(),
            'stats'      => BlogRepository::getStats(),
            'categories' => BlogRepository::CATEGORIES,
        ]);
    }

    if ($method === 'POST') {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'multipart/form-data') || !empty($_FILES)) {
            handleBlogForm();
        }

        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid post ID.');
            }
            $existing = BlogRepository::getById($id);
            BlogRepository::delete($id);
            if ($existing !== null) {
                MediaUploadService::delete($existing['coverImage'] ?? null);
            }
            jsonOk(['message' => 'Post deleted.', 'stats' => BlogRepository::getStats()]);
        }

        if ($action === 'toggle') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid post ID.');
            }
            $post = BlogRepository::getById($id);
            if ($post === null) {
                jsonError('Post not found.', 404);
            }
            $next = !((bool) $post['isPublished']);
            BlogRepository::setPublished($id, $next);
            jsonOk([
                'message' => $next ? 'Post published.' : 'Post unpublished.',
                'post'    => BlogRepository::getById($id),
                'stats'   => BlogRepository::getStats(),
            ]);
        }

        $id = (int) ($body['id'] ?? 0);
        if ($id > 0) {
            BlogRepository::update($id, $body);
            jsonOk([
                'message' => 'Post updated.',
                'post'    => BlogRepository::getById($id),
                'stats'   => BlogRepository::getStats(),
            ]);
        }

        $newId = BlogRepository::create($body);
        jsonOk([
            'message' => 'Post created.',
            'post'    => BlogRepository::getById($newId),
            'stats'   => BlogRepository::getStats(),
        ]);
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage(), 400);
}

function handleBlogForm(): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $payload = [
        'title'       => (string) ($_POST['title'] ?? ''),
        'slug'        => (string) ($_POST['slug'] ?? ''),
        'excerpt'     => (string) ($_POST['excerpt'] ?? ''),
        'content'     => (string) ($_POST['content'] ?? ''),
        'category'    => (string) ($_POST['category'] ?? 'market-insights'),
        'authorName'  => (string) ($_POST['authorName'] ?? 'Biver Royalty Homes'),
        'isPublished' => (string) ($_POST['isPublished'] ?? '0') === '1',
    ];

    $hasCover = MediaUploadService::hasUpload($_FILES, 'coverImage');

    if ($id > 0) {
        $existing = BlogRepository::getById($id);
        if ($existing === null) {
            jsonError('Post not found.', 404);
        }
        $cover = (string) ($existing['coverImage'] ?? '');
        if ($hasCover) {
            $cover = MediaUploadService::storeNamedImage(
                'blog',
                'post-' . $id,
                $_FILES['coverImage'],
                $cover !== '' ? $cover : null
            );
        }
        $payload['coverImage'] = $cover;
        BlogRepository::update($id, $payload);
        jsonOk([
            'message' => 'Post updated.',
            'post'    => BlogRepository::getById($id),
            'stats'   => BlogRepository::getStats(),
        ]);
    }

    $payload['coverImage'] = '';
    $newId = BlogRepository::create($payload);
    if ($hasCover) {
        $cover = MediaUploadService::storeNamedImage('blog', 'post-' . $newId, $_FILES['coverImage']);
        BlogRepository::update($newId, ['coverImage' => $cover] + $payload);
    }

    jsonOk([
        'message' => 'Post created.',
        'post'    => BlogRepository::getById($newId),
        'stats'   => BlogRepository::getStats(),
    ]);
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
