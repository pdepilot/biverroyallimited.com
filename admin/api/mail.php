<?php
/**
 * Admin API: email configuration and test sends.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__, 2) . '/includes/MailConfigService.php';
require_once dirname(__DIR__, 2) . '/includes/MailService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        jsonOk([
            'mail'       => MailService::getStatus(),
            'providers'  => MailConfigService::providers(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? '');

        if ($action === 'save') {
            MailConfigService::save([
                'provider'        => $body['provider'] ?? 'gmail',
                'useSmtp'         => $body['useSmtp'] ?? '1',
                'host'            => $body['host'] ?? '',
                'port'            => $body['port'] ?? 587,
                'encryption'      => $body['encryption'] ?? 'tls',
                'username'        => $body['username'] ?? '',
                'password'        => $body['password'] ?? '',
                'fromEmail'       => $body['fromEmail'] ?? '',
                'fromName'        => $body['fromName'] ?? '',
                'replyTo'         => $body['replyTo'] ?? '',
                'notifyEmail'     => $body['notifyEmail'] ?? '',
                'notifyOnContact' => $body['notifyOnContact'] ?? '1',
                'timeout'         => $body['timeout'] ?? 30,
            ]);

            jsonOk([
                'message' => 'Email settings saved.',
                'mail'    => MailService::getStatus(),
            ]);
        }

        if ($action === 'test') {
            $toEmail = trim((string) ($body['email'] ?? ''));
            if ($toEmail === '') {
                $toEmail = (string) (AuthSecurity::getCurrentAdmin()['email'] ?? '');
            }
            if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                jsonError('Enter a valid test email address.');
            }

            $sent = MailService::sendTestEmail($toEmail, (string) ($_SESSION['admin_name'] ?? 'Admin'));
            if (!$sent) {
                jsonError(MailService::getLastError() ?? 'Test email failed.', 500);
            }

            jsonOk([
                'message' => 'Test email sent to ' . $toEmail . '. Check inbox and spam folder.',
                'mail'    => MailService::getStatus(),
            ]);
        }

        jsonError('Unknown action.');
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
