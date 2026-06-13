<?php
/**
 * Event-driven automated email notifications for biverroyaltyhomesltd.
 */
declare(strict_types=1);

require_once __DIR__ . '/EmailRepository.php';
require_once __DIR__ . '/HtmlSanitizer.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/MailConfigService.php';
require_once __DIR__ . '/site_paths.php';

final class AutomatedEmailService
{
    public const EVENT_CONTACT_RECEIVED       = 'contact_enquiry_received';
    public const EVENT_CONTACT_ADMIN        = 'contact_admin_notification';
    public const EVENT_PROPERTY_SUBMITTED   = 'property_submission_received';
    public const EVENT_PROPERTY_APPROVED    = 'property_approved';
    public const EVENT_PROPERTY_REJECTED    = 'property_rejected';
    public const EVENT_PROPERTY_SUSPENDED   = 'property_suspended';
    public const EVENT_PROPERTY_EXPIRED     = 'property_expired';
    public const EVENT_PROPERTY_REACTIVATED = 'property_reactivated';
    public const EVENT_NEWSLETTER_WELCOME   = 'newsletter_welcome';
    public const EVENT_PASSWORD_RESET       = 'password_reset';
    public const EVENT_USER_REGISTRATION    = 'user_registration';

    /** @return array<string, array{name:string,subject:string,body_html:string,description?:string}> */
    public static function defaultEventTemplates(): array
    {
        return [
            self::EVENT_CONTACT_RECEIVED => [
                'name'        => 'Contact Enquiry Received',
                'description' => 'Auto-response when visitor submits contact form',
                'subject'     => 'We Have Received Your Enquiry',
                'body_html'   => '<p>Hello {{customer_name}},</p>
<p>Thank you for contacting <strong>biverroyaltyhomesltd</strong>.</p>
<p>We have successfully received your enquiry regarding:</p>
<p><strong>"{{enquiry_subject}}"</strong></p>
<p>Our team has been notified and one of our representatives will review your message and get back to you as soon as possible.</p>
<p><strong>Reference Number:</strong> {{ticket_id}}<br>
<strong>Submission Date:</strong> {{submission_date}}</p>
<p>We appreciate your interest in biverroyaltyhomesltd and look forward to assisting you.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_CONTACT_ADMIN => [
                'name'        => 'Contact Enquiry Admin Notification',
                'description' => 'Notifies administrators of new contact enquiries',
                'subject'     => 'New Contact Enquiry — {{customer_name}}',
                'body_html'   => '<p>A new contact enquiry was submitted on the website.</p>
<table style="width:100%;font-size:14px;border-collapse:collapse;">
<tr><td style="padding:6px 0;color:#6c5e4e;width:130px;">Customer Name</td><td><strong>{{customer_name}}</strong></td></tr>
<tr><td style="padding:6px 0;color:#6c5e4e;">Email</td><td>{{customer_email}}</td></tr>
<tr><td style="padding:6px 0;color:#6c5e4e;">Phone</td><td>{{customer_phone}}</td></tr>
<tr><td style="padding:6px 0;color:#6c5e4e;">Subject</td><td>{{enquiry_subject}}</td></tr>
<tr><td style="padding:6px 0;color:#6c5e4e;">Submitted</td><td>{{submission_date}}</td></tr>
</table>
<p><strong>Message:</strong></p>
<p style="background:#f9f7f2;padding:14px;border-radius:8px;">{{message}}</p>
<p><strong>Reference:</strong> {{ticket_id}}</p>',
            ],
            self::EVENT_PROPERTY_SUBMITTED => [
                'name'        => 'Property Submission Received',
                'description' => 'Acknowledgement when owner submits a listing',
                'subject'     => 'Your Property Listing Request Has Been Received',
                'body_html'   => '<p>Hello {{owner_name}},</p>
<p>Thank you for submitting your property for listing with <strong>biverroyaltyhomesltd</strong>.</p>
<p>We have successfully received your property submission and supporting documents.</p>
<p>Your property is currently under review by our verification team.</p>
<p><strong>Status:</strong> Pending Approval<br>
<strong>Reference Number:</strong> {{listing_id}}<br>
<strong>Submission Date:</strong> {{submission_date}}</p>
<p>Our team will inspect and verify the submitted information and documents. Once verification is completed and approved, your property will be published on our website.</p>
<p>Thank you for choosing biverroyaltyhomesltd.</p>
<p>Best Regards,<br><strong>Mannavilla Limited</strong></p>',
            ],
            self::EVENT_PROPERTY_APPROVED => [
                'name'        => 'Property Approved',
                'description' => 'Sent when admin approves a property listing',
                'subject'     => 'Congratulations! Your Property Has Been Approved',
                'body_html'   => '<p>Hello {{owner_name}},</p>
<p>We are pleased to inform you that your property submission has been reviewed and <strong>approved</strong> by our team.</p>
<p>Your property is now live and visible on the biverroyaltyhomesltd website.</p>
<p><a href="{{property_url}}" style="display:inline-block;background:#D4AF37;color:#371801;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold;">View Your Property</a></p>
<p><strong>Property Title:</strong> {{property_title}}<br>
<strong>Approval Date:</strong> {{approval_date}}</p>
<p>Thank you for listing with biverroyaltyhomesltd. We wish you success in finding the right buyer or investor.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_PROPERTY_REJECTED => [
                'name'        => 'Property Rejected',
                'description' => 'Sent when admin rejects a property listing',
                'subject'     => 'Property Listing Update',
                'body_html'   => '<p>Hello {{owner_name}},</p>
<p>Thank you for submitting your property listing.</p>
<p>After reviewing the submitted information and documentation, we are currently unable to approve the property for publication.</p>
<p><strong>Reason:</strong><br>{{rejection_reason}}</p>
<p>You may update the required information and resubmit your property for review. If you need assistance, please contact our support team.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_PROPERTY_SUSPENDED => [
                'name'        => 'Property Suspended',
                'description' => 'Sent when a live property is suspended',
                'subject'     => 'Your Property Listing Has Been Suspended',
                'body_html'   => '<p>Hello {{owner_name}},</p>
<p>Your property listing <strong>{{property_title}}</strong> has been temporarily suspended and is no longer visible on our website.</p>
<p><strong>Reason:</strong> {{status_reason}}</p>
<p>Please contact our team if you have questions or need to restore your listing.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_PROPERTY_EXPIRED => [
                'name'        => 'Property Expired',
                'description' => 'Sent when a property listing expires',
                'subject'     => 'Your Property Listing Has Expired',
                'body_html'   => '<p>Hello {{owner_name}},</p>
<p>Your property listing <strong>{{property_title}}</strong> has expired and is no longer visible on our website.</p>
<p>To keep your listing active, please contact us or resubmit your property for review.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_PROPERTY_REACTIVATED => [
                'name'        => 'Property Reactivated',
                'description' => 'Sent when a suspended listing is restored',
                'subject'     => 'Your Property Listing Is Live Again',
                'body_html'   => '<p>Hello {{owner_name}},</p>
<p>Good news! Your property listing <strong>{{property_title}}</strong> has been reactivated and is visible again on biverroyaltyhomesltd.</p>
<p><a href="{{property_url}}">View your property</a></p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_NEWSLETTER_WELCOME => [
                'name'        => 'Newsletter Welcome',
                'description' => 'Sent when a visitor subscribes to the newsletter',
                'subject'     => 'Welcome to biverroyaltyhomesltd Newsletter',
                'body_html'   => '<p>Hello {{customer_name}},</p>
<p>Thank you for subscribing to the <strong>biverroyaltyhomesltd</strong> newsletter.</p>
<p>You will receive updates on premium properties, market insights, and exclusive offers.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_PASSWORD_RESET => [
                'name'        => 'Password Reset',
                'description' => 'Sent when a password reset is requested',
                'subject'     => 'Reset Your Password — biverroyaltyhomesltd',
                'body_html'   => '<p>Hello {{customer_name}},</p>
<p>We received a request to reset your password.</p>
<p><a href="{{reset_link}}" style="display:inline-block;background:#D4AF37;color:#371801;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold;">Reset Password</a></p>
<p>If you did not request this, please ignore this email.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
            self::EVENT_USER_REGISTRATION => [
                'name'        => 'User Registration Welcome',
                'description' => 'Sent when a new user completes registration',
                'subject'     => 'Welcome to biverroyaltyhomesltd',
                'body_html'   => '<p>Hello {{customer_name}},</p>
<p>Your account has been created successfully. Welcome to <strong>biverroyaltyhomesltd</strong>!</p>
<p>You can now explore properties, save favourites, and manage your profile.</p>
<p>Best Regards,<br><strong>biverroyaltyhomesltd</strong></p>',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function eventLabels(): array
    {
        return [
            self::EVENT_CONTACT_RECEIVED       => 'Contact Enquiry Received',
            self::EVENT_CONTACT_ADMIN          => 'Contact Admin Notification',
            self::EVENT_PROPERTY_SUBMITTED     => 'Property Submission Received',
            self::EVENT_PROPERTY_APPROVED      => 'Property Approved',
            self::EVENT_PROPERTY_REJECTED      => 'Property Rejected',
            self::EVENT_PROPERTY_SUSPENDED     => 'Property Suspended',
            self::EVENT_PROPERTY_EXPIRED       => 'Property Expired',
            self::EVENT_PROPERTY_REACTIVATED   => 'Property Reactivated',
            self::EVENT_NEWSLETTER_WELCOME     => 'Newsletter Welcome Email',
            self::EVENT_PASSWORD_RESET         => 'Password Reset',
            self::EVENT_USER_REGISTRATION      => 'New User Registration',
        ];
    }

    /**
     * @param array<string, string> $vars
     */
    public static function send(
        string $eventKey,
        string $toEmail,
        string $toName,
        array $vars = [],
        ?int $relatedRecordId = null,
        int $createdBy = 0
    ): bool {
        EmailRepository::ensureSchema();

        $toEmail = strtolower(trim($toEmail));
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $template = EmailRepository::getTemplateByEvent($eventKey);
        if ($template === null) {
            $defaults = self::defaultEventTemplates();
            if (!isset($defaults[$eventKey])) {
                return false;
            }
            $template = $defaults[$eventKey];
        }

        $vars = self::normalizeVars($vars, $toName);
        $subject = self::replaceVars((string) $template['subject'], $vars);
        $inner   = self::replaceVars((string) $template['body_html'], $vars);
        $inner   = HtmlSanitizer::sanitizeEmailHtml($inner);
        $html    = self::wrapBranded($inner);
        $plain   = HtmlSanitizer::htmlToPlain($inner);

        $ok = MailService::sendEmail($toEmail, $toName, $subject, $html, $plain);

        EmailRepository::logEmailExtended([
            'recipient'         => $toEmail,
            'recipient_name'    => $toName,
            'subject'           => $subject,
            'message'           => $html,
            'status'            => $ok ? 'sent' : 'failed',
            'email_type'        => $eventKey,
            'related_record_id' => $relatedRecordId,
            'admin_id'          => $createdBy > 0 ? $createdBy : null,
            'error_msg'         => $ok ? null : (MailService::getLastError() ?? 'Send failed'),
        ]);

        return $ok;
    }

    /** @param array<string, mixed> $inquiry */
    public static function onContactSubmitted(array $inquiry): void
    {
        $id = (int) ($inquiry['id'] ?? 0);
        $name = (string) ($inquiry['full_name'] ?? $inquiry['name'] ?? 'Customer');
        $email = (string) ($inquiry['email'] ?? '');
        $phone = (string) ($inquiry['phone'] ?? 'Not provided');
        $type = (string) ($inquiry['inquiry_type'] ?? 'general');
        $message = (string) ($inquiry['message'] ?? '');
        $subjectLabel = self::inquiryTypeLabel($type);
        $ticketId = 'BRH-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
        $date = date('d M Y, H:i');

        $vars = [
            'customer_name'    => $name,
            'enquiry_subject'  => $subjectLabel,
            'ticket_id'        => $ticketId,
            'reference_number' => $ticketId,
            'submission_date'  => $date,
            'customer_email'   => $email,
            'customer_phone'   => $phone,
            'message'          => nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')),
        ];

        self::send(self::EVENT_CONTACT_RECEIVED, $email, $name, $vars, $id);

        $config = MailConfigService::get();
        if (!empty($config['notifyOnContact'])) {
            $notifyEmail = trim((string) ($config['notifyEmail'] ?? ''));
            if ($notifyEmail !== '' && filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
                self::send(self::EVENT_CONTACT_ADMIN, $notifyEmail, 'Admin', $vars, $id);
            }
        }
    }

    /** @param array<string, mixed> $property */
    public static function onPropertySubmitted(array $property): void
    {
        $email = trim((string) ($property['ownerEmail'] ?? $property['owner_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $id = (int) ($property['id'] ?? 0);
        $vars = [
            'owner_name'       => (string) ($property['ownerName'] ?? $property['owner_name'] ?? 'Property Owner'),
            'listing_id'       => 'LIST-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'reference_number' => 'LIST-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'submission_date'  => date('d M Y'),
            'property_title'   => (string) ($property['title'] ?? ''),
        ];

        self::send(self::EVENT_PROPERTY_SUBMITTED, $email, $vars['owner_name'], $vars, $id);
    }

    /**
     * @param array<string, mixed> $property
     */
    public static function onPropertyStatusChange(
        array $property,
        string $oldStatus,
        string $newStatus,
        int $adminId = 0,
        ?string $reason = null
    ): void {
        $email = trim((string) ($property['ownerEmail'] ?? $property['owner_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $id = (int) ($property['id'] ?? 0);
        $ownerName = (string) ($property['ownerName'] ?? $property['owner_name'] ?? 'Property Owner');
        $title = (string) ($property['title'] ?? 'Your Property');
        $reason = $reason ?? (string) ($property['adminNotes'] ?? $property['admin_notes'] ?? 'Please contact support for details.');

        $baseVars = [
            'owner_name'       => $ownerName,
            'property_title'   => $title,
            'property_url'     => self::absoluteUrl(propertyDetailUrl($id)),
            'rejection_reason' => $reason,
            'status_reason'    => $reason,
            'approval_date'    => date('d M Y'),
            'listing_id'       => 'LIST-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
        ];

        $event = null;
        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            $event = $oldStatus === 'rejected'
                ? self::EVENT_PROPERTY_REACTIVATED
                : self::EVENT_PROPERTY_APPROVED;
        } elseif ($newStatus === 'rejected') {
            $event = stripos($reason, 'expir') !== false
                ? self::EVENT_PROPERTY_EXPIRED
                : self::EVENT_PROPERTY_REJECTED;
        } elseif ($newStatus === 'pending' && $oldStatus === 'approved') {
            $event = self::EVENT_PROPERTY_SUSPENDED;
        }

        if ($event !== null) {
            self::send($event, $email, $ownerName, $baseVars, $id, $adminId);
        }
    }

    public static function onNewsletterSubscribed(string $email, ?string $name = null): void
    {
        $displayName = $name !== null && $name !== '' ? $name : 'Subscriber';
        self::send(self::EVENT_NEWSLETTER_WELCOME, $email, $displayName, [
            'customer_name' => $displayName,
            'name'          => $displayName,
        ]);
    }

    public static function onPasswordResetRequested(string $email, string $name, string $resetLink): void
    {
        self::send(self::EVENT_PASSWORD_RESET, $email, $name, [
            'customer_name' => $name,
            'reset_link'    => $resetLink,
        ]);
    }

    public static function onUserRegistered(string $email, string $name): void
    {
        self::send(self::EVENT_USER_REGISTRATION, $email, $name, [
            'customer_name' => $name,
        ]);
    }

    public static function resendLog(int $logId): bool
    {
        $log = EmailRepository::getLogById($logId);
        if ($log === null) {
            throw new InvalidArgumentException('Log entry not found.');
        }

        $ok = MailService::sendEmail(
            (string) $log['recipient'],
            (string) ($log['recipient_name'] ?: 'Recipient'),
            (string) $log['subject'],
            (string) $log['message'],
            HtmlSanitizer::htmlToPlain((string) $log['message'])
        );

        EmailRepository::logEmailExtended([
            'recipient'         => (string) $log['recipient'],
            'recipient_name'    => (string) ($log['recipient_name'] ?? ''),
            'subject'           => (string) $log['subject'],
            'message'           => (string) $log['message'],
            'status'            => $ok ? 'sent' : 'failed',
            'email_type'        => (string) ($log['email_type'] ?? 'resend'),
            'related_record_id' => !empty($log['related_record_id']) ? (int) $log['related_record_id'] : null,
            'admin_id'          => (int) ($_SESSION['admin_id'] ?? 0) ?: null,
            'error_msg'         => $ok ? null : (MailService::getLastError() ?? 'Resend failed'),
        ]);

        return $ok;
    }

    public static function wrapBranded(string $innerHtml): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f9f7f2;padding:24px;color:#2c2418;">
  <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;border:1px solid #e9e5dc;">
    <h2 style="color:#371801;font-family:Georgia,serif;margin:0 0 16px;">biverroyaltyhomesltd</h2>
    {$innerHtml}
    <p style="margin-top:24px;font-size:13px;color:#6c5e4e;">
      Mannavilla Limited / Biver Royalty Homes Ltd<br>
      No. 31 Wetheral Road, Owerri, Imo State<br>
      +234 903 313 7432
    </p>
  </div>
</body></html>
HTML;
    }

    /** @param array<string, string> $vars */
    private static function replaceVars(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace(
                ['{{' . $key . '}}', '{{' . strtoupper($key) . '}}'],
                $value,
                $text
            );
        }

        return $text;
    }

    /** @param array<string, string> $vars @return array<string, string> */
    private static function normalizeVars(array $vars, string $toName): array
    {
        $vars['name'] = $vars['name'] ?? htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $vars['customer_name'] = $vars['customer_name'] ?? $vars['name'];

        foreach ($vars as $k => $v) {
            if (!str_contains($k, 'message') && !str_contains($k, 'url') && !str_contains($k, 'link')) {
                $vars[$k] = htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
            }
        }

        return $vars;
    }

    private static function inquiryTypeLabel(string $type): string
    {
        $labels = [
            'general'     => 'General Inquiry',
            'buying'      => 'Interested in Buying',
            'renting'     => 'Interested in Renting',
            'selling'     => 'Selling a Property',
            'partnership' => 'Partnership Opportunity',
        ];

        return $labels[$type] ?? ucfirst($type);
    }

    private static function absoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return $scheme . '://' . $host . $path;
    }
}
