<?php

/**
 * Send transactional email via PHPMailer (SMTP) with PHP mail() fallback.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/MailConfigService.php';
require_once __DIR__ . '/AutomatedEmailService.php';

class MailService
{
    /** @var string|null Last error message for admin feedback */
    private static ?string $lastError = null;

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    /** @return array<string, mixed> */
    public static function getStatus(): array
    {
        return MailConfigService::getPublic();
    }

    /**
     * Send HTML reply email to a customer (admin inquiry reply).
     */
    public static function sendInquiryReply(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        string $originalMessage
    ): bool {
        self::$lastError = null;

        $htmlBody  = self::buildReplyTemplate($toName, $body, $originalMessage);
        $plainBody = self::htmlToPlain($body) . "\n\n---\nYour original message:\n" . self::htmlToPlain($originalMessage);

        return self::sendEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);
    }

    /**
     * Notify site admin when a new contact form inquiry is received.
     *
     * @param array{full_name:string,email:string,phone?:string,inquiry_type:string,message:string,id?:int} $inquiry
     */
    public static function sendNewInquiryNotification(array $inquiry): bool
    {
        self::$lastError = null;

        $config = MailConfigService::get();
        if (empty($config['notifyOnContact'])) {
            return false;
        }

        $notifyEmail = trim((string) ($config['notifyEmail'] ?? ''));
        if ($notifyEmail === '' || !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Notification email is not configured.';
            return false;
        }

        $name    = (string) ($inquiry['full_name'] ?? 'Visitor');
        $email   = (string) ($inquiry['email'] ?? '');
        $phone   = (string) ($inquiry['phone'] ?? 'Not provided');
        $type    = (string) ($inquiry['inquiry_type'] ?? 'general');
        $message = (string) ($inquiry['message'] ?? '');
        $id      = (int) ($inquiry['id'] ?? 0);

        $subject = 'New contact inquiry — ' . $name . ' (' . $type . ')';
        $htmlBody = self::buildInquiryNotificationTemplate($name, $email, $phone, $type, $message, $id);
        $plainBody = "New contact inquiry\n\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\nType: {$type}\n\nMessage:\n{$message}";

        return self::sendEmail($notifyEmail, 'Biver Royalty Admin', $subject, $htmlBody, $plainBody);
    }

    /**
     * Send a test message to verify SMTP settings.
     */
    public static function sendTestEmail(string $toEmail, string $toName = 'Admin'): bool
    {
        self::$lastError = null;

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid test email address.';
            return false;
        }

        $config = MailConfigService::getPublic();
        $subject = 'Biver Royalty Homes — Mail Test';
        $sentAt = date('Y-m-d H:i:s');
        $providerLabel = htmlspecialchars((string) $config['provider'], ENT_QUOTES, 'UTF-8');
        $inner = <<<HTML
<p style="margin:0 0 16px;font-size:16px;">Hello,</p>
<p style="margin:0 0 16px;">This is a test email from <strong>Biver Royalty Homes</strong> confirming that your mail configuration is working correctly.</p>
<p style="margin:0 0 16px;">Provider: <strong>{$providerLabel}</strong></p>
<p style="margin:0;font-size:13px;color:#6c5e4e;">Sent at {$sentAt}</p>
HTML;
        $htmlBody = AutomatedEmailService::wrapBranded($inner);

        $plainBody = 'Mail configuration OK. This is a test email from Biver Royalty Homes.';

        return self::sendEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);
    }

    /**
     * @param list<array{content:string,name:string,type?:string}> $attachments
     */
    public static function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array $attachments = []
    ): bool {
        self::$lastError = null;

        $config = MailConfigService::get();

        if (!empty($config['useSmtp'])) {
            if (!MailConfigService::isReady()) {
                self::$lastError = self::buildNotReadyMessage($config);
                error_log('MailService: ' . self::$lastError);
                return false;
            }

            return self::sendViaSmtp($toEmail, $toName, $subject, $htmlBody, $plainBody, $config, $attachments);
        }

        return self::sendViaPhpMail($toEmail, $subject, $htmlBody, $plainBody, $config, $attachments);
    }

    /**
     * Test SMTP connectivity and return a safe diagnostic report for admins.
     *
     * @return array<string, mixed>
     */
    public static function diagnoseSmtp(): array
    {
        $config = MailConfigService::get();
        $public = MailConfigService::getPublic();
        $localPath = MailConfigService::localPath();
        $vendorPath = dirname(__DIR__) . '/vendor/autoload.php';

        $report = [
            'provider'          => $public['provider'] ?? '',
            'use_smtp'          => (bool) ($public['useSmtp'] ?? false),
            'host'              => $public['host'] ?? '',
            'port'              => (int) ($public['port'] ?? 0),
            'encryption'        => $public['encryption'] ?? '',
            'username'          => $public['username'] ?? '',
            'from_email'        => $public['fromEmail'] ?? '',
            'password_set'      => (bool) ($public['passwordSet'] ?? false),
            'composer_installed'=> is_readable($vendorPath),
            'mail_local_exists' => is_readable($localPath),
            'is_ready'          => MailConfigService::isReady(),
            'checks'            => [],
            'attempts'          => [],
            'recommendation'    => '',
        ];

        if (!$report['use_smtp']) {
            $report['checks'][] = ['ok' => false, 'label' => 'SMTP disabled', 'detail' => 'Enable “Use SMTP” in SMTP Settings.'];
            $report['recommendation'] = 'Turn on SMTP and save Hostinger mailbox credentials.';
            return $report;
        }

        if (!$report['mail_local_exists']) {
            $report['checks'][] = [
                'ok'     => false,
                'label'  => 'config/mail.local.php missing on server',
                'detail' => 'Upload mail.local.php or save SMTP settings in Admin → SMTP Settings.',
            ];
        } else {
            $report['checks'][] = ['ok' => true, 'label' => 'config/mail.local.php found'];
        }

        if (!$report['composer_installed']) {
            $report['checks'][] = [
                'ok'     => false,
                'label'  => 'Composer vendor/ missing',
                'detail' => 'Run composer install on the server (SSH) or upload the vendor folder.',
            ];
            $report['recommendation'] = 'Run composer install on Hostinger so PHPMailer can send via SMTP.';
            return $report;
        }

        if (!$report['password_set']) {
            $report['checks'][] = ['ok' => false, 'label' => 'SMTP password not saved', 'detail' => 'Re-enter the mailbox password and save.'];
        }

        if (($report['username'] ?? '') === '' || ($report['from_email'] ?? '') === '') {
            $report['checks'][] = ['ok' => false, 'label' => 'Username or From email empty'];
        }

        if (
            ($report['username'] ?? '') !== ''
            && ($report['from_email'] ?? '') !== ''
            && strcasecmp((string) $report['username'], (string) $report['from_email']) !== 0
        ) {
            $report['checks'][] = [
                'ok'     => false,
                'label'  => 'From email differs from SMTP username',
                'detail' => 'For Hostinger, both should be the same mailbox (e.g. agent@biverroyaltyhomesltd.com).',
            ];
        }

        require_once $vendorPath;

        foreach (self::smtpProfiles($config) as $profile) {
            $attempt = self::testSmtpProfile($config, $profile);
            $report['attempts'][] = $attempt;
            if ($attempt['connected']) {
                $report['checks'][] = [
                    'ok'     => true,
                    'label'  => 'SMTP connection OK',
                    'detail' => $profile['label'],
                ];
                $report['recommendation'] = 'Connection works with ' . $profile['label'] . '. If sends still fail, check spam folder or reset mailbox password in hPanel.';
                return $report;
            }
        }

        $last = $report['attempts'][count($report['attempts']) - 1] ?? null;
        $report['checks'][] = [
            'ok'     => false,
            'label'  => 'SMTP connection failed',
            'detail' => $last['error'] ?? 'Unknown error',
        ];
        $report['recommendation'] = self::diagnoseRecommendation($config, $last['error'] ?? '');

        return $report;
    }

    /** @param array<string, mixed> $config */
    private static function buildNotReadyMessage(array $config): string
    {
        $parts = ['SMTP is not fully configured on this server.'];
        if (!is_readable(MailConfigService::localPath())) {
            $parts[] = 'Missing config/mail.local.php — upload it or save settings in Admin → SMTP Settings.';
        }
        if (($config['password'] ?? '') === '') {
            $parts[] = 'SMTP password is empty.';
        }
        if (!is_readable(dirname(__DIR__) . '/vendor/autoload.php')) {
            $parts[] = 'Run composer install (vendor/ folder missing).';
        }

        return implode(' ', $parts);
    }

    /**
     * Hostinger and similar hosts often need a second attempt on port 587/TLS.
     *
     * @param array<string, mixed> $config
     * @return list<array{label:string,port:int,encryption:string}>
     */
    private static function smtpProfiles(array $config): array
    {
        $profiles = [[
            'label'      => (string) ($config['host'] ?? 'smtp') . ':' . (int) ($config['port'] ?? 587) . ' ' . strtoupper((string) ($config['encryption'] ?? 'tls')),
            'port'       => (int) ($config['port'] ?? 587),
            'encryption' => strtolower((string) ($config['encryption'] ?? 'tls')),
        ]];

        $provider = (string) ($config['provider'] ?? '');
        $host = strtolower((string) ($config['host'] ?? ''));
        $isHostinger = $provider === 'hostinger' || str_contains($host, 'hostinger');

        if ($isHostinger) {
            $currentPort = (int) ($config['port'] ?? 465);
            $currentEnc = strtolower((string) ($config['encryption'] ?? 'ssl'));

            if ($currentPort !== 587 || $currentEnc !== 'tls') {
                $profiles[] = [
                    'label'      => 'smtp.hostinger.com:587 TLS (fallback)',
                    'port'       => 587,
                    'encryption' => 'tls',
                ];
            }
            if ($currentPort !== 465 || $currentEnc !== 'ssl') {
                $profiles[] = [
                    'label'      => 'smtp.hostinger.com:465 SSL (fallback)',
                    'port'       => 465,
                    'encryption' => 'ssl',
                ];
            }
        }

        return $profiles;
    }

    /**
     * @param array<string, mixed> $config
     * @param array{label:string,port:int,encryption:string} $profile
     * @return array{label:string,port:int,encryption:string,connected:bool,error:string}
     */
    private static function testSmtpProfile(array $config, array $profile): array
    {
        $mail = new PHPMailer(true);
        $debugLog = [];

        try {
            self::applySmtpConfig($mail, $config, $profile['port'], $profile['encryption']);
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = static function (string $str) use (&$debugLog): void {
                $debugLog[] = trim($str);
            };

            if (!$mail->smtpConnect()) {
                throw new PhpMailerException($mail->ErrorInfo ?: 'smtpConnect() returned false');
            }

            $mail->smtpClose();

            return [
                'label'      => $profile['label'],
                'port'       => $profile['port'],
                'encryption' => $profile['encryption'],
                'connected'  => true,
                'error'      => '',
            ];
        } catch (PhpMailerException $e) {
            return [
                'label'      => $profile['label'],
                'port'       => $profile['port'],
                'encryption' => $profile['encryption'],
                'connected'  => false,
                'error'      => self::formatSmtpError($mail->ErrorInfo ?: $e->getMessage(), $config),
                'debug_tail' => array_slice($debugLog, -6),
            ];
        }
    }

    /** @param array<string, mixed> $config */
    private static function diagnoseRecommendation(array $config, string $error): string
    {
        $errorLower = strtolower($error);
        if (str_contains($errorLower, 'authenticate')) {
            return 'Reset the mailbox password in hPanel → Emails → Manage, then paste the new password in SMTP Settings and save.';
        }
        if (str_contains($errorLower, 'connect') || str_contains($errorLower, 'timed out')) {
            return 'Try port 587 with TLS, or confirm Hostinger allows outbound SMTP from your hosting plan.';
        }

        return 'Open Email Center → Email Logs and check the error_msg column for failed sends.';
    }

    /**
     * @param array<string, mixed> $config
     * @param list<array{content:string,name:string,type?:string}> $attachments
     */
    private static function sendViaSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array $config,
        array $attachments = []
    ): bool {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';

        if (!is_readable($autoload)) {
            self::$lastError = 'Composer dependencies missing. Run: composer install on the server and upload vendor/.';
            error_log('MailService: vendor/autoload.php not found');
            return false;
        }

        require_once $autoload;

        $fromEmail = trim((string) $config['fromEmail']);
        $fromName  = (string) $config['fromName'];
        $replyTo   = trim((string) $config['replyTo']);
        $username  = trim((string) $config['username']);

        if ($fromEmail === '') {
            $fromEmail = $username;
        }

        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'From email is not configured.';
            return false;
        }

        $errors = [];

        foreach (self::smtpProfiles($config) as $profile) {
            $mail = new PHPMailer(true);

            try {
                self::applySmtpConfig($mail, $config, $profile['port'], $profile['encryption']);
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($toEmail, $toName);
                $mail->addReplyTo($replyTo !== '' ? $replyTo : $fromEmail, $fromName);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = $plainBody;

                foreach ($attachments as $attachment) {
                    if (($attachment['content'] ?? '') === '') {
                        continue;
                    }
                    $mail->addStringAttachment(
                        $attachment['content'],
                        $attachment['name'] ?? 'attachment.pdf',
                        PHPMailer::ENCODING_BASE64,
                        $attachment['type'] ?? 'application/pdf'
                    );
                }

                $mail->send();
                return true;
            } catch (PhpMailerException $e) {
                $msg = self::formatSmtpError($mail->ErrorInfo ?: $e->getMessage(), $config);
                $errors[] = $profile['label'] . ': ' . $msg;
                error_log('MailService SMTP attempt failed (' . $profile['label'] . '): ' . $msg);
            }
        }

        self::$lastError = $errors !== [] ? end($errors) : 'SMTP send failed.';
        return false;
    }

    /** @param array<string, mixed> $config */
    private static function applySmtpConfig(
        PHPMailer $mail,
        array $config,
        ?int $portOverride = null,
        ?string $encryptionOverride = null
    ): void {
        $mail->isSMTP();
        $mail->Host       = (string) $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) $config['username'];
        $mail->Password   = (string) $config['password'];
        $mail->Port       = $portOverride ?? (int) $config['port'];
        $mail->Timeout    = (int) ($config['timeout'] ?? 30);
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;
        $mail->SMTPKeepAlive = false;

        $encryption = strtolower($encryptionOverride ?? (string) ($config['encryption'] ?? 'tls'));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ];
    }

    /** @param array<string, mixed> $config */
    private static function formatSmtpError(string $error, array $config): string
    {
        $error = trim($error);
        if (stripos($error, 'authenticate') === false && stripos($error, 'authentication') === false) {
            return $error;
        }

        $provider = (string) ($config['provider'] ?? 'custom');
        $username = (string) ($config['username'] ?? '');
        $fromEmail = (string) ($config['fromEmail'] ?? '');

        $hints = ['SMTP authentication failed.'];

        if ($provider === 'gmail') {
            $hints[] = 'Gmail requires a 16-character App Password (not your normal Gmail password).';
            $hints[] = 'Create one at: https://myaccount.google.com/apppasswords';
            $hints[] = 'SMTP username must be your full Gmail address.';
            if ($fromEmail !== '' && strcasecmp($fromEmail, $username) !== 0) {
                $hints[] = 'From email should match the Gmail account, unless "Send mail as" is configured in Gmail.';
            }
        } elseif ($provider === 'hostinger' || str_contains((string) ($config['host'] ?? ''), 'hostinger')) {
            $hints[] = 'Use your full Hostinger mailbox email as SMTP username (e.g. agent@yourdomain.com).';
            $hints[] = 'Use the mailbox password from hPanel → Emails.';
            $hints[] = 'Host: smtp.hostinger.com, Port: 465, Encryption: SSL.';
        } else {
            $hints[] = 'Verify SMTP username, password, host, port, and encryption match your email provider.';
            if ($fromEmail !== '' && $username !== '' && strcasecmp($fromEmail, $username) !== 0) {
                $hints[] = 'From email and SMTP username should usually be the same mailbox.';
            }
        }

        return $error . ' — ' . implode(' ', $hints);
    }

    /** @param array<string, mixed> $config */
    /**
     * @param array<string, mixed> $config
     * @param list<array{content:string,name:string,type?:string}> $attachments
     */
    private static function sendViaPhpMail(
        string $toEmail,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array $config,
        array $attachments = []
    ): bool {
        $fromEmail   = (string) ($config['fromEmail'] ?? '');
        $fromName    = (string) ($config['fromName'] ?? 'Biver Royalty Homes');
        $replyTo     = (string) ($config['replyTo'] ?? $fromEmail);
        $safeSubject = self::escapeHeader($subject);
        $altBoundary = 'bre_alt_' . bin2hex(random_bytes(8));

        $alternative  = "--{$altBoundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$plainBody}\r\n\r\n";
        $alternative .= "--{$altBoundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
        $alternative .= "--{$altBoundary}--";

        $validAttachments = array_values(array_filter(
            $attachments,
            static fn ($a): bool => ($a['content'] ?? '') !== ''
        ));

        if ($validAttachments === []) {
            $headers = [
                'MIME-Version: 1.0',
                'From: ' . self::escapeHeader($fromName) . ' <' . $fromEmail . '>',
                'Reply-To: ' . $replyTo,
                'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"',
            ];
            $message = $alternative;
        } else {
            $mixedBoundary = 'bre_mix_' . bin2hex(random_bytes(8));
            $headers = [
                'MIME-Version: 1.0',
                'From: ' . self::escapeHeader($fromName) . ' <' . $fromEmail . '>',
                'Reply-To: ' . $replyTo,
                'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"',
            ];

            $message  = "--{$mixedBoundary}\r\nContent-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n{$alternative}\r\n\r\n";
            foreach ($validAttachments as $attachment) {
                $name = $attachment['name'] ?? 'attachment.pdf';
                $type = $attachment['type'] ?? 'application/pdf';
                $encoded = chunk_split(base64_encode($attachment['content']));
                $message .= "--{$mixedBoundary}\r\n"
                    . "Content-Type: {$type}; name=\"{$name}\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n"
                    . "Content-Disposition: attachment; filename=\"{$name}\"\r\n\r\n"
                    . $encoded . "\r\n";
            }
            $message .= "--{$mixedBoundary}--";
        }

        $sent = @mail($toEmail, $safeSubject, $message, implode("\r\n", $headers));

        if (!$sent) {
            self::$lastError = self::$lastError ?? 'PHP mail() failed. Configure SMTP in Admin → Settings → Email.';
        }

        return $sent;
    }

    private static function buildReplyTemplate(
        string $customerName,
        string $replyBody,
        string $originalMessage
    ): string {
        $name     = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $reply    = nl2br(htmlspecialchars($replyBody, ENT_QUOTES, 'UTF-8'));
        $original = nl2br(htmlspecialchars($originalMessage, ENT_QUOTES, 'UTF-8'));

        $inner = <<<HTML
<p style="margin:0 0 16px;font-size:16px;">Dear {$name},</p>
<div style="line-height:1.75;margin:0 0 24px;">{$reply}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-top:8px;">
  <tr>
    <td style="padding:16px 18px;background-color:#f9f7f2;border-radius:10px;border:1px solid #e9e5dc;">
      <p style="margin:0 0 8px;font-size:12px;color:#6c5e4e;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Your original message</p>
      <p style="margin:0;font-size:13px;color:#6c5e4e;line-height:1.6;">{$original}</p>
    </td>
  </tr>
</table>
<p style="margin:24px 0 0;font-size:14px;color:#2c2418;">Warm regards,<br><strong>Biver Royalty Homes Team</strong></p>
HTML;

        return AutomatedEmailService::wrapBranded($inner);
    }

    private static function buildInquiryNotificationTemplate(
        string $name,
        string $email,
        string $phone,
        string $type,
        string $message,
        int $id
    ): string {
        $eName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $eEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $ePhone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $eType    = htmlspecialchars(ucfirst($type), ENT_QUOTES, 'UTF-8');
        $eMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $adminUrl = htmlspecialchars(self::adminContactUrl($id), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f9f7f2;padding:24px;color:#2c2418;">
  <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;border:1px solid #e9e5dc;">
    <h2 style="color:#371801;font-family:Georgia,serif;margin:0 0 8px;">New Contact Inquiry</h2>
    <p style="color:#6c5e4e;margin-bottom:20px;">A visitor submitted the contact form on your website.</p>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
      <tr><td style="padding:8px 0;color:#6c5e4e;width:120px;">Name</td><td style="padding:8px 0;"><strong>{$eName}</strong></td></tr>
      <tr><td style="padding:8px 0;color:#6c5e4e;">Email</td><td style="padding:8px 0;"><a href="mailto:{$eEmail}">{$eEmail}</a></td></tr>
      <tr><td style="padding:8px 0;color:#6c5e4e;">Phone</td><td style="padding:8px 0;">{$ePhone}</td></tr>
      <tr><td style="padding:8px 0;color:#6c5e4e;">Type</td><td style="padding:8px 0;">{$eType}</td></tr>
    </table>
    <p style="margin:20px 0 8px;font-weight:bold;color:#371801;">Message</p>
    <p style="background:#f9f7f2;padding:14px;border-radius:8px;line-height:1.6;">{$eMessage}</p>
    <p style="margin-top:24px;"><a href="{$adminUrl}" style="display:inline-block;background:#D4AF37;color:#371801;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold;">Open in Admin</a></p>
  </div>
</body>
</html>
HTML;
    }

    private static function adminContactUrl(int $id): string
    {
        require_once __DIR__ . '/site_paths.php';

        $base = siteRootPath();
        $path = ($base !== '' ? $base : '') . '/admin/admin-contact.php';

        return $id > 0 ? $path . '?id=' . $id : $path;
    }

    private static function htmlToPlain(string $text): string
    {
        return strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $text));
    }

    private static function escapeHeader(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }
}
