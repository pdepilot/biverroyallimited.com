<?php
/**
 * Email notifications for chatbot support events.
 */
declare(strict_types=1);

require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/../chatbot/chatbot-config.php';

class ChatbotNotificationService
{
    /**
     * @param array<string, mixed> $lead
     */
    public static function notifyNewSupportRequest(array $lead, ?string $ticketNumber = null): bool
    {
        $site = chatbotSiteConfig();
        $to = $site['contactEmail'] ?? '';
        if ($to === '') {
            return false;
        }

        $subject = '[Biver Royalty Homes] New chatbot support request';
        $body = '<h2>New support request from chatbot</h2>'
            . '<p><strong>Name:</strong> ' . htmlspecialchars((string) ($lead['visitor_name'] ?? $lead['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Phone:</strong> ' . htmlspecialchars((string) ($lead['visitor_phone'] ?? $lead['phone'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Email:</strong> ' . htmlspecialchars((string) ($lead['visitor_email'] ?? $lead['email'] ?? '—'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Question:</strong></p><p>' . nl2br(htmlspecialchars((string) ($lead['question'] ?? ''), ENT_QUOTES, 'UTF-8')) . '</p>';

        if ($ticketNumber) {
            $body .= '<p><strong>Ticket:</strong> ' . htmlspecialchars($ticketNumber, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $body .= '<p>Open the <strong>Live Chat &amp; Leads</strong> admin panel to respond.</p>';

        return MailService::sendEmail($to, $site['siteName'] ?? 'Admin', $subject, $body, strip_tags($body));
    }

    public static function notifyAgentAssignment(string $conversationLabel, string $assignedTo): bool
    {
        $site = chatbotSiteConfig();
        $to = $site['contactEmail'] ?? '';
        if ($to === '') {
            return false;
        }

        $subject = '[Biver Royalty Homes] Conversation assigned';
        $body = '<p>Conversation <strong>' . htmlspecialchars($conversationLabel, ENT_QUOTES, 'UTF-8')
            . '</strong> was assigned to <strong>' . htmlspecialchars($assignedTo, ENT_QUOTES, 'UTF-8') . '</strong>.</p>';

        return MailService::sendEmail($to, $site['siteName'] ?? 'Admin', $subject, $body, strip_tags($body));
    }
}
