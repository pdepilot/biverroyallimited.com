<?php
/**
 * Admin email composition, validation, and delivery orchestration.
 */
declare(strict_types=1);

require_once __DIR__ . '/EmailRepository.php';
require_once __DIR__ . '/HtmlSanitizer.php';
require_once __DIR__ . '/MailService.php';

final class AdminEmailService
{
    private const BULK_THRESHOLD = 3;

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function send(array $input, int $adminId): array
    {
        $recipientType = (string) ($input['recipient_type'] ?? 'single');
        $subject = trim((string) ($input['subject'] ?? ''));
        $bodyHtml = HtmlSanitizer::sanitizeEmailHtml((string) ($input['body_html'] ?? $input['message'] ?? ''));
        $bodyPlain = HtmlSanitizer::htmlToPlain($bodyHtml);

        if ($subject === '') {
            throw new InvalidArgumentException('Subject is required.');
        }
        if ($bodyHtml === '') {
            throw new InvalidArgumentException('Message body is required.');
        }

        $recipients = self::resolveRecipients($recipientType, $input);

        if ($recipients === []) {
            throw new InvalidArgumentException('No valid recipients found.');
        }

        foreach ($recipients as &$r) {
            $inner = self::personalize($bodyHtml, (string) ($r['name'] ?? ''));
            $r['html'] = self::wrapEmailTemplate($inner);
            $r['plain'] = HtmlSanitizer::htmlToPlain($inner);
        }
        unset($r);

        if (count($recipients) >= self::BULK_THRESHOLD) {
            return self::queueBulk($recipients, $subject, $adminId);
        }

        return self::sendImmediate($recipients, $subject, $adminId);
    }

    /**
     * @param array<string, mixed> $input
     * @return list<array{email:string,name:string}>
     */
    private static function resolveRecipients(string $type, array $input): array
    {
        switch ($type) {
            case 'single':
                $email = strtolower(trim((string) ($input['email'] ?? '')));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Enter a valid email address.');
                }

                return [['email' => $email, 'name' => (string) ($input['name'] ?? 'Recipient')]];

            case 'multiple':
                $raw = (string) ($input['emails'] ?? $input['recipients'] ?? '');
                if (is_array($input['emails'] ?? null)) {
                    $lines = $input['emails'];
                } else {
                    $lines = preg_split('/[\s,;]+/', $raw) ?: [];
                }
                $out = [];
                $seen = [];
                foreach ($lines as $line) {
                    $email = strtolower(trim((string) $line));
                    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                        continue;
                    }
                    $seen[$email] = true;
                    $out[] = ['email' => $email, 'name' => 'Recipient'];
                }

                return $out;

            case 'subscribers':
                $all = filter_var($input['send_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $ids = $input['subscriber_ids'] ?? null;
                if ($all) {
                    $rows = EmailRepository::getSubscribers('active');
                } elseif (is_array($ids) && $ids !== []) {
                    $rows = EmailRepository::getSubscribers('active', array_map('intval', $ids));
                } else {
                    throw new InvalidArgumentException('Select subscribers or choose send to all.');
                }

                return array_map(static fn (array $r): array => [
                    'email' => (string) $r['email'],
                    'name'  => (string) ($r['name'] ?: 'Subscriber'),
                ], $rows);

            case 'owners':
                $all = filter_var($input['send_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $ids = $input['owner_ids'] ?? $input['property_ids'] ?? null;
                if ($all) {
                    $rows = EmailRepository::getPropertyOwners();
                } elseif (is_array($ids) && $ids !== []) {
                    $rows = EmailRepository::getPropertyOwners(array_map('intval', $ids));
                } else {
                    throw new InvalidArgumentException('Select property owners or choose send to all.');
                }

                return array_map(static fn (array $r): array => [
                    'email' => (string) $r['email'],
                    'name'  => (string) ($r['name'] ?: 'Property Owner'),
                ], $rows);

            default:
                throw new InvalidArgumentException('Invalid recipient type.');
        }
    }

    /**
     * @param list<array{email:string,name:string,html:string,plain:string}> $recipients
     * @return array<string, mixed>
     */
    private static function sendImmediate(array $recipients, string $subject, int $adminId): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $r) {
            $ok = MailService::sendEmail($r['email'], $r['name'], $subject, $r['html'], $r['plain']);
            EmailRepository::logEmail(
                $r['email'],
                $subject,
                $r['html'],
                $ok ? 'sent' : 'failed',
                $adminId,
                $ok ? null : (MailService::getLastError() ?? 'Send failed')
            );

            if ($ok) {
                ++$sent;
            } else {
                ++$failed;
                $errors[] = $r['email'] . ': ' . (MailService::getLastError() ?? 'failed');
            }
        }

        return [
            'mode'    => 'immediate',
            'sent'    => $sent,
            'failed'  => $failed,
            'total'   => count($recipients),
            'errors'  => $errors,
            'message' => $failed === 0
                ? "Email sent to {$sent} recipient(s)."
                : "Sent {$sent}, failed {$failed}.",
        ];
    }

    /**
     * @param list<array{email:string,name:string,html:string,plain:string}> $recipients
     * @return array<string, mixed>
     */
    private static function queueBulk(array $recipients, string $subject, int $adminId): array
    {
        $batchId = 'batch_' . bin2hex(random_bytes(12));

        $queued = EmailRepository::enqueueBatch($batchId, $recipients, $subject, $adminId);

        if ($queued === 0) {
            throw new InvalidArgumentException('No valid emails queued.');
        }

        return [
            'mode'     => 'queued',
            'batch_id' => $batchId,
            'queued'   => $queued,
            'message'  => "{$queued} email(s) queued for delivery.",
        ];
    }

    public static function personalize(string $html, string $name): string
    {
        $safeName = htmlspecialchars($name !== '' ? $name : 'Valued Customer', ENT_QUOTES, 'UTF-8');

        return str_replace(['{{name}}', '{{NAME}}'], $safeName, $html);
    }

    public static function wrapEmailTemplate(string $innerHtml): string
    {
        require_once __DIR__ . '/AutomatedEmailService.php';

        return AutomatedEmailService::wrapBranded($innerHtml);
    }
}
