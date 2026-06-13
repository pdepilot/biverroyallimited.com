<?php
/**
 * Database operations for the chatbot system.
 */

declare(strict_types=1);

require_once __DIR__ . '/../chatbot-config.php';
require_once __DIR__ . '/ChatbotSecurity.php';

class ChatbotRepository
{
    private static int $contentCacheToken = 0;

    private PDO $pdo;

    /** Call after DB content changes so intents/FAQs/KB reload immediately. */
    public static function invalidateContentCache(): void
    {
        self::$contentCacheToken++;
    }

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getDatabaseConnection();
    }

    public function isInstalled(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM chat_sessions LIMIT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function findOrCreateVisitor(string $visitorUuid, array $meta = []): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE visitor_uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $visitorUuid]);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO users (visitor_uuid, name, email, phone, ip_address, user_agent, metadata)
             VALUES (:uuid, :name, :email, :phone, :ip, :ua, :meta)'
        );
        $insert->execute([
            'uuid'  => $visitorUuid,
            'name'  => $meta['name'] ?? null,
            'email' => $meta['email'] ?? null,
            'phone' => $meta['phone'] ?? null,
            'ip'    => ChatbotSecurity::getClientIp(),
            'ua'    => ChatbotSecurity::getUserAgent(),
            'meta'  => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $stmt->execute(['uuid' => $visitorUuid]);
        return $stmt->fetch() ?: ['id' => $id, 'visitor_uuid' => $visitorUuid];
    }

    public function createSession(int $userId, array $data = []): array
    {
        $uuid = chatbotGenerateUuid();

        $stmt = $this->pdo->prepare(
            'INSERT INTO chat_sessions
             (session_uuid, user_id, mode, status, page_url, visitor_name, visitor_email, visitor_phone, last_message_at)
             VALUES (:uuid, :user_id, :mode, :status, :page_url, :name, :email, :phone, NOW())'
        );
        $stmt->execute([
            'uuid'     => $uuid,
            'user_id'  => $userId,
            'mode'     => $data['mode'] ?? 'bot',
            'status'   => $data['status'] ?? 'active',
            'page_url' => $data['page_url'] ?? null,
            'name'     => $data['visitor_name'] ?? null,
            'email'    => $data['visitor_email'] ?? null,
            'phone'    => $data['visitor_phone'] ?? null,
        ]);

        return $this->getSessionByUuid($uuid) ?? ['session_uuid' => $uuid];
    }

    public function getSessionByUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cs.*, u.visitor_uuid, u.name AS user_name, u.email AS user_email
             FROM chat_sessions cs
             JOIN users u ON u.id = cs.user_id
             WHERE cs.session_uuid = :uuid LIMIT 1'
        );
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getSessionById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM chat_sessions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateSession(int $sessionId, array $fields): void
    {
        $allowed = ['mode', 'status', 'agent_id', 'visitor_name', 'visitor_email', 'visitor_phone', 'subject', 'unread_admin', 'unread_visitor', 'last_message_at', 'closed_at'];
        $sets = [];
        $params = ['id' => $sessionId];

        foreach ($fields as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[] = "`{$key}` = :{$key}";
                $params[$key] = $value;
            }
        }

        if ($sets === []) {
            return;
        }

        $sql = 'UPDATE chat_sessions SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function addMessage(int $sessionId, string $senderType, string $message, array $opts = []): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO chat_messages
             (session_id, sender_type, sender_id, message, message_type, delivery_status, metadata)
             VALUES (:session_id, :sender_type, :sender_id, :message, :message_type, :delivery_status, :metadata)'
        );
        $stmt->execute([
            'session_id'      => $sessionId,
            'sender_type'     => $senderType,
            'sender_id'       => $opts['sender_id'] ?? null,
            'message'         => $message,
            'message_type'    => $opts['message_type'] ?? 'text',
            'delivery_status' => $opts['delivery_status'] ?? 'delivered',
            'metadata'        => isset($opts['metadata']) ? json_encode($opts['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ]);

        $messageId = (int) $this->pdo->lastInsertId();

        $unreadField = $senderType === 'visitor' ? 'unread_admin' : 'unread_visitor';
        $this->pdo->prepare(
            "UPDATE chat_sessions SET last_message_at = NOW(), {$unreadField} = {$unreadField} + 1 WHERE id = :id"
        )->execute(['id' => $sessionId]);

        $messageRow = $this->getMessageById($messageId) ?? ['id' => $messageId];

        try {
            if (!class_exists('SupportPlatformRepository', false)) {
                require_once dirname(__DIR__, 2) . '/includes/SupportPlatformRepository.php';
            }
            $support = new SupportPlatformRepository($this->pdo);
            if ($support->isInstalled()) {
                $support->mirrorChatMessage($sessionId, $senderType, $message, $messageId);
            }
        } catch (Throwable) {
            /* non-fatal */
        }

        return $messageRow;
    }

    public function getMessageById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM chat_messages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->formatMessage($row) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMessages(int $sessionId, ?int $afterId = null, int $limit = 100): array
    {
        $sql = 'SELECT * FROM chat_messages WHERE session_id = :session_id';
        $params = ['session_id' => $sessionId];

        if ($afterId !== null && $afterId > 0) {
            $sql .= ' AND id > :after_id';
            $params['after_id'] = $afterId;
        }

        $sql .= ' ORDER BY id ASC LIMIT ' . max(1, min($limit, 200));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([$this, 'formatMessage'], $stmt->fetchAll());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchMessages(int $sessionId, string $query): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM chat_messages
             WHERE session_id = :session_id AND message LIKE :q
             ORDER BY id DESC LIMIT 50'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'q'          => '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%',
        ]);

        return array_map([$this, 'formatMessage'], $stmt->fetchAll());
    }

    public function markMessagesRead(int $sessionId, string $readerType): int
    {
        $senderTypes = $readerType === 'visitor'
            ? ['bot', 'agent', 'system']
            : ['visitor'];

        $placeholders = implode(',', array_fill(0, count($senderTypes), '?'));
        $params = array_merge([$sessionId], $senderTypes);

        $stmt = $this->pdo->prepare(
            "UPDATE chat_messages SET delivery_status = 'read'
             WHERE session_id = ? AND sender_type IN ({$placeholders}) AND delivery_status != 'read'"
        );
        $stmt->execute($params);
        $count = $stmt->rowCount();

        $field = $readerType === 'visitor' ? 'unread_visitor' : 'unread_admin';
        $this->pdo->prepare("UPDATE chat_sessions SET {$field} = 0 WHERE id = :id")
            ->execute(['id' => $sessionId]);

        return $count;
    }

    public function logVisitorEvent(?int $sessionId, ?int $userId, string $eventType, array $data = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO visitor_logs
             (session_id, user_id, ip_address, user_agent, page_url, referrer, event_type, event_data)
             VALUES (:session_id, :user_id, :ip, :ua, :page_url, :referrer, :event_type, :event_data)'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'user_id'    => $userId,
            'ip'         => ChatbotSecurity::getClientIp(),
            'ua'         => ChatbotSecurity::getUserAgent(),
            'page_url'   => $data['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? null),
            'referrer'   => $data['referrer'] ?? null,
            'event_type' => $eventType,
            'event_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function createSupportTicket(int $sessionId, int $userId, string $question, array $visitor = []): array
    {
        $ticketNumber = chatbotGenerateTicketNumber();

        $stmt = $this->pdo->prepare(
            'INSERT INTO support_tickets
             (session_id, user_id, ticket_number, question, status, priority, visitor_name, visitor_email, visitor_phone)
             VALUES (:session_id, :user_id, :ticket_number, :question, :status, :priority, :name, :email, :phone)'
        );
        $stmt->execute([
            'session_id'    => $sessionId,
            'user_id'       => $userId,
            'ticket_number' => $ticketNumber,
            'question'      => $question,
            'status'        => 'open',
            'priority'      => 'normal',
            'name'          => $visitor['name'] ?? null,
            'email'         => $visitor['email'] ?? null,
            'phone'         => $visitor['phone'] ?? null,
        ]);

        $ticketId = (int) $this->pdo->lastInsertId();

        $this->queueNotification('escalation', $ticketId, null, 'New Chat Escalation', sprintf(
            'Ticket %s: %s',
            $ticketNumber,
            mb_substr($question, 0, 200)
        ));

        $this->updateSession($sessionId, [
            'mode'   => 'human',
            'status' => 'waiting',
        ]);

        return $this->getTicketById($ticketId) ?? ['id' => $ticketId, 'ticket_number' => $ticketNumber];
    }

    public function getTicketById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_tickets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function queueNotification(string $type, ?int $referenceId, ?string $recipient, string $subject, string $body): void
    {
        $site = chatbotSiteConfig();
        $stmt = $this->pdo->prepare(
            'INSERT INTO notification_queue (type, reference_id, recipient, subject, body, status)
             VALUES (:type, :ref, :recipient, :subject, :body, :status)'
        );
        $stmt->execute([
            'type'      => $type,
            'ref'       => $referenceId,
            'recipient' => $recipient ?? ($site['contactEmail'] ?? null),
            'subject'   => $subject,
            'body'      => $body,
            'status'    => 'pending',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActiveIntents(): array
    {
        static $cache = null;
        static $cacheTime = 0;
        static $token = -1;

        if ($token !== self::$contentCacheToken) {
            $cache = null;
            $token = self::$contentCacheToken;
        }

        if ($cache !== null && (time() - $cacheTime) < CHATBOT_INTENT_CACHE_TTL) {
            return $cache;
        }

        $stmt = $this->pdo->query(
            'SELECT * FROM chatbot_intents WHERE is_active = 1 ORDER BY priority DESC'
        );
        $rows = $stmt->fetchAll();

        $responsesByIntent = [];
        $respStmt = $this->pdo->query(
            'SELECT id, intent_id, response_text, weight
             FROM chatbot_responses
             WHERE is_active = 1
             ORDER BY intent_id ASC, weight DESC, id ASC'
        );
        foreach ($respStmt->fetchAll() as $resp) {
            $intentId = (int) $resp['intent_id'];
            $responsesByIntent[$intentId][] = [
                'id'     => (int) $resp['id'],
                'text'   => $resp['response_text'],
                'weight' => (int) ($resp['weight'] ?? 1),
            ];
        }

        foreach ($rows as &$row) {
            $row['keywords'] = json_decode($row['keywords'] ?? '[]', true) ?: [];
            $row['responses'] = $responsesByIntent[(int) $row['id']] ?? [];
        }
        unset($row);

        $cache = $rows;
        $cacheTime = time();

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActiveFaqs(): array
    {
        static $cache = null;
        static $cacheTime = 0;
        static $token = -1;

        if ($token !== self::$contentCacheToken) {
            $cache = null;
            $token = self::$contentCacheToken;
        }

        if ($cache !== null && (time() - $cacheTime) < CHATBOT_INTENT_CACHE_TTL) {
            return $cache;
        }

        $stmt = $this->pdo->query(
            'SELECT * FROM chatbot_faqs WHERE is_active = 1 ORDER BY priority DESC'
        );
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['keywords'] = json_decode($row['keywords'] ?? '[]', true) ?: [];
        }
        unset($row);

        $cache = $rows;
        $cacheTime = time();

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActiveKnowledgeBase(): array
    {
        static $cache = null;
        static $cacheTime = 0;
        static $token = -1;

        if ($token !== self::$contentCacheToken) {
            $cache = null;
            $token = self::$contentCacheToken;
        }

        if ($cache !== null && (time() - $cacheTime) < CHATBOT_INTENT_CACHE_TTL) {
            return $cache;
        }

        $stmt = $this->pdo->query(
            'SELECT * FROM chatbot_knowledgebase WHERE is_active = 1 ORDER BY priority DESC'
        );
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['keywords'] = json_decode($row['keywords'] ?? '[]', true) ?: [];
        }
        unset($row);

        $cache = $rows;
        $cacheTime = time();

        return $rows;
    }

    public function createInspectionRequest(int $sessionId, int $userId, array $data): array
    {
        $details = sprintf(
            "Inspection Request\nProperty: %s\nPreferred Date: %s\nName: %s\nPhone: %s\nEmail: %s\nNotes: %s",
            $data['property'] ?? 'Not specified',
            $data['preferred_date'] ?? 'Flexible',
            $data['name'] ?? 'Not provided',
            $data['phone'] ?? 'Not provided',
            $data['email'] ?? 'Not provided',
            $data['notes'] ?? 'None'
        );

        $ticket = $this->createSupportTicket($sessionId, $userId, $details, [
            'name'  => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $this->updateSession($sessionId, ['subject' => 'Inspection Request']);

        return $ticket;
    }

    /** Dashboard stats */
    public function getDashboardStats(): array
    {
        $stats = [];

        $stats['totalChats'] = (int) $this->pdo->query('SELECT COUNT(*) FROM chat_sessions')->fetchColumn();
        $stats['openTickets'] = (int) $this->pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','in_progress')")->fetchColumn();
        $stats['resolvedTickets'] = (int) $this->pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('resolved','closed')")->fetchColumn();

        $since = date('Y-m-d H:i:s', time() - 900);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM visitor_logs WHERE event_type = 'page_visit' AND created_at >= :since"
        );
        $stmt->execute(['since' => $since]);
        $stats['onlineVisitors'] = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'waiting'");
        $stats['waitingCustomers'] = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status IN ('assigned','active') AND mode = 'human'");
        $stats['activeConversations'] = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM agents WHERE status = 'online' AND is_active = 1");
        $stats['onlineAgents'] = (int) $stmt->fetchColumn();

        return $stats;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentConversations(int $limit = 20, ?string $search = null): array
    {
        $sql = 'SELECT cs.*, u.visitor_uuid, u.email AS user_email,
                       (SELECT message FROM chat_messages cm WHERE cm.session_id = cs.id ORDER BY cm.id DESC LIMIT 1) AS last_message
                FROM chat_sessions cs
                JOIN users u ON u.id = cs.user_id';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' WHERE cs.visitor_name LIKE :q OR cs.visitor_email LIKE :q OR cs.session_uuid LIKE :q OR u.email LIKE :q';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY cs.last_message_at DESC LIMIT ' . max(1, min($limit, 100));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAgents(): array
    {
        return $this->pdo->query('SELECT id, name, email, status, avatar_url, is_active, last_seen_at FROM agents ORDER BY name')->fetchAll();
    }

    public function assignAgent(int $sessionId, int $agentId): void
    {
        $this->updateSession($sessionId, [
            'agent_id' => $agentId,
            'status'   => 'assigned',
            'mode'     => 'human',
        ]);
    }

    public function closeSession(int $sessionId): void
    {
        $this->updateSession($sessionId, [
            'status'    => 'closed',
            'closed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function exportConversation(int $sessionId): array
    {
        $session = $this->getSessionById($sessionId);
        if (!$session) {
            return [];
        }

        return [
            'session'  => $session,
            'messages' => $this->getMessages($sessionId, null, 5000),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatMessage(array $row): array
    {
        return [
            'id'              => (int) $row['id'],
            'sessionId'       => (int) $row['session_id'],
            'senderType'      => $row['sender_type'],
            'senderId'        => $row['sender_id'] !== null ? (int) $row['sender_id'] : null,
            'message'         => $row['message'],
            'messageType'     => $row['message_type'],
            'deliveryStatus'  => $row['delivery_status'],
            'metadata'        => $row['metadata'] ? (json_decode($row['metadata'], true) ?: null) : null,
            'createdAt'       => $row['created_at'],
        ];
    }
}
