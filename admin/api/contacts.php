<?php
/**
 * Admin API: manage contact inquiries (list, read, reply, delete).
 * Requires active PHP admin session.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/ContactRepository.php';
require_once dirname(__DIR__, 2) . '/includes/MailService.php';

$method = $_SERVER['REQUEST_METHOD'];
$adminId = (int) ($_SESSION['admin_id'] ?? 0);

try {
    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            $id = (int) $_GET['id'];
            $inquiry = ContactRepository::getInquiryById($id);
            if (!$inquiry) {
                jsonError('Inquiry not found.', 404);
            }
            $inquiry['replies'] = ContactRepository::getRepliesForInquiry($id);
            jsonOk(['inquiry' => formatInquiry($inquiry), 'replies' => $inquiry['replies']]);
        }

        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $list = ContactRepository::getAllInquiries($status, $search);

        jsonOk([
            'contacts' => array_map('formatInquiry', $list),
            'stats'    => ContactRepository::getStats(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? '');

        if ($action === 'mark_read') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid inquiry ID.');
            }
            ContactRepository::markAsRead($id);
            jsonOk(['message' => 'Marked as read.']);
        }

        if ($action === 'update_status') {
            $id = (int) ($body['id'] ?? 0);
            $status = (string) ($body['status'] ?? '');
            if ($id <= 0 || !ContactRepository::updateStatus($id, $status)) {
                jsonError('Invalid status update.');
            }
            jsonOk(['message' => 'Status updated.']);
        }

        if ($action === 'reply') {
            $id = (int) ($body['inquiry_id'] ?? $body['id'] ?? 0);
            $subject = trim((string) ($body['subject'] ?? ''));
            $replyBody = trim((string) ($body['message'] ?? $body['body'] ?? ''));

            if ($id <= 0 || $subject === '' || $replyBody === '') {
                jsonError('Subject and message are required.');
            }

            $inquiry = ContactRepository::getInquiryById($id);
            if (!$inquiry) {
                jsonError('Inquiry not found.', 404);
            }

            $mailSent = MailService::sendInquiryReply(
                $inquiry['email'],
                $inquiry['full_name'],
                $subject,
                $replyBody,
                $inquiry['message']
            );

            $result = ContactRepository::saveReply(
                $id,
                $adminId,
                $subject,
                $replyBody,
                $inquiry['email'],
                $mailSent
            );

            jsonOk([
                'message'    => $mailSent
                    ? 'Reply sent successfully via email.'
                    : self::mailFailureMessage(),
                'mail_sent'  => $mailSent,
                'mail_error' => $mailSent ? null : MailService::getLastError(),
                'reply_id'   => $result['reply_id'],
            ]);
        }

        if ($action === 'compose') {
            $toEmail = trim((string) ($body['email'] ?? ''));
            $toName = trim((string) ($body['name'] ?? 'Customer'));
            $subject = trim((string) ($body['subject'] ?? ''));
            $replyBody = trim((string) ($body['message'] ?? ''));

            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || $subject === '' || $replyBody === '') {
                jsonError('Valid email, subject, and message are required.');
            }

            $mailSent = MailService::sendInquiryReply(
                $toEmail,
                $toName,
                $subject,
                $replyBody,
                '(New message from admin — no prior inquiry)'
            );

            jsonOk([
                'message'    => $mailSent ? 'Email sent successfully.' : self::mailFailureMessage(),
                'mail_sent'  => $mailSent,
                'mail_error' => $mailSent ? null : MailService::getLastError(),
            ]);
        }

        jsonError('Unknown action.');
    }

    if ($method === 'DELETE') {
        if (!empty($_GET['bulk']) && $_GET['bulk'] === 'read') {
            $count = ContactRepository::deleteReadInquiries();
            jsonOk(['message' => "Deleted {$count} read/archived message(s).", 'deleted' => $count]);
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Invalid inquiry ID.');
        }
        ContactRepository::deleteInquiry($id);
        jsonOk(['message' => 'Message deleted.']);
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
} catch (Throwable $e) {
    error_log('Admin contacts API: ' . $e->getMessage());
    jsonError('Server error. Please try again.', 500);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function formatInquiry(array $row): array
{
    return [
        'id'           => (int) $row['id'],
        '_id'          => (string) $row['id'],
        'name'         => $row['full_name'],
        'full_name'    => $row['full_name'],
        'email'        => $row['email'],
        'phone'        => $row['phone'],
        'inquiryType'  => $row['inquiry_type'],
        'inquiry_type' => $row['inquiry_type'],
        'message'      => $row['message'],
        'status'       => $row['status'],
        'isRead'       => in_array($row['status'], ['read', 'replied', 'archived'], true),
        'reply_count'  => (int) ($row['reply_count'] ?? 0),
        'last_reply_at'=> $row['last_reply_at'] ?? null,
        'createdAt'    => $row['created_at'],
        'created_at'   => $row['created_at'],
        'updated_at'   => $row['updated_at'],
    ];
}

function jsonOk(array $data): void
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function mailFailureMessage(): string
{
    $detail = MailService::getLastError();
    $base   = 'Reply saved but email could not be sent.';

    if ($detail) {
        return $base . ' ' . $detail;
    }

    if (!is_readable(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
        return $base . ' Run composer install and configure config/mail.local.php';
    }

    return $base . ' Set SMTP credentials in config/mail.local.php (see mail.local.php.example).';
}
