<?php
/**
 * Admin API: Email Center — compose, templates, logs, queue.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__, 2) . '/includes/HtmlSanitizer.php';
require_once dirname(__DIR__, 2) . '/includes/EmailRepository.php';
require_once dirname(__DIR__, 2) . '/includes/AdminEmailService.php';
require_once dirname(__DIR__, 2) . '/includes/MailService.php';
require_once dirname(__DIR__, 2) . '/includes/AutomatedEmailService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$adminId = (int) ($_SESSION['admin_id'] ?? 0);

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
        $view = (string) ($_GET['view'] ?? 'meta');

        if ($view === 'templates') {
            jsonOk(['templates' => EmailRepository::getTemplates()]);
        }

        if ($view === 'logs') {
            $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
            $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
            $emailType = isset($_GET['email_type']) ? (string) $_GET['email_type'] : null;
            jsonOk([
                'logs'  => EmailRepository::getEmailLogs($status, $search, $emailType),
                'stats' => EmailRepository::getLogStats(),
            ]);
        }

        if ($view === 'preview_template') {
            $id = (int) ($_GET['id'] ?? 0);
            $tpl = $id > 0 ? EmailRepository::getTemplateById($id) : null;
            if (!$tpl) {
                jsonError('Template not found.', 404);
            }
            $inner = HtmlSanitizer::sanitizeEmailHtml((string) $tpl['body_html']);
            $sample = str_replace(
                ['{{customer_name}}', '{{owner_name}}', '{{name}}'],
                'Sample Customer',
                $inner
            );
            jsonOk([
                'preview_html' => AutomatedEmailService::wrapBranded($sample),
                'subject'      => (string) $tpl['subject'],
            ]);
        }

        if ($view === 'draft') {
            $draft = EmailRepository::getDraftForAdmin($adminId);
            jsonOk(['draft' => $draft ? formatDraft($draft) : null]);
        }

        if ($view === 'recipients') {
            $type = (string) ($_GET['type'] ?? '');
            if ($type === 'owners') {
                jsonOk(['owners' => EmailRepository::getPropertyOwners()]);
            }
            if ($type === 'subscribers') {
                jsonOk(['subscribers' => EmailRepository::getSubscribers('active')]);
            }
            jsonError('Invalid recipient type.');
        }

        if ($view === 'queue') {
            $batchId = trim((string) ($_GET['batch_id'] ?? ''));
            if ($batchId === '') {
                jsonError('batch_id required.');
            }
            jsonOk(['queue' => EmailRepository::getQueueStats($batchId)]);
        }

        jsonOk([
            'templates'    => EmailRepository::getTemplates(),
            'log_stats'    => EmailRepository::getLogStats(),
            'mail_status'  => MailService::getStatus(),
            'email_events' => AutomatedEmailService::eventLabels(),
            'csrf_token'   => AuthSecurity::generateCsrfToken(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        requireCsrf($body);
        $action = (string) ($body['action'] ?? '');

        if ($action === 'send') {
            $result = AdminEmailService::send($body, $adminId);
            jsonOk($result);
        }

        if ($action === 'save_draft') {
            $subject = trim((string) ($body['subject'] ?? ''));
            $bodyHtml = HtmlSanitizer::sanitizeEmailHtml((string) ($body['body_html'] ?? ''));
            $draftId = EmailRepository::saveDraft($adminId, [
                'recipient_type' => (string) ($body['recipient_type'] ?? 'single'),
                'recipients'     => $body['recipients'] ?? [],
                'subject'        => $subject,
                'body_html'      => $bodyHtml,
                'template_id'    => !empty($body['template_id']) ? (int) $body['template_id'] : null,
            ]);
            jsonOk(['message' => 'Draft saved.', 'draft_id' => $draftId]);
        }

        if ($action === 'process_queue') {
            $batchId = trim((string) ($body['batch_id'] ?? ''));
            $batchSize = (int) ($body['batch_size'] ?? 10);
            if ($batchId === '') {
                jsonError('batch_id required.');
            }
            $result = EmailRepository::processQueueBatch($batchId, $batchSize);
            $result['queue'] = EmailRepository::getQueueStats($batchId);
            jsonOk($result);
        }

        if ($action === 'create_template') {
            $name = trim((string) ($body['name'] ?? ''));
            $subject = trim((string) ($body['subject'] ?? ''));
            $bodyHtml = HtmlSanitizer::sanitizeEmailHtml((string) ($body['body_html'] ?? ''));
            if ($name === '' || $subject === '' || $bodyHtml === '') {
                jsonError('Name, subject, and body are required.');
            }
            $eventKey = trim((string) ($body['event_key'] ?? '')) ?: null;
            $desc = trim((string) ($body['description'] ?? '')) ?: null;
            $id = EmailRepository::createTemplate($name, $subject, $bodyHtml, $adminId, $eventKey, $desc);
            jsonOk(['message' => 'Template created.', 'id' => $id]);
        }

        if ($action === 'update_template') {
            $id = (int) ($body['id'] ?? 0);
            $name = trim((string) ($body['name'] ?? ''));
            $subject = trim((string) ($body['subject'] ?? ''));
            $bodyHtml = HtmlSanitizer::sanitizeEmailHtml((string) ($body['body_html'] ?? ''));
            if ($id <= 0 || $name === '' || $subject === '' || $bodyHtml === '') {
                jsonError('Invalid template data.');
            }
            $eventKey = trim((string) ($body['event_key'] ?? '')) ?: null;
            $desc = trim((string) ($body['description'] ?? '')) ?: null;
            EmailRepository::updateTemplate($id, $name, $subject, $bodyHtml, $eventKey, $desc);
            jsonOk(['message' => 'Template updated.']);
        }

        if ($action === 'assign_event') {
            $id = (int) ($body['id'] ?? 0);
            $eventKey = trim((string) ($body['event_key'] ?? ''));
            if ($id <= 0) {
                jsonError('Invalid template ID.');
            }
            EmailRepository::assignTemplateEvent($id, $eventKey !== '' ? $eventKey : null);
            jsonOk(['message' => 'Event assignment updated.']);
        }

        if ($action === 'resend_log') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid log ID.');
            }
            $ok = AutomatedEmailService::resendLog($id);
            if (!$ok) {
                jsonError(MailService::getLastError() ?? 'Resend failed.', 500);
            }
            jsonOk(['message' => 'Email resent successfully.']);
        }

        if ($action === 'delete_template') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid template ID.');
            }
            EmailRepository::deleteTemplate($id);
            jsonOk(['message' => 'Template deleted.']);
        }

        if ($action === 'duplicate_template') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                jsonError('Invalid template ID.');
            }
            $newId = EmailRepository::duplicateTemplate($id, $adminId);
            if ($newId === null) {
                jsonError('Template not found.', 404);
            }
            jsonOk(['message' => 'Template duplicated.', 'id' => $newId]);
        }

        jsonError('Unknown action.');
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage(), 400);
}

/** @param array<string, mixed> $draft @return array<string, mixed> */
function formatDraft(array $draft): array
{
    $recipients = json_decode((string) ($draft['recipients_json'] ?? '[]'), true);

    return [
        'id'             => (int) $draft['id'],
        'recipient_type' => (string) $draft['recipient_type'],
        'recipients'     => is_array($recipients) ? $recipients : [],
        'subject'        => (string) $draft['subject'],
        'body_html'      => (string) ($draft['body_html'] ?? ''),
        'template_id'    => !empty($draft['template_id']) ? (int) $draft['template_id'] : null,
        'updated_at'     => (string) ($draft['updated_at'] ?? ''),
    ];
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
