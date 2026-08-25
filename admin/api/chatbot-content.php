<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/ChatbotContentRepository.php';

AdminPermissions::require(AdminPermissions::PERM_CHATBOT);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $type = (string) ($_GET['type'] ?? 'all');
        $intentId = isset($_GET['intentId']) ? (int) $_GET['intentId'] : null;

        if ($type === 'intents') {
            jsonOk(['intents' => ChatbotContentRepository::getIntents()]);
        }
        if ($type === 'responses') {
            jsonOk(['responses' => ChatbotContentRepository::getResponses($intentId)]);
        }
        if ($type === 'knowledge') {
            jsonOk(['knowledge' => ChatbotContentRepository::getKnowledge()]);
        }

        jsonOk([
            'intents'   => ChatbotContentRepository::getIntents(),
            'responses' => ChatbotContentRepository::getResponses(),
            'knowledge' => ChatbotContentRepository::getKnowledge(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');
        $type = (string) ($body['type'] ?? 'intent');

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid ID.');
            }
            if ($type === 'response') {
                ChatbotContentRepository::deleteResponse($id);
            } elseif ($type === 'knowledge') {
                ChatbotContentRepository::deleteKnowledge($id);
            } else {
                ChatbotContentRepository::deleteIntent($id);
            }
            jsonOk(['message' => 'Deleted.']);
        }

        if ($type === 'response') {
            $id = ChatbotContentRepository::saveResponse($body);
            jsonOk(['message' => 'Response saved.', 'id' => $id]);
        }
        if ($type === 'knowledge') {
            $id = ChatbotContentRepository::saveKnowledge($body);
            jsonOk(['message' => 'Knowledge article saved.', 'id' => $id]);
        }

        $id = ChatbotContentRepository::saveIntent($body);
        jsonOk(['message' => 'Intent saved.', 'id' => $id]);
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
