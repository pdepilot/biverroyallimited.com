<?php
/**
 * Admin API: newsletter subscribers management + country breakdown.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AuthSecurity.php';
require_once dirname(__DIR__, 2) . '/includes/EmailRepository.php';
require_once dirname(__DIR__, 2) . '/includes/AutomatedEmailService.php';
require_once dirname(__DIR__, 2) . '/includes/GeoIpService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $search = trim((string) ($_GET['search'] ?? ''));
        $country = trim((string) ($_GET['country'] ?? ''));
        $list = EmailRepository::getSubscribers(
            $status !== '' ? $status : null,
            null,
            $country !== '' ? $country : null
        );

        if ($search !== '') {
            $q = strtolower($search);
            $list = array_values(array_filter($list, static function (array $row) use ($q): bool {
                return str_contains(strtolower((string) $row['email']), $q)
                    || str_contains(strtolower((string) ($row['name'] ?? '')), $q)
                    || str_contains(strtolower((string) ($row['country_name'] ?? '')), $q)
                    || str_contains(strtolower((string) ($row['country_code'] ?? '')), $q);
            }));
        }

        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="newsletter-subscribers.csv"');
            $out = fopen('php://output', 'w');
            if ($out) {
                fputcsv($out, ['Email', 'Name', 'Status', 'Source', 'Country', 'Country code', 'IP', 'Timezone', 'Subscribed at', 'Subscriber local time']);
                foreach ($list as $row) {
                    fputcsv($out, [
                        $row['email'] ?? '',
                        $row['name'] ?? '',
                        $row['status'] ?? '',
                        $row['source'] ?? '',
                        $row['country_name'] ?? '',
                        $row['country_code'] ?? '',
                        $row['ip_address'] ?? '',
                        $row['timezone'] ?? '',
                        $row['subscribed_at_label'] ?? ($row['subscribed_at'] ?? ''),
                        $row['subscribed_local_label'] ?? '',
                    ]);
                }
                fclose($out);
            }
            exit;
        }

        jsonOk([
            'subscribers' => $list,
            'stats'       => EmailRepository::getSubscriberStats(),
            'countries'   => EmailRepository::getSubscriberCountryStats(),
            'csrf_token'  => AuthSecurity::generateCsrfToken(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        requireCsrf($body);
        $action = (string) ($body['action'] ?? '');

        if ($action === 'add') {
            $email = trim((string) ($body['email'] ?? ''));
            $name = trim((string) ($body['name'] ?? ''));
            $geo = GeoIpService::lookup();
            $id = EmailRepository::addSubscriber(
                $email,
                $name !== '' ? $name : null,
                'admin',
                $geo['country_code'] ?? null,
                $geo['country_name'] ?? null,
                $geo['ip'] ?? null,
                date_default_timezone_get() ?: 'UTC'
            );
            AutomatedEmailService::onNewsletterSubscribed($email, $name !== '' ? $name : null);
            jsonOk(['message' => 'Subscriber added and welcome email sent.', 'id' => $id]);
        }

        if ($action === 'update_status') {
            $id = (int) ($body['id'] ?? 0);
            $status = (string) ($body['status'] ?? '');
            if ($id <= 0 || !EmailRepository::updateSubscriberStatus($id, $status)) {
                jsonError('Invalid status update.');
            }
            jsonOk(['message' => 'Subscriber updated.']);
        }

        jsonError('Unknown action.');
    }

    if ($method === 'DELETE') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_GET;
        requireCsrf($body);
        $id = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Invalid subscriber ID.');
        }
        EmailRepository::deleteSubscriber($id);
        jsonOk(['message' => 'Subscriber removed.']);
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
