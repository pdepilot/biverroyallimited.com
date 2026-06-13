<?php
/**
 * Admin API: newsletter subscribers management.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__, 2) . '/includes/EmailRepository.php';
require_once dirname(__DIR__, 2) . '/includes/AutomatedEmailService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function requireCsrf(array $body): void
{
    $token = (string) ($body['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!AuthSecurity::validateCsrfToken($token)) {
        jsonError('Invalid or expired security token. Refresh the page.', 403);
    }
}

try {
    EmailRepository::ensureTables();

    if ($method === 'GET') {
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $search = trim((string) ($_GET['search'] ?? ''));
        $list = EmailRepository::getSubscribers($status);

        if ($search !== '') {
            $q = strtolower($search);
            $list = array_values(array_filter($list, static function (array $row) use ($q): bool {
                return str_contains(strtolower((string) $row['email']), $q)
                    || str_contains(strtolower((string) ($row['name'] ?? '')), $q);
            }));
        }

        jsonOk([
            'subscribers' => $list,
            'stats'       => EmailRepository::getSubscriberStats(),
            'csrf_token'  => AuthSecurity::generateCsrfToken(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        requireCsrf($body);
        $action = (string) ($body['action'] ?? '');

        if ($action === 'add') {
            $email = trim((string) ($body['email'] ?? ''));
            $name = trim((string) ($body['name'] ?? ''));
            $id = EmailRepository::addSubscriber($email, $name !== '' ? $name : null, 'admin');
            AutomatedEmailService::onNewsletterSubscribed($email, $name !== '' ? $name : null);
            jsonOk(['message' => 'Subscriber added and welcome email sent.', 'id' => $id]);
        }

        if ($action === 'update_status') {
            $id = (int) ($body['id'] ?? 0);
            $status = (string) ($body['status'] ?? '');
            if ($id <= 0 || !EmailRepository::updateSubscriberStatus($id, $status)) {
                jsonError('Invalid status update.');
            }
            jsonOk(['message' => 'Subscriber updated.']);
        }

        jsonError('Unknown action.');
    }

    if ($method === 'DELETE') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_GET;
        requireCsrf($body);
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Invalid subscriber ID.');
        }
        EmailRepository::deleteSubscriber($id);
        jsonOk(['message' => 'Subscriber removed.']);
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
