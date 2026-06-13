<?php
/**
 * Chatbot human support form endpoint (spec: admin-contact.php).
 * POST JSON: name, phone, email, question, session_uuid, csrf_token
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/chatbot/chatbot-config.php';
require_once dirname(__DIR__, 2) . '/chatbot/includes/ChatbotSecurity.php';
require_once dirname(__DIR__, 2) . '/chatbot/includes/ChatbotRepository.php';
require_once dirname(__DIR__, 2) . '/includes/SupportPlatformRepository.php';
require_once dirname(__DIR__, 2) . '/includes/ChatbotNotificationService.php';

ChatbotSecurity::requireAjaxOrigin();
ChatbotSecurity::initSession();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatbotJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$body = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : $_POST;

$csrf = $body['csrf_token'] ?? $_SERVER['HTTP_X_CHATBOT_CSRF'] ?? '';
if (!ChatbotSecurity::validateCsrfToken($csrf)) {
    chatbotJsonResponse(['success' => false, 'error' => 'Invalid security token'], 403);
}

$name = ChatbotSecurity::sanitizeText($body['name'] ?? '', 120);
$phone = ChatbotSecurity::sanitizePhone($body['phone'] ?? '');
$email = ChatbotSecurity::sanitizeEmail($body['email'] ?? null);
$question = ChatbotSecurity::sanitizeText($body['question'] ?? '', 2000);

if ($name === '' || !$phone || $question === '') {
    chatbotJsonResponse(['success' => false, 'error' => 'Name, phone, and question are required.'], 422);
}

try {
    $repo = new ChatbotRepository();
    $support = new SupportPlatformRepository();

    if (!$support->isInstalled()) {
        chatbotJsonResponse([
            'success' => false,
            'error'   => 'Support platform not installed. Run sql/install_support_platform.php',
        ], 503);
    }

    $sessionUuid = $body['session_uuid'] ?? '';
    if ($sessionUuid === '' || !ChatbotSecurity::validateVisitorSession($sessionUuid)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Invalid chat session'], 401);
    }

    $session = $repo->getSessionByUuid($sessionUuid);
    if (!$session) {
        chatbotJsonResponse(['success' => false, 'error' => 'Session not found'], 404);
    }

    $repo->updateSession((int) $session['id'], [
        'visitor_name'  => $name,
        'visitor_email' => $email,
        'visitor_phone' => $phone,
        'mode'          => 'human',
        'status'        => 'waiting',
    ]);

    $result = $support->submitHumanSupportRequest([
        'session'  => $session,
        'name'     => $name,
        'phone'    => $phone,
        'email'    => $email,
        'question' => $question,
    ]);

    $ticket = $repo->createSupportTicket((int) $session['id'], (int) $session['user_id'], $question, [
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
    ]);

    $confirm = $repo->addMessage(
        (int) $session['id'],
        'system',
        'Thank you, ' . $name . ". Your request has been received (Ref: {$ticket['ticket_number']}). A consultant will reply in this chat shortly.",
        ['message_type' => 'system']
    );

    ChatbotNotificationService::notifyNewSupportRequest([
        'visitor_name'  => $name,
        'visitor_phone' => $phone,
        'visitor_email' => $email,
        'question'      => $question,
    ], $ticket['ticket_number']);

    chatbotJsonResponse([
        'success' => true,
        'message' => 'Support request submitted successfully.',
        'ticket'  => $ticket,
        'lead_id' => $result['lead_id'],
        'chat'    => $confirm,
        'mode'    => 'human',
        'status'  => 'waiting',
    ]);
} catch (Throwable $e) {
    error_log('chatbot-contact API: ' . $e->getMessage());
    chatbotJsonResponse(['success' => false, 'error' => 'Could not submit request. Please try again.'], 500);
}
