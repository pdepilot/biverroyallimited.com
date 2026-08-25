<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once dirname(__DIR__) . '/includes/BlogRepository.php';

try {
    BlogRepository::ensureSchema();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        if ($slug !== '') {
            $post = BlogRepository::getBySlug($slug, true);
            if ($post === null) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post not found.', 'post' => null]);
                exit;
            }
            echo json_encode(['success' => true, 'post' => $post]);
            exit;
        }

        $category = (string) ($_GET['category'] ?? '');
        $search = (string) ($_GET['q'] ?? $_GET['search'] ?? '');
        $posts = BlogRepository::getPublic($category !== '' ? $category : null, $search !== '' ? $search : null);
        echo json_encode([
            'success'    => true,
            'posts'      => $posts,
            'count'      => count($posts),
            'categories' => BlogRepository::CATEGORIES,
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Blog unavailable.', 'posts' => []]);
}
