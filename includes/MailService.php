<?php

/**
 * Send transactional email via PHPMailer (SMTP) with PHP mail() fallback.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/MailConfigService.php';

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
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f9f7f2;padding:24px;color:#2c2418;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e9e5dc;">
    <h2 style="color:#371801;font-family:Georgia,serif;">Mail configuration OK</h2>
    <p>This is a test email from <strong>Biver Royalty Homes</strong>.</p>
    <p>Provider: <strong>{$providerLabel}</strong></p>
    <p>If you received this message, SMTP is working correctly.</p>
    <p style="font-size:12px;color:#6c5e4e;margin-top:24px;">Sent at {$sentAt}</p>
  </div>
</body></html>
HTML;

        $plainBody = 'Mail configuration OK. This is a test email from Biver Royalty Homes.';

        return self::sendEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);
    }

    public static function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody
    ): bool {
        self::$lastError = null;

        $config = MailConfigService::get();

        if (!empty($config['useSmtp'])) {
            if (!MailConfigService::isReady()) {
                self::$lastError = 'SMTP is not fully configured. Set credentials in Admin → Settings → Email.';
                return self::sendViaPhpMail($toEmail, $subject, $htmlBody, $plainBody, $config);
            }

            return self::sendViaSmtp($toEmail, $toName, $subject, $htmlBody, $plainBody, $config);
        }

        return self::sendViaPhpMail($toEmail, $subject, $htmlBody, $plainBody, $config);
    }

    /** @param array<string, mixed> $config */
    private static function sendViaSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array $config
    ): bool {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';

        if (!is_readable($autoload)) {
            self::$lastError = 'Composer dependencies missing. Run: composer install';
            error_log('MailService: vendor/autoload.php not found');
            return self::sendViaPhpMail($toEmail, $subject, $htmlBody, $plainBody, $config);
        }

        require_once $autoload;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host     = (string) $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = (string) $config['username'];
            $mail->Password = (string) $config['password'];
            $mail->Port     = (int) $config['port'];
            $mail->Timeout  = (int) $config['timeout'];
            $mail->CharSet  = PHPMailer::CHARSET_UTF8;

            $encryption = strtolower((string) $config['encryption']);
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure  = '';
                $mail->SMTPAutoTLS = false;
            }

            $fromEmail = (string) $config['fromEmail'];
            $fromName  = (string) $config['fromName'];
            $replyTo   = (string) $config['replyTo'];

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($replyTo !== '' ? $replyTo : $fromEmail, $fromName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();
            return true;
        } catch (PhpMailerException $e) {
            self::$lastError = $mail->ErrorInfo ?: $e->getMessage();
            error_log('MailService SMTP error: ' . self::$lastError);
            return false;
        }
    }

    /** @param array<string, mixed> $config */
    private static function sendViaPhpMail(
        string $toEmail,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array $config
    ): bool {
        $fromEmail   = (string) ($config['fromEmail'] ?? '');
        $fromName    = (string) ($config['fromName'] ?? 'Biver Royalty Homes');
        $replyTo     = (string) ($config['replyTo'] ?? $fromEmail);
        $safeSubject = self::escapeHeader($subject);
        $boundary    = 'bre_' . bin2hex(random_bytes(8));

        $headers = [
            'MIME-Version: 1.0',
            'From: ' . self::escapeHeader($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $replyTo,
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $message  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$plainBody}\r\n\r\n";
        $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
        $message .= "--{$boundary}--";

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

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f9f7f2; padding:24px; color:#2c2418;">
  <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;border:1px solid #e9e5dc;">
    <h2 style="color:#371801;font-family:Georgia,serif;margin:0 0 16px;">Biver Royalty Homes</h2>
    <p>Dear {$name},</p>
    <div style="line-height:1.6;margin:16px 0;">{$reply}</div>
    <hr style="border:none;border-top:1px solid #e9e5dc;margin:24px 0;">
    <p style="font-size:12px;color:#6c5e4e;"><strong>Your original message:</strong></p>
    <p style="font-size:13px;color:#6c5e4e;background:#f9f7f2;padding:12px;border-radius:8px;">{$original}</p>
    <p style="margin-top:24px;font-size:13px;color:#6c5e4e;">
      Biver Royalty Homes Ltd<br>
      No. 31 Wetheral Road, Owerri, Imo State<br>
      +234 903 313 7432
    </p>
  </div>
</body>
</html>
HTML;
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
