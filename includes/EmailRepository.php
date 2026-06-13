<?php
/**
 * Database access for email center, templates, logs, queue, and subscribers.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class EmailRepository
{
    public static function ensureTables(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $pdo = getDatabaseConnection();
        $check = $pdo->query("SHOW TABLES LIKE 'email_logs'");
        if (!$check || !$check->fetch()) {
            $sqlFile = dirname(__DIR__) . '/sql/email_tables.sql';
            if (!is_readable($sqlFile)) {
                throw new RuntimeException('Email tables not installed. Run sql/install_email.php');
            }

            $raw = file_get_contents($sqlFile);
            $statements = array_filter(
                array_map('trim', preg_split('/;\s*\n/', $raw) ?: []),
                static fn (string $s): bool => $s !== '' && !preg_match('/^USE\s/i', $s)
            );

            foreach ($statements as $stmt) {
                $pdo->exec($stmt);
            }
        }

        self::ensureSchema();
        $done = true;
    }

    public static function ensureSchema(): void
    {
        static $schemaDone = false;
        if ($schemaDone) {
            return;
        }

        $pdo = getDatabaseConnection();
        $alters = [
            'email_templates' => [
                'event_key'   => 'VARCHAR(64) DEFAULT NULL',
                'description' => 'VARCHAR(255) DEFAULT NULL',
                'is_system'   => 'TINYINT(1) NOT NULL DEFAULT 0',
            ],
            'email_logs' => [
                'recipient_name'    => 'VARCHAR(120) DEFAULT NULL',
                'email_type'        => 'VARCHAR(64) DEFAULT NULL',
                'related_record_id' => 'INT UNSIGNED DEFAULT NULL',
            ],
        ];

        foreach ($alters as $table => $columns) {
            foreach ($columns as $col => $def) {
                try {
                    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
                } catch (PDOException $e) {
                    if (!str_contains($e->getMessage(), 'Duplicate column')) {
                        // ignore if table missing during first install race
                    }
                }
            }
        }

        require_once __DIR__ . '/AutomatedEmailService.php';
        foreach (AutomatedEmailService::defaultEventTemplates() as $eventKey => $tpl) {
            $check = $pdo->prepare('SELECT id FROM email_templates WHERE event_key = ? LIMIT 1');
            $check->execute([$eventKey]);
            if ($check->fetch()) {
                continue;
            }
            $ins = $pdo->prepare(
                'INSERT INTO email_templates (name, subject, body_html, event_key, description, is_system)
                 VALUES (?, ?, ?, ?, ?, 1)'
            );
            $ins->execute([
                $tpl['name'],
                $tpl['subject'],
                $tpl['body_html'],
                $eventKey,
                $tpl['description'] ?? '',
            ]);
        }

        $schemaDone = true;
    }

    /** @return list<array<string, mixed>> */
    public static function getTemplates(): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT id, name, subject, body_html, event_key, description, is_system, created_at, updated_at
             FROM email_templates ORDER BY name ASC'
        );

        return $stmt ? $stmt->fetchAll() : [];
    }

    /** @return array<string, mixed>|null */
    public static function getTemplateByEvent(string $eventKey): ?array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE event_key = ? LIMIT 1');
        $stmt->execute([$eventKey]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function assignTemplateEvent(int $templateId, ?string $eventKey): bool
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();

        if ($eventKey !== null && $eventKey !== '') {
            $clear = $pdo->prepare('UPDATE email_templates SET event_key = NULL WHERE event_key = ? AND id != ?');
            $clear->execute([$eventKey, $templateId]);
        }

        $stmt = $pdo->prepare('UPDATE email_templates SET event_key = :event WHERE id = :id');

        return $stmt->execute([
            'event' => ($eventKey === '' ? null : $eventKey),
            'id'    => $templateId,
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function getTemplateById(int $id): ?array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function createTemplate(
        string $name,
        string $subject,
        string $bodyHtml,
        int $adminId,
        ?string $eventKey = null,
        ?string $description = null
    ): int {
        self::ensureTables();
        $pdo = getDatabaseConnection();

        if ($eventKey !== null && $eventKey !== '') {
            $pdo->prepare('UPDATE email_templates SET event_key = NULL WHERE event_key = ?')->execute([$eventKey]);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO email_templates (name, subject, body_html, created_by, event_key, description)
             VALUES (:name, :subject, :body, :admin, :event, :desc)'
        );
        $stmt->execute([
            'name'    => $name,
            'subject' => $subject,
            'body'    => $bodyHtml,
            'admin'   => $adminId > 0 ? $adminId : null,
            'event'   => $eventKey ?: null,
            'desc'    => $description,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateTemplate(
        int $id,
        string $name,
        string $subject,
        string $bodyHtml,
        ?string $eventKey = null,
        ?string $description = null
    ): bool {
        self::ensureTables();
        $pdo = getDatabaseConnection();

        if ($eventKey !== null && $eventKey !== '') {
            $pdo->prepare('UPDATE email_templates SET event_key = NULL WHERE event_key = ? AND id != ?')
                ->execute([$eventKey, $id]);
        }

        $stmt = $pdo->prepare(
            'UPDATE email_templates SET name = :name, subject = :subject, body_html = :body,
             event_key = :event, description = :desc WHERE id = :id'
        );

        return $stmt->execute([
            'id'    => $id,
            'name'  => $name,
            'subject' => $subject,
            'body'  => $bodyHtml,
            'event' => $eventKey ?: null,
            'desc'  => $description,
        ]);
    }

    public static function deleteTemplate(int $id): bool
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM email_templates WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public static function duplicateTemplate(int $id, int $adminId): ?int
    {
        $tpl = self::getTemplateById($id);
        if (!$tpl) {
            return null;
        }

        $name = (string) $tpl['name'] . ' (Copy)';

        return self::createTemplate($name, (string) $tpl['subject'], (string) $tpl['body_html'], $adminId);
    }

    /** @return array<string, mixed>|null */
    public static function getDraftForAdmin(int $adminId): ?array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_drafts WHERE admin_id = :aid ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute(['aid' => $adminId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public static function saveDraft(int $adminId, array $data): int
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $existing = self::getDraftForAdmin($adminId);

        $params = [
            'aid'   => $adminId,
            'type'  => (string) ($data['recipient_type'] ?? 'single'),
            'recip' => json_encode($data['recipients'] ?? [], JSON_UNESCAPED_UNICODE),
            'subj'  => (string) ($data['subject'] ?? ''),
            'body'  => (string) ($data['body_html'] ?? ''),
            'tpl'   => !empty($data['template_id']) ? (int) $data['template_id'] : null,
        ];

        if ($existing) {
            $stmt = $pdo->prepare(
                'UPDATE email_drafts SET recipient_type = :type, recipients_json = :recip, subject = :subj,
                 body_html = :body, template_id = :tpl WHERE id = :id'
            );
            $stmt->execute($params + ['id' => (int) $existing['id']]);

            return (int) $existing['id'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO email_drafts (admin_id, recipient_type, recipients_json, subject, body_html, template_id)
             VALUES (:aid, :type, :recip, :subj, :body, :tpl)'
        );
        $stmt->execute($params);

        return (int) $pdo->lastInsertId();
    }

    public static function logEmail(
        string $recipient,
        string $subject,
        string $message,
        string $status,
        int $adminId,
        ?string $errorMsg = null
    ): int {
        return self::logEmailExtended([
            'recipient'  => $recipient,
            'subject'    => $subject,
            'message'    => $message,
            'status'     => $status,
            'admin_id'   => $adminId > 0 ? $adminId : null,
            'error_msg'  => $errorMsg,
        ]);
    }

    /** @param array<string, mixed> $data */
    public static function logEmailExtended(array $data): int
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $status = (string) ($data['status'] ?? 'queued');
        $stmt = $pdo->prepare(
            'INSERT INTO email_logs (recipient, recipient_name, subject, message, status, error_msg,
             sent_at, admin_id, email_type, related_record_id)
             VALUES (:recipient, :rname, :subject, :message, :status, :error, :sent_at, :admin, :etype, :related)'
        );
        $stmt->execute([
            'recipient' => (string) ($data['recipient'] ?? ''),
            'rname'     => (string) ($data['recipient_name'] ?? '') ?: null,
            'subject'   => (string) ($data['subject'] ?? ''),
            'message'   => (string) ($data['message'] ?? ''),
            'status'    => $status,
            'error'     => $data['error_msg'] ?? null,
            'sent_at'   => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            'admin'     => !empty($data['admin_id']) ? (int) $data['admin_id'] : null,
            'etype'     => (string) ($data['email_type'] ?? '') ?: null,
            'related'   => !empty($data['related_record_id']) ? (int) $data['related_record_id'] : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public static function getLogById(int $id): ?array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM email_logs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function getEmailLogs(?string $status = null, ?string $search = null, ?string $emailType = null, int $limit = 100): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $sql = 'SELECT id, recipient, recipient_name, subject, status, error_msg, email_type,
                       related_record_id, sent_at, created_at, admin_id
                FROM email_logs WHERE 1=1';
        $params = [];

        if ($status !== null && $status !== '' && in_array($status, ['sent', 'failed', 'queued'], true)) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        if ($emailType !== null && $emailType !== '') {
            $sql .= ' AND email_type = ?';
            $params[] = $emailType;
        }

        if ($search !== null && $search !== '') {
            $sql .= ' AND (recipient LIKE ? OR recipient_name LIKE ? OR subject LIKE ? OR email_type LIKE ?)';
            $q = '%' . $search . '%';
            $params = array_merge($params, [$q, $q, $q, $q]);
        }

        $sql .= ' ORDER BY COALESCE(sent_at, created_at) DESC LIMIT ' . max(1, min($limit, 500));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array{total:int,sent:int,failed:int,queued:int} */
    public static function getLogStats(): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'sent') AS sent,
                    SUM(status = 'failed') AS failed,
                    SUM(status = 'queued') AS queued
             FROM email_logs"
        );
        $row = $stmt ? $stmt->fetch() : [];

        return [
            'total'  => (int) ($row['total'] ?? 0),
            'sent'   => (int) ($row['sent'] ?? 0),
            'failed' => (int) ($row['failed'] ?? 0),
            'queued' => (int) ($row['queued'] ?? 0),
        ];
    }

    /**
     * @param list<array{email:string,name?:string,html:string,plain:string}> $recipients
     */
    public static function enqueueBatch(
        string $batchId,
        array $recipients,
        string $subject,
        int $adminId
    ): int {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO email_queue (batch_id, recipient, recipient_name, subject, body_html, body_plain, admin_id)
             VALUES (:batch, :email, :name, :subject, :html, :plain, :admin)'
        );

        $count = 0;
        foreach ($recipients as $r) {
            $email = trim((string) ($r['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $stmt->execute([
                'batch'   => $batchId,
                'email'   => $email,
                'name'    => (string) ($r['name'] ?? ''),
                'subject' => $subject,
                'html'    => (string) ($r['html'] ?? ''),
                'plain'   => (string) ($r['plain'] ?? ''),
                'admin'   => $adminId > 0 ? $adminId : null,
            ]);
            ++$count;
        }

        return $count;
    }

    /** @return array{processed:int,sent:int,failed:int,remaining:int,complete:bool} */
    public static function processQueueBatch(string $batchId, int $batchSize = 10): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $batchSize = max(1, min($batchSize, 25));

        $stmt = $pdo->prepare(
            "SELECT * FROM email_queue WHERE batch_id = :batch AND status = 'pending'
             ORDER BY id ASC LIMIT {$batchSize}"
        );
        $stmt->execute(['batch' => $batchId]);
        $rows = $stmt->fetchAll();

        require_once __DIR__ . '/MailService.php';

        $sent = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $ok = MailService::sendEmail(
                (string) $row['recipient'],
                (string) ($row['recipient_name'] ?: 'Subscriber'),
                (string) $row['subject'],
                (string) $row['body_html'],
                (string) $row['body_plain']
            );

            $status = $ok ? 'sent' : 'failed';
            $error = $ok ? null : (MailService::getLastError() ?? 'Send failed');

            $upd = $pdo->prepare(
                "UPDATE email_queue SET status = :status, attempts = attempts + 1,
                 error_msg = :error, processed_at = NOW() WHERE id = :id"
            );
            $upd->execute(['status' => $status, 'error' => $error, 'id' => (int) $row['id']]);

            self::logEmail(
                (string) $row['recipient'],
                (string) $row['subject'],
                (string) $row['body_html'],
                $ok ? 'sent' : 'failed',
                (int) ($row['admin_id'] ?? 0),
                $error
            );

            if ($ok) {
                ++$sent;
            } else {
                ++$failed;
            }
        }

        $remainingStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM email_queue WHERE batch_id = :batch AND status = 'pending'"
        );
        $remainingStmt->execute(['batch' => $batchId]);
        $remaining = (int) $remainingStmt->fetchColumn();

        return [
            'processed' => count($rows),
            'sent'      => $sent,
            'failed'    => $failed,
            'remaining' => $remaining,
            'complete'  => $remaining === 0,
        ];
    }

    /** @return array{total:int,pending:int,sent:int,failed:int} */
    public static function getQueueStats(string $batchId): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'pending') AS pending,
                    SUM(status = 'sent') AS sent,
                    SUM(status = 'failed') AS failed
             FROM email_queue WHERE batch_id = :batch"
        );
        $stmt->execute(['batch' => $batchId]);
        $row = $stmt->fetch() ?: [];

        return [
            'total'   => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'sent'    => (int) ($row['sent'] ?? 0),
            'failed'  => (int) ($row['failed'] ?? 0),
        ];
    }

    /** @return list<array{email:string,name:string,id?:int}> */
    public static function getPropertyOwners(?array $ids = null): array
    {
        $pdo = getDatabaseConnection();
        $sql = "SELECT id, owner_email AS email, owner_name AS name
                FROM properties
                WHERE owner_email IS NOT NULL AND owner_email != ''";
        $params = [];

        if ($ids !== null && $ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND id IN ({$placeholders})";
            $params = array_map('intval', $ids);
        }

        $sql .= ' ORDER BY owner_name ASC, owner_email ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $seen = [];
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $email = strtolower(trim((string) $row['email']));
            if ($email === '' || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;
            $out[] = [
                'id'    => (int) $row['id'],
                'email' => (string) $row['email'],
                'name'  => (string) ($row['name'] ?: 'Property Owner'),
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function getSubscribers(?string $status = 'active', ?array $ids = null): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $sql = 'SELECT id, email, name, status, source, subscribed_at FROM newsletter_subscribers WHERE 1=1';
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        if ($ids !== null && $ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND id IN ({$placeholders})";
            foreach ($ids as $id) {
                $params[] = (int) $id;
            }
        }

        $sql .= ' ORDER BY subscribed_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array{total:int,active:int,unsubscribed:int} */
    public static function getSubscriberStats(): array
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'active') AS active,
                    SUM(status = 'unsubscribed') AS unsubscribed
             FROM newsletter_subscribers"
        );
        $row = $stmt ? $stmt->fetch() : [];

        return [
            'total'          => (int) ($row['total'] ?? 0),
            'active'         => (int) ($row['active'] ?? 0),
            'unsubscribed'   => (int) ($row['unsubscribed'] ?? 0),
        ];
    }

    public static function addSubscriber(string $email, ?string $name = null, string $source = 'admin'): int
    {
        self::ensureTables();
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO newsletter_subscribers (email, name, source, status)
             VALUES (:email, :name, :source, \'active\')
             ON DUPLICATE KEY UPDATE name = COALESCE(VALUES(name), name), status = \'active\''
        );
        $stmt->execute(['email' => $email, 'name' => $name, 'source' => $source]);

        $idStmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = :email LIMIT 1');
        $idStmt->execute(['email' => $email]);

        return (int) $idStmt->fetchColumn();
    }

    public static function deleteSubscriber(int $id): bool
    {
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public static function updateSubscriberStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['active', 'unsubscribed'], true)) {
            return false;
        }
        self::ensureTables();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('UPDATE newsletter_subscribers SET status = :status WHERE id = :id');

        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
