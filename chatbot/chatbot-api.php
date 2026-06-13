<?php
/**
 * Biver Royalty Homes — Chatbot REST API
 */

declare(strict_types=1);

require_once __DIR__ . '/chatbot-config.php';
require_once __DIR__ . '/includes/ChatbotSecurity.php';
require_once __DIR__ . '/includes/ChatbotRepository.php';
require_once __DIR__ . '/includes/ChatbotEngine.php';
require_once dirname(__DIR__) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__) . '/includes/SupportPlatformRepository.php';
require_once dirname(__DIR__) . '/includes/ChatbotNotificationService.php';

ChatbotSecurity::requireAjaxOrigin();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Admin dashboard uses BRE_ADMIN_SID; visitor chat uses BRE_CHAT_SID — never mix them.
if (str_starts_with($action, 'admin_')) {
    AuthSecurity::initSession();
} else {
    ChatbotSecurity::initSession();
}

try {
    $repo = new ChatbotRepository();
    $pdo = getDatabaseConnection();

    if ($action === 'health') {
        chatbotJsonResponse([
            'success'   => true,
            'installed' => $repo->isInstalled(),
            'csrf_token' => ChatbotSecurity::generateCsrfToken(),
        ]);
    }

    if (!in_array($action, ['csrf', 'health'], true) && !$repo->isInstalled()) {
        chatbotJsonResponse([
            'success' => false,
            'error'   => 'Chat database not installed. Please open sql/install_chatbot.php in your browser.',
        ], 503);
    }

    if (str_starts_with($action, 'admin_')) {
        handleAdminAction($action, $repo);
    }

    match ($action) {
        'init'        => handleInit($repo),
        'send'        => handleSend($repo),
        'poll'        => handlePoll($repo),
        'history'     => handleHistory($repo),
        'mark_read'   => handleMarkRead($repo),
        'escalate'         => handleEscalate($repo),
        'support_request'  => handleSupportRequest($repo),
        'agent_connect'    => handleAgentConnect($repo),
        'resume_bot'       => handleResumeBot($repo),
        'search'           => handleSearch($repo),
        'inspection'  => handleInspection($repo),
        'track'       => handleTrack($repo),
        'csrf'        => handleCsrf(),
        default       => chatbotJsonResponse(['success' => false, 'error' => 'Unknown action'], 400),
    };
} catch (PDOException $e) {
    error_log('Chatbot API DB error: ' . $e->getMessage());
    chatbotJsonResponse([
        'success' => false,
        'error'   => 'Database unavailable. Please ensure chatbot tables are installed.',
    ], 503);
} catch (Throwable $e) {
    error_log('Chatbot API error: ' . $e->getMessage());
    chatbotJsonResponse(['success' => false, 'error' => 'An unexpected error occurred.'], 500);
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $_POST;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function requireCsrf(array $body, bool $strict = true): void
{
    $token = $body['csrf_token'] ?? $_SERVER['HTTP_X_CHATBOT_CSRF'] ?? '';
    if ($token === '' && !$strict) {
        return;
    }
    if (!ChatbotSecurity::validateCsrfToken($token)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh the page.'], 403);
    }
}

function requireSession(array $body, ChatbotRepository $repo): array
{
    $sessionUuid = $body['session_uuid'] ?? '';
    if ($sessionUuid === '' || !preg_match('/^[a-f0-9\-]{36}$/i', $sessionUuid)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Invalid session. Please refresh.'], 401);
    }

    $session = $repo->getSessionByUuid($sessionUuid);
    if (!$session || ($session['status'] ?? '') === 'closed') {
        chatbotJsonResponse(['success' => false, 'error' => 'Session not found or closed.'], 404);
    }

    ChatbotSecurity::bindVisitorSession($sessionUuid);

    return $session;
}

function handleCsrf(): never
{
    chatbotJsonResponse([
        'success'    => true,
        'csrf_token' => ChatbotSecurity::generateCsrfToken(),
    ]);
}

function handleInit(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    $csrf = $body['csrf_token'] ?? $_SERVER['HTTP_X_CHATBOT_CSRF'] ?? '';
    if ($csrf !== '' && !ChatbotSecurity::validateCsrfToken($csrf)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh the page.'], 403);
    }

    $visitorUuid = $body['visitor_uuid'] ?? chatbotGenerateUuid();
    if (!preg_match('/^[a-f0-9\-]{36}$/i', $visitorUuid)) {
        $visitorUuid = chatbotGenerateUuid();
    }

    $user = $repo->findOrCreateVisitor($visitorUuid, [
        'name'  => ChatbotSecurity::sanitizeText($body['name'] ?? '', 120) ?: null,
        'email' => ChatbotSecurity::sanitizeEmail($body['email'] ?? null),
        'phone' => ChatbotSecurity::sanitizePhone($body['phone'] ?? null),
    ]);

    $existingUuid = $_SESSION['chatbot_session_uuid'] ?? null;
    $session = null;

    if ($existingUuid) {
        $session = $repo->getSessionByUuid($existingUuid);
        if ($session && ($session['status'] ?? '') !== 'closed') {
            ChatbotSecurity::bindVisitorSession($session['session_uuid']);
            $messages = $repo->getMessages((int) $session['id'], null, 100);

            chatbotJsonResponse([
                'success'      => true,
                'visitor_uuid' => $visitorUuid,
                'session_uuid' => $session['session_uuid'],
                'mode'         => $session['mode'],
                'status'       => $session['status'],
                'messages'     => $messages,
                'csrf_token'   => ChatbotSecurity::generateCsrfToken(),
                'config'       => chatbotPublicConfig(),
                'resumed'      => true,
            ]);
        }
    }

    $session = $repo->createSession((int) $user['id'], [
        'page_url'      => ChatbotSecurity::sanitizeText($body['page_url'] ?? '', 500) ?: null,
        'visitor_name'  => $user['name'] ?? null,
        'visitor_email' => $user['email'] ?? null,
        'visitor_phone' => $user['phone'] ?? null,
    ]);

    ChatbotSecurity::bindVisitorSession($session['session_uuid']);

    $welcome = '👋 Welcome to Biver Royalty Homes. I am your virtual property assistant. How may I help you today?';
    $botMessage = $repo->addMessage((int) $session['id'], 'bot', $welcome, [
        'message_type' => 'system',
    ]);

    $repo->logVisitorEvent((int) $session['id'], (int) $user['id'], 'session_start', [
        'page_url' => $body['page_url'] ?? null,
    ]);
    $repo->logVisitorEvent((int) $session['id'], (int) $user['id'], 'page_visit', [
        'page_url' => $body['page_url'] ?? null,
    ]);

    chatbotJsonResponse([
        'success'      => true,
        'visitor_uuid' => $visitorUuid,
        'session_uuid' => $session['session_uuid'],
        'mode'         => $session['mode'] ?? 'bot',
        'status'       => $session['status'] ?? 'active',
        'messages'     => [$botMessage],
        'csrf_token'   => ChatbotSecurity::generateCsrfToken(),
        'config'       => chatbotPublicConfig(),
        'resumed'      => false,
    ]);
}

function handleSend(ChatbotRepository $repo): never
{
    global $pdo;
    $body = getJsonBody();
    requireCsrf($body, false);

    if (!ChatbotSecurity::checkRateLimit($pdo)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Too many messages. Please wait a moment.'], 429);
    }

    $session = requireSession($body, $repo);
    $message = ChatbotSecurity::sanitizeText($body['message'] ?? '');

    if ($message === '') {
        chatbotJsonResponse(['success' => false, 'error' => 'Message cannot be empty.'], 422);
    }

    $sessionId = (int) $session['id'];
    $userId = (int) $session['user_id'];

    $visitorMsg = $repo->addMessage($sessionId, 'visitor', $message);
    $repo->logVisitorEvent($sessionId, $userId, 'message_sent', ['message_id' => $visitorMsg['id']]);

    // Only skip AI after the visitor explicitly requested a human (mode = human).
    if (($session['mode'] ?? 'bot') === 'human') {
        $ack = $repo->addMessage(
            $sessionId,
            'bot',
            "Thanks — your message was sent to our support team. A consultant will reply in this chat shortly.\n\n"
            . "To get **instant AI answers** again, tap **Continue with AI** below.",
            [
                'message_type' => 'system',
                'metadata'     => ['show_resume_bot_button' => true],
            ]
        );
        chatbotJsonResponse([
            'success'        => true,
            'mode'           => 'human',
            'status'         => $session['status'] ?? 'waiting',
            'messages'       => [$visitorMsg, $ack],
            'awaiting_agent' => true,
        ]);
    }

    try {
        $engine = new ChatbotEngine($repo);
        $result = $engine->processMessage($message, ['session' => $session]);
    } catch (Throwable $e) {
        error_log('Chatbot engine error: ' . $e->getMessage());
        $result = [
            'response'   => 'I apologise — I hit a small technical snag. Please try your question again, or ask about properties, rentals, land, or pricing in ₦.',
            'source'     => 'system',
            'confidence' => 0.0,
            'intent'     => null,
            'escalate'   => false,
        ];
    }

    $metadata = $result['metadata'] ?? [];
    $offerHuman = !empty($metadata['show_support_options']) || !empty($metadata['offer_human_support']);

    $botMsg = $repo->addMessage($sessionId, 'bot', $result['response'], [
        'message_type' => $offerHuman ? 'escalation' : 'text',
        'metadata'     => array_merge($metadata, [
            'source'              => $result['source'],
            'confidence'          => $result['confidence'],
            'intent'              => $result['intent'],
            'offer_human_support' => $offerHuman,
        ]),
    ]);

    chatbotJsonResponse([
        'success'  => true,
        'mode'     => 'bot',
        'messages' => [$visitorMsg, $botMsg],
        'engine'   => [
            'source'              => $result['source'],
            'confidence'          => $result['confidence'],
            'intent'              => $result['intent'],
            'escalate'            => false,
            'offer_human_support' => $offerHuman,
        ],
    ]);
}

function handleResumeBot(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);
    $sessionId = (int) $session['id'];

    $repo->updateSession($sessionId, ['mode' => 'bot', 'status' => 'active']);

    $msg = $repo->addMessage(
        $sessionId,
        'bot',
        "You're back with the **AI assistant**. Ask me anything about properties, rentals, land, or pricing in ₦ — I'm here to help.",
        ['message_type' => 'system']
    );

    chatbotJsonResponse([
        'success'  => true,
        'mode'     => 'bot',
        'status'   => 'active',
        'message'  => $msg,
        'messages' => [$msg],
    ]);
}

function handlePoll(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    $sessionUuid = $body['session_uuid'] ?? $_GET['session_uuid'] ?? '';

    if ($sessionUuid === '' || !preg_match('/^[a-f0-9\-]{36}$/i', $sessionUuid)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Invalid session'], 401);
    }

    $session = $repo->getSessionByUuid($sessionUuid);
    if (!$session) {
        chatbotJsonResponse(['success' => false, 'error' => 'Session not found'], 404);
    }

    $afterId = (int) ($body['after_id'] ?? $_GET['after_id'] ?? 0);
    $messages = $repo->getMessages((int) $session['id'], $afterId > 0 ? $afterId : null, 50);

    chatbotJsonResponse([
        'success'        => true,
        'messages'       => $messages,
        'mode'           => $session['mode'],
        'status'         => $session['status'],
        'unread_visitor' => (int) ($session['unread_visitor'] ?? 0),
    ]);
}

function handleHistory(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    $session = requireSession($body, $repo);

    chatbotJsonResponse([
        'success'  => true,
        'messages' => $repo->getMessages((int) $session['id'], null, 200),
    ]);
}

function handleMarkRead(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);

    $count = $repo->markMessagesRead((int) $session['id'], 'visitor');

    chatbotJsonResponse(['success' => true, 'marked' => $count]);
}

function handleEscalate(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);

    $question = ChatbotSecurity::sanitizeText($body['question'] ?? $body['message'] ?? 'Visitor requested human assistance.');
    $name = ChatbotSecurity::sanitizeText($body['name'] ?? $session['visitor_name'] ?? '', 120) ?: null;
    $email = ChatbotSecurity::sanitizeEmail($body['email'] ?? $session['visitor_email'] ?? null);
    $phone = ChatbotSecurity::sanitizePhone($body['phone'] ?? $session['visitor_phone'] ?? null);

    if ($name || $email || $phone) {
        $repo->updateSession((int) $session['id'], [
            'visitor_name'  => $name,
            'visitor_email' => $email,
            'visitor_phone' => $phone,
        ]);
    }

    $ticket = $repo->createSupportTicket((int) $session['id'], (int) $session['user_id'], $question, [
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
    ]);

    $systemMsg = $repo->addMessage(
        (int) $session['id'],
        'system',
        "Your request has been forwarded to our property consultants. Ticket reference: {$ticket['ticket_number']}. An agent will respond shortly.",
        ['message_type' => 'system']
    );

    $repo->logVisitorEvent((int) $session['id'], (int) $session['user_id'], 'escalation', [
        'ticket_id' => $ticket['id'],
    ]);

    try {
        $support = new SupportPlatformRepository(getDatabaseConnection());
        if ($support->isInstalled()) {
            $support->ensureConversation($session);
            ChatbotNotificationService::notifyNewSupportRequest([
                'visitor_name'  => $name,
                'visitor_phone' => $phone,
                'visitor_email' => $email,
                'question'      => $question,
            ], $ticket['ticket_number']);
        }
    } catch (Throwable) {
        /* non-fatal */
    }

    chatbotJsonResponse([
        'success' => true,
        'ticket'  => $ticket,
        'message' => $systemMsg,
        'mode'    => 'human',
        'status'  => 'waiting',
    ]);
}

function handleSupportRequest(ChatbotRepository $repo): never
{
    global $pdo;
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);

    $name = ChatbotSecurity::sanitizeText($body['name'] ?? '', 120);
    $phone = ChatbotSecurity::sanitizePhone($body['phone'] ?? '');
    $email = ChatbotSecurity::sanitizeEmail($body['email'] ?? null);
    $question = ChatbotSecurity::sanitizeText($body['question'] ?? '', 2000);

    if ($name === '' || !$phone || $question === '') {
        chatbotJsonResponse(['success' => false, 'error' => 'Name, phone, and question are required.'], 422);
    }

    $repo->updateSession((int) $session['id'], [
        'visitor_name'  => $name,
        'visitor_email' => $email,
        'visitor_phone' => $phone,
        'mode'          => 'human',
        'status'        => 'waiting',
    ]);

    $ticket = $repo->createSupportTicket((int) $session['id'], (int) $session['user_id'], $question, [
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
    ]);

    $support = new SupportPlatformRepository($pdo);
    if ($support->isInstalled()) {
        $support->submitHumanSupportRequest([
            'session'  => $session,
            'name'     => $name,
            'phone'    => $phone,
            'email'    => $email,
            'question' => $question,
        ]);
        ChatbotNotificationService::notifyNewSupportRequest([
            'visitor_name'  => $name,
            'visitor_phone' => $phone,
            'visitor_email' => $email,
            'question'      => $question,
        ], $ticket['ticket_number']);
    }

    $systemMsg = $repo->addMessage(
        (int) $session['id'],
        'system',
        'Thank you, ' . $name . ". Your request has been received (Ref: {$ticket['ticket_number']}). A consultant will reply in this chat shortly.",
        ['message_type' => 'system']
    );

    chatbotJsonResponse([
        'success' => true,
        'ticket'  => $ticket,
        'message' => $systemMsg,
        'mode'    => 'human',
        'status'  => 'waiting',
    ]);
}

function handleAgentConnect(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);

    $repo->updateSession((int) $session['id'], ['mode' => 'human', 'status' => 'waiting']);

    $systemMsg = $repo->addMessage(
        (int) $session['id'],
        'system',
        "You've requested a **human consultant**. Someone from our team will join this chat shortly — please stay on this page.\n\n"
        . "Until they reply, you can tap **Continue with AI** if you'd like more instant answers from me.",
        [
            'message_type' => 'system',
            'metadata'     => ['show_resume_bot_button' => true],
        ]
    );

    try {
        $support = new SupportPlatformRepository(getDatabaseConnection());
        if ($support->isInstalled()) {
            $support->ensureConversation($session);
        }
    } catch (Throwable) {
        /* non-fatal */
    }

    chatbotJsonResponse([
        'success' => true,
        'message' => $systemMsg,
        'mode'    => 'human',
        'status'  => 'waiting',
    ]);
}

function handleSearch(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);

    $query = ChatbotSecurity::sanitizeText($body['query'] ?? '', 200);
    if ($query === '') {
        chatbotJsonResponse(['success' => false, 'error' => 'Search query required'], 422);
    }

    chatbotJsonResponse([
        'success' => true,
        'results' => $repo->searchMessages((int) $session['id'], $query),
    ]);
}

function handleInspection(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    requireCsrf($body);
    $session = requireSession($body, $repo);

    $data = [
        'property'       => ChatbotSecurity::sanitizeText($body['property'] ?? '', 200) ?: null,
        'preferred_date' => ChatbotSecurity::sanitizeText($body['preferred_date'] ?? '', 100) ?: null,
        'name'           => ChatbotSecurity::sanitizeText($body['name'] ?? '', 120) ?: null,
        'email'          => ChatbotSecurity::sanitizeEmail($body['email'] ?? null),
        'phone'          => ChatbotSecurity::sanitizePhone($body['phone'] ?? null),
        'notes'          => ChatbotSecurity::sanitizeText($body['notes'] ?? '', 500) ?: null,
    ];

    if (!$data['name'] || !$data['phone']) {
        chatbotJsonResponse(['success' => false, 'error' => 'Name and phone are required for inspection booking.'], 422);
    }

    $ticket = $repo->createInspectionRequest((int) $session['id'], (int) $session['user_id'], $data);

    $confirmMsg = $repo->addMessage(
        (int) $session['id'],
        'bot',
        "✅ Your inspection request has been received (Ref: {$ticket['ticket_number']}). Our team will contact you at {$data['phone']} to confirm your appointment.",
        ['message_type' => 'inspection']
    );

    chatbotJsonResponse([
        'success' => true,
        'ticket'  => $ticket,
        'message' => $confirmMsg,
    ]);
}

function handleTrack(ChatbotRepository $repo): never
{
    $body = getJsonBody();
    $sessionUuid = $body['session_uuid'] ?? null;
    $sessionId = null;
    $userId = null;

    if ($sessionUuid && ChatbotSecurity::validateVisitorSession($sessionUuid)) {
        $session = $repo->getSessionByUuid($sessionUuid);
        if ($session) {
            $sessionId = (int) $session['id'];
            $userId = (int) $session['user_id'];
        }
    }

    $repo->logVisitorEvent($sessionId, $userId, $body['event'] ?? 'page_visit', [
        'page_url' => ChatbotSecurity::sanitizeText($body['page_url'] ?? '', 500) ?: null,
    ]);

    chatbotJsonResponse(['success' => true]);
}

function handleAdminAction(string $action, ChatbotRepository $repo): never
{
    AuthSecurity::initSession();

    if (!AuthSecurity::isAuthenticated()) {
        chatbotJsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
    }

    $body = getJsonBody();
    $csrf = $body['csrf_token']
        ?? $_SERVER['HTTP_X_ADMIN_CSRF']
        ?? $_SERVER['HTTP_X_CHATBOT_CSRF']
        ?? '';
    if (!AuthSecurity::validateCsrfToken($csrf)) {
        chatbotJsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
    }

    if ($action === 'admin_stats') {
        $stats = $repo->getDashboardStats();
        try {
            $support = new SupportPlatformRepository(getDatabaseConnection());
            if ($support->isInstalled()) {
                $stats = array_merge($stats, $support->getDashboardStats());
            }
        } catch (Throwable) {
            /* optional */
        }
        chatbotJsonResponse(['success' => true, 'stats' => $stats]);
    }

    if ($action === 'admin_conversations') {
        chatbotJsonResponse([
            'success'       => true,
            'conversations' => $repo->getRecentConversations(
                (int) ($body['limit'] ?? 30),
                $body['search'] ?? null
            ),
        ]);
    }

    if ($action === 'admin_messages') {
        $sessionId = (int) ($body['session_id'] ?? 0);
        if ($sessionId <= 0) {
            chatbotJsonResponse(['success' => false, 'error' => 'session_id required'], 422);
        }
        chatbotJsonResponse([
            'success'  => true,
            'messages' => $repo->getMessages($sessionId, null, 500),
            'session'  => $repo->getSessionById($sessionId),
        ]);
    }

    if ($action === 'admin_reply') {
        $sessionId = (int) ($body['session_id'] ?? 0);
        $message = ChatbotSecurity::sanitizeText($body['message'] ?? '');
        if ($sessionId <= 0 || $message === '') {
            chatbotJsonResponse(['success' => false, 'error' => 'session_id and message required'], 422);
        }
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $msg = $repo->addMessage($sessionId, 'agent', $message, [
            'sender_id'    => $adminId,
            'message_type' => 'text',
        ]);
        $repo->updateSession($sessionId, ['status' => 'active', 'mode' => 'human']);
        chatbotJsonResponse(['success' => true, 'message' => $msg]);
    }

    if ($action === 'admin_assign') {
        $sessionId = (int) ($body['session_id'] ?? 0);
        $agentId = (int) ($body['agent_id'] ?? 0);
        if ($sessionId <= 0 || $agentId <= 0) {
            chatbotJsonResponse(['success' => false, 'error' => 'session_id and agent_id required'], 422);
        }
        $repo->assignAgent($sessionId, $agentId);
        chatbotJsonResponse(['success' => true]);
    }

    if ($action === 'admin_close') {
        $sessionId = (int) ($body['session_id'] ?? 0);
        if ($sessionId <= 0) {
            chatbotJsonResponse(['success' => false, 'error' => 'session_id required'], 422);
        }
        $repo->closeSession($sessionId);
        $repo->addMessage($sessionId, 'system', 'This conversation has been closed by an agent. Thank you for contacting Biver Royalty Homes.', [
            'message_type' => 'system',
        ]);
        chatbotJsonResponse(['success' => true]);
    }

    if ($action === 'admin_export') {
        $sessionId = (int) ($body['session_id'] ?? 0);
        if ($sessionId <= 0) {
            chatbotJsonResponse(['success' => false, 'error' => 'session_id required'], 422);
        }
        chatbotJsonResponse(['success' => true, 'export' => $repo->exportConversation($sessionId)]);
    }

    if ($action === 'admin_agents') {
        chatbotJsonResponse(['success' => true, 'agents' => $repo->getAgents()]);
    }

    if ($action === 'admin_agent_status') {
        $agentId = (int) ($body['agent_id'] ?? 0);
        $status = $body['status'] ?? 'online';
        if (!in_array($status, ['online', 'away', 'offline'], true)) {
            chatbotJsonResponse(['success' => false, 'error' => 'Invalid status'], 422);
        }
        $pdo = getDatabaseConnection();
        $pdo->prepare('UPDATE agents SET status = :status, last_seen_at = NOW() WHERE id = :id')
            ->execute(['status' => $status, 'id' => $agentId]);
        chatbotJsonResponse(['success' => true]);
    }

    if ($action === 'admin_tickets') {
        $pdo = getDatabaseConnection();
        $tickets = $pdo->query(
            "SELECT st.*, cs.session_uuid FROM support_tickets st
             JOIN chat_sessions cs ON cs.id = st.session_id
             ORDER BY st.created_at DESC LIMIT 50"
        )->fetchAll();
        chatbotJsonResponse(['success' => true, 'tickets' => $tickets]);
    }

    if ($action === 'admin_leads') {
        $support = new SupportPlatformRepository(getDatabaseConnection());
        if (!$support->isInstalled()) {
            chatbotJsonResponse(['success' => false, 'error' => 'Support platform not installed'], 503);
        }
        chatbotJsonResponse([
            'success' => true,
            'leads'   => $support->getLeads((int) ($body['limit'] ?? 50), $body['stage'] ?? null),
        ]);
    }

    if ($action === 'admin_update_lead') {
        $leadId = (int) ($body['lead_id'] ?? 0);
        $stage = $body['stage'] ?? '';
        if ($leadId <= 0 || $stage === '') {
            chatbotJsonResponse(['success' => false, 'error' => 'lead_id and stage required'], 422);
        }
        $support = new SupportPlatformRepository(getDatabaseConnection());
        $ok = $support->updateLeadStage($leadId, $stage);
        chatbotJsonResponse(['success' => $ok]);
    }

    if ($action === 'admin_assign_label') {
        $sessionId = (int) ($body['session_id'] ?? 0);
        $assignedTo = ChatbotSecurity::sanitizeText($body['assigned_to'] ?? '', 120);
        if ($sessionId <= 0 || $assignedTo === '') {
            chatbotJsonResponse(['success' => false, 'error' => 'session_id and assigned_to required'], 422);
        }
        $support = new SupportPlatformRepository(getDatabaseConnection());
        $conv = $support->getConversationBySessionId($sessionId);
        if ($conv) {
            $support->assignConversation((int) $conv['id'], $assignedTo, (int) ($body['agent_id'] ?? 0) ?: null);
            ChatbotNotificationService::notifyAgentAssignment('Session #' . $sessionId, $assignedTo);
        }
        if (!empty($body['agent_id'])) {
            $repo->assignAgent($sessionId, (int) $body['agent_id']);
        }
        chatbotJsonResponse(['success' => true]);
    }

    chatbotJsonResponse(['success' => false, 'error' => 'Unknown admin action'], 400);
}
