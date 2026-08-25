<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/FaqRepository.php';

AdminPermissions::require(AdminPermissions::PERM_FAQS);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        jsonOk(['faqs' => FaqRepository::getAll(), 'stats' => FaqRepository::getStats()]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid FAQ ID.');
            }
            FaqRepository::delete($id);
            jsonOk(['message' => 'FAQ deleted.', 'stats' => FaqRepository::getStats()]);
        }

        if ($action === 'toggle') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid FAQ ID.');
            }
            $faq = FaqRepository::getById($id);
            if ($faq === null) {
                jsonError('FAQ not found.', 404);
            }
            $next = !((bool) $faq['isActive']);
            FaqRepository::setActive($id, $next);
            jsonOk([
                'message' => $next ? 'FAQ published to the website.' : 'FAQ hidden from the website.',
                'faq'     => FaqRepository::getById($id),
                'stats'   => FaqRepository::getStats(),
            ]);
        }

        $id = (int) ($body['id'] ?? 0);
        if (trim((string) ($body['question'] ?? '')) === '' || trim((string) ($body['answer'] ?? '')) === '') {
            jsonError('Question and answer are required.');
        }

        if ($id > 0) {
            FaqRepository::update($id, $body);
            jsonOk([
                'message' => 'FAQ updated.',
                'faq'     => FaqRepository::getById($id),
                'stats'   => FaqRepository::getStats(),
            ]);
        }

        $newId = FaqRepository::create($body);
        jsonOk([
            'message' => 'FAQ created.',
            'faq'     => FaqRepository::getById($newId),
            'stats'   => FaqRepository::getStats(),
        ]);
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
