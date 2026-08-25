<?php
/**
 * Admin API: manage property transactions (used for receipts & certificates).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/includes/admin_api_guard.php';
require_once dirname(__DIR__, 2) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__, 2) . '/includes/TransactionRepository.php';
require_once dirname(__DIR__, 2) . '/includes/CustomerRepository.php';
require_once dirname(__DIR__, 2) . '/includes/PropertyRepository.php';
require_once dirname(__DIR__, 2) . '/includes/TransactionDocument.php';
require_once dirname(__DIR__, 2) . '/includes/PdfService.php';
require_once dirname(__DIR__, 2) . '/includes/MailService.php';
require_once dirname(__DIR__, 2) . '/includes/EmailRepository.php';

AdminPermissions::require(AdminPermissions::PERM_TRANSACTIONS);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        // Lightweight lookups so the page needs only the transactions permission.
        if (($_GET['lookup'] ?? '') === 'customers') {
            $rows = CustomerRepository::getAll(isset($_GET['q']) ? trim((string) $_GET['q']) : null, null, null, 200);
            jsonOk(['customers' => array_map(static fn ($c) => [
                'id'      => $c['id'],
                'name'    => $c['name'],
                'email'   => $c['email'],
                'phone'   => $c['phone'],
                'address' => trim($c['address'] . ' ' . $c['city'] . ' ' . $c['state']),
            ], $rows)]);
        }

        if (($_GET['lookup'] ?? '') === 'properties') {
            $rows = PropertyRepository::getAll(200, null, isset($_GET['q']) ? trim((string) $_GET['q']) : null);
            jsonOk(['properties' => array_map(static fn ($p) => [
                'id'       => $p['id'],
                'title'    => $p['title'],
                'location' => $p['location'],
                'price'    => $p['price'],
            ], $rows)]);
        }

        if (!empty($_GET['id'])) {
            $tx = TransactionRepository::getById((int) $_GET['id']);
            if ($tx === null) {
                jsonError('Transaction not found.', 404);
            }
            jsonOk(['transaction' => $tx]);
        }

        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $type   = isset($_GET['type']) ? (string) $_GET['type'] : null;
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;

        jsonOk([
            'transactions' => TransactionRepository::getAll($search, $type, $status),
            'stats'        => TransactionRepository::getStats(),
        ]);
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input') ?: '', true) ?? $_POST;
        $action = (string) ($body['action'] ?? 'save');

        if ($action === 'delete') {
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0 || !TransactionRepository::delete($id)) {
                jsonError('Transaction not found.', 404);
            }
            jsonOk(['message' => 'Transaction deleted.']);
        }

        if ($action === 'email') {
            $id = (int) ($body['id'] ?? 0);
            $tx = $id > 0 ? TransactionRepository::getById($id) : null;
            if ($tx === null) {
                jsonError('Transaction not found.', 404);
            }

            $docType = ($body['docType'] ?? 'receipt') === 'certificate' ? 'certificate' : 'receipt';
            $to = strtolower(trim((string) ($body['to'] ?? $tx['customerEmail'] ?? '')));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                jsonError('Please enter a valid recipient email address.');
            }

            $subject = trim((string) ($body['subject'] ?? '')) ?: TransactionDocument::defaultSubject($tx, $docType);
            $intro = trim((string) ($body['message'] ?? ''));

            $plain = TransactionDocument::plain($tx, $docType);
            $attachments = [];
            $html = TransactionDocument::email($tx, $docType, $intro);

            // Prefer attaching a pixel-perfect PDF; fall back to inline HTML if the engine is unavailable.
            if (PdfService::available()) {
                try {
                    $meta = TransactionDocument::pdfMeta($tx, $docType);
                    $pdfBytes = PdfService::render(
                        TransactionDocument::pdf($tx, $docType),
                        $meta['paper'],
                        $meta['orientation']
                    );
                    $attachments[] = [
                        'content' => $pdfBytes,
                        'name'    => $meta['filename'],
                        'type'    => 'application/pdf',
                    ];
                    $html = TransactionDocument::coverNote($tx, $docType, $intro);
                } catch (Throwable $pdfError) {
                    error_log('Transaction PDF render failed: ' . $pdfError->getMessage());
                }
            }

            $ok = MailService::sendEmail($to, (string) $tx['customerName'], $subject, $html, $plain, $attachments);

            EmailRepository::logEmail(
                $to,
                $subject,
                $html,
                $ok ? 'sent' : 'failed',
                (int) ($_SESSION['admin_id'] ?? 0),
                $ok ? null : (MailService::getLastError() ?? 'Send failed')
            );

            if (!$ok) {
                jsonError('Email failed: ' . (MailService::getLastError() ?? 'Unknown error'), 502);
            }

            $label = $docType === 'certificate' ? 'Certificate' : 'Receipt';
            jsonOk(['message' => $label . ' emailed to ' . $to . '.']);
        }

        if (trim((string) ($body['customerName'] ?? '')) === '') {
            jsonError('Customer name is required.');
        }
        if ((int) preg_replace('/[^\d]/', '', (string) ($body['amount'] ?? '0')) <= 0) {
            jsonError('Please enter a valid amount.');
        }

        $email = trim((string) ($body['customerEmail'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('Please enter a valid customer email address.');
        }

        // Stamp the issuing admin if not provided.
        if (trim((string) ($body['issuedBy'] ?? '')) === '') {
            $body['issuedBy'] = (string) ($_SESSION['admin_name'] ?? 'Administrator');
        }

        $id = (int) ($body['id'] ?? 0);
        if ($id > 0) {
            TransactionRepository::update($id, $body);
            jsonOk(['message' => 'Transaction updated.', 'transaction' => TransactionRepository::getById($id)]);
        }

        $newId = TransactionRepository::create($body);
        jsonOk(['message' => 'Transaction recorded.', 'transaction' => TransactionRepository::getById($newId)]);
    }

    jsonError('Method not allowed.', 405);
} catch (Throwable $e) {
    jsonError($e->getMessage() ?: 'Request failed.', 400);
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
