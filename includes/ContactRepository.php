<?php
/**
 * Database operations for contact inquiries and admin replies.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

class ContactRepository
{
    /**
     * Store a new inquiry from the public contact form.
     *
     * @param array{full_name:string,email:string,phone?:string,inquiry_type:string,message:string,ip_address?:string,user_agent?:string} $data
     */
    public static function createInquiry(array $data): int
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO contact_inquiries
                (full_name, email, phone, inquiry_type, message, ip_address, user_agent, status)
             VALUES
                (:name, :email, :phone, :type, :message, :ip, :ua, \'new\')'
        );
        $stmt->execute([
            'name'    => $data['full_name'],
            'email'   => mb_strtolower($data['email']),
            'phone'   => $data['phone'] ?? null,
            'type'    => $data['inquiry_type'],
            'message' => $data['message'],
            'ip'      => $data['ip_address'] ?? null,
            'ua'      => $data['user_agent'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getAllInquiries(?string $status = null, ?string $search = null): array
    {
        $pdo = getDatabaseConnection();
        $sql = 'SELECT i.*,
                       (SELECT COUNT(*) FROM contact_replies r WHERE r.inquiry_id = i.id) AS reply_count,
                       (SELECT MAX(sent_at) FROM contact_replies r WHERE r.inquiry_id = i.id) AS last_reply_at
                FROM contact_inquiries i
                WHERE 1=1';
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= ' AND i.status = :status';
            $params['status'] = $status;
        }

        if ($search !== null && $search !== '') {
            $sql .= ' AND (i.full_name LIKE :q OR i.email LIKE :q OR i.message LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY i.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getInquiryById(int $id): ?array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT i.*,
                    (SELECT COUNT(*) FROM contact_replies r WHERE r.inquiry_id = i.id) AS reply_count
             FROM contact_inquiries i
             WHERE i.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getRepliesForInquiry(int $inquiryId): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT r.*, u.full_name AS admin_name, u.email AS admin_email
             FROM contact_replies r
             INNER JOIN admin_users u ON u.id = r.admin_id
             WHERE r.inquiry_id = :id
             ORDER BY r.sent_at ASC'
        );
        $stmt->execute(['id' => $inquiryId]);

        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $allowed = ['new', 'read', 'replied', 'archived'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'UPDATE contact_inquiries SET status = :status WHERE id = :id'
        );

        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function markAsRead(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'UPDATE contact_inquiries SET status = \'read\' WHERE id = :id AND status = \'new\''
        );
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        $check = $pdo->prepare('SELECT status FROM contact_inquiries WHERE id = :id');
        $check->execute(['id' => $id]);
        $row = $check->fetch();

        return $row && $row['status'] !== 'new';
    }

    public static function deleteInquiry(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM contact_inquiries WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Delete all inquiries with status read or archived.
     */
    public static function deleteReadInquiries(): int
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM contact_inquiries WHERE status IN (\'read\', \'archived\')'
        );
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * @return array{reply_id: int, mail_sent: bool}
     */
    public static function saveReply(
        int $inquiryId,
        int $adminId,
        string $subject,
        string $body,
        string $sentTo,
        bool $mailSent
    ): array {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO contact_replies
                (inquiry_id, admin_id, subject, body, sent_to, mail_sent)
             VALUES
                (:inquiry_id, :admin_id, :subject, :body, :sent_to, :mail_sent)'
        );
        $stmt->execute([
            'inquiry_id' => $inquiryId,
            'admin_id'   => $adminId,
            'subject'    => $subject,
            'body'       => $body,
            'sent_to'    => $sentTo,
            'mail_sent'  => $mailSent ? 1 : 0,
        ]);

        $pdo->prepare(
            'UPDATE contact_inquiries SET status = \'replied\' WHERE id = :id'
        )->execute(['id' => $inquiryId]);

        return [
            'reply_id'  => (int) $pdo->lastInsertId(),
            'mail_sent' => $mailSent,
        ];
    }

    public static function getStats(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT
                COUNT(*) AS total,
                SUM(status = \'new\') AS new_count,
                SUM(status = \'read\') AS read_count,
                SUM(status = \'replied\') AS replied_count,
                SUM(status = \'archived\') AS archived_count
             FROM contact_inquiries'
        );

        return $stmt->fetch() ?: [
            'total' => 0,
            'new_count' => 0,
            'read_count' => 0,
            'replied_count' => 0,
            'archived_count' => 0,
        ];
    }

    /**
     * @return list<array{label:string,count:int}>
     */
    public static function getMonthlyInquiryCounts(int $months = 6): array
    {
        $pdo = getDatabaseConnection();
        $months = max(1, min($months, 24));
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                    DATE_FORMAT(created_at, '%b') AS label,
                    COUNT(*) AS count
             FROM contact_inquiries
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
             GROUP BY month_key, label
             ORDER BY month_key ASC"
        );
        $stmt->execute(['months' => $months - 1]);

        return array_map(static fn (array $row): array => [
            'label' => (string) $row['label'],
            'count' => (int) $row['count'],
        ], $stmt->fetchAll());
    }
}
