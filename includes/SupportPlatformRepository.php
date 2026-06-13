<?php
/**
 * Support conversations, messages, and CRM leads — synced with chat_sessions.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/chatbot_helpers.php';

class SupportPlatformRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getDatabaseConnection();
    }

    public function isInstalled(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM support_conversations LIMIT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function ensureConversation(array $session): array
    {
        $sessionId = (int) $session['id'];
        $existing = $this->getConversationBySessionId($sessionId);
        if ($existing) {
            return $existing;
        }

        $status = match ($session['status'] ?? 'active') {
            'waiting'  => 'pending',
            'closed'   => 'closed',
            default    => 'open',
        };

        $stmt = $this->pdo->prepare(
            'INSERT INTO support_conversations
             (session_id, visitor_name, visitor_email, visitor_phone, status, agent_id, assigned_to)
             VALUES (:sid, :name, :email, :phone, :status, :agent, :assigned)'
        );
        $stmt->execute([
            'sid'      => $sessionId,
            'name'     => $session['visitor_name'] ?? null,
            'email'    => $session['visitor_email'] ?? null,
            'phone'    => $session['visitor_phone'] ?? null,
            'status'   => $status,
            'agent'    => $session['agent_id'] ?? null,
            'assigned' => null,
        ]);

        return $this->getConversationBySessionId($sessionId) ?? ['id' => (int) $this->pdo->lastInsertId(), 'session_id' => $sessionId];
    }

    public function getConversationBySessionId(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_conversations WHERE session_id = :id LIMIT 1');
        $stmt->execute(['id' => $sessionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getConversationById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_conversations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function mirrorChatMessage(int $sessionId, string $senderType, string $message, ?int $chatMessageId = null): void
    {
        if (!$this->isInstalled()) {
            return;
        }

        $conv = $this->getConversationBySessionId($sessionId);
        if (!$conv) {
            $stmt = $this->pdo->prepare('SELECT * FROM chat_sessions WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $sessionId]);
            $session = $stmt->fetch();
            if (!$session) {
                return;
            }
            $conv = $this->ensureConversation($session);
        }

        $sender = chatbot_map_sender_to_support($senderType);
        if ($senderType === 'system') {
            $sender = 'bot';
        }

        $this->pdo->prepare(
            'INSERT INTO support_messages (conversation_id, sender, message, chat_message_id)
             VALUES (:cid, :sender, :msg, :mid)'
        )->execute([
            'cid'    => (int) $conv['id'],
            'sender' => $sender,
            'msg'    => $message,
            'mid'    => $chatMessageId,
        ]);
    }

    /**
     * @param array{session: array, name: string, phone: string, email?: ?string, question: string} $data
     */
    public function submitHumanSupportRequest(array $data): array
    {
        $session = $data['session'];
        $sessionId = (int) $session['id'];
        $conv = $this->ensureConversation($session);

        $this->pdo->prepare(
            'UPDATE support_conversations SET visitor_name = :name, visitor_email = :email,
             visitor_phone = :phone, status = \'pending\', updated_at = NOW() WHERE id = :id'
        )->execute([
            'name'  => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'id'    => (int) $conv['id'],
        ]);

        $leadStmt = $this->pdo->prepare(
            'INSERT INTO chat_leads (conversation_id, session_id, visitor_name, visitor_email, visitor_phone, question, stage, source)
             VALUES (:cid, :sid, :name, :email, :phone, :question, \'New\', \'chatbot\')'
        );
        $leadStmt->execute([
            'cid'      => (int) $conv['id'],
            'sid'      => $sessionId,
            'name'     => $data['name'],
            'email'    => $data['email'] ?? null,
            'phone'    => $data['phone'],
            'question' => $data['question'],
        ]);

        $leadId = (int) $this->pdo->lastInsertId();
        $this->mirrorChatMessage($sessionId, 'user', $data['question']);

        return [
            'conversation' => $this->getConversationById((int) $conv['id']),
            'lead_id'      => $leadId,
        ];
    }

    public function updateLeadStage(int $leadId, string $stage): bool
    {
        $allowed = ['New', 'Contacted', 'Interested', 'Inspection Scheduled', 'Negotiating', 'Closed Sale'];
        if (!in_array($stage, $allowed, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare('UPDATE chat_leads SET stage = :stage WHERE id = :id');
        $stmt->execute(['stage' => $stage, 'id' => $leadId]);
        return $stmt->rowCount() > 0;
    }

    public function assignConversation(int $conversationId, ?string $assignedTo, ?int $agentId = null): void
    {
        $this->pdo->prepare(
            'UPDATE support_conversations SET assigned_to = :assigned, agent_id = :agent, updated_at = NOW() WHERE id = :id'
        )->execute([
            'assigned' => $assignedTo,
            'agent'    => $agentId,
            'id'       => $conversationId,
        ]);

        $this->pdo->prepare(
            'UPDATE chat_leads SET assigned_to = :assigned WHERE conversation_id = :id'
        )->execute(['assigned' => $assignedTo, 'id' => $conversationId]);
    }

    /**
     * @return array<string, int>
     */
    public function getDashboardStats(): array
    {
        if (!$this->isInstalled()) {
            return [];
        }

        $stats = [];
        $stats['total_leads'] = (int) $this->pdo->query('SELECT COUNT(*) FROM chat_leads')->fetchColumn();
        $stats['today_leads'] = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM chat_leads WHERE DATE(created_at) = CURDATE()'
        )->fetchColumn();
        $stats['open_chats'] = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM support_conversations WHERE status = 'open'"
        )->fetchColumn();
        $stats['pending_chats'] = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM support_conversations WHERE status = 'pending'"
        )->fetchColumn();
        $stats['closed_chats'] = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM support_conversations WHERE status = 'closed'"
        )->fetchColumn();
        $stats['total_conversations'] = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM support_conversations'
        )->fetchColumn();

        return $stats;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLeads(int $limit = 50, ?string $stage = null): array
    {
        $sql = 'SELECT l.*, sc.status AS conversation_status, cs.session_uuid
                FROM chat_leads l
                LEFT JOIN support_conversations sc ON sc.id = l.conversation_id
                LEFT JOIN chat_sessions cs ON cs.id = l.session_id
                WHERE 1=1';
        $params = [];
        if ($stage) {
            $sql .= ' AND l.stage = :stage';
            $params['stage'] = $stage;
        }
        $sql .= ' ORDER BY l.created_at DESC LIMIT ' . max(1, min($limit, 200));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function backfillConversations(): int
    {
        if (!$this->isInstalled()) {
            return 0;
        }

        $sessions = $this->pdo->query('SELECT * FROM chat_sessions ORDER BY id ASC')->fetchAll();
        $count = 0;
        foreach ($sessions as $session) {
            $this->ensureConversation($session);
            $count++;

            $msgs = $this->pdo->prepare(
                'SELECT * FROM chat_messages WHERE session_id = :id ORDER BY id ASC'
            );
            $msgs->execute(['id' => $session['id']]);
            foreach ($msgs->fetchAll() as $msg) {
                if ($msg['sender_type'] === 'system') {
                    continue;
                }
                $exists = $this->pdo->prepare(
                    'SELECT id FROM support_messages WHERE chat_message_id = :mid LIMIT 1'
                );
                $exists->execute(['mid' => $msg['id']]);
                if ($exists->fetch()) {
                    continue;
                }
                $this->mirrorChatMessage(
                    (int) $session['id'],
                    $msg['sender_type'],
                    $msg['message'],
                    (int) $msg['id']
                );
            }
        }
        return $count;
    }
}
