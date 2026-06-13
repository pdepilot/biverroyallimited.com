<?php
/**
 * Biver Royalty Homes — Chatbot Configuration
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/site_paths.php';

/** Chatbot session cookie name */
const CHATBOT_SESSION_NAME = 'BRE_CHAT_SID';

/** Visitor session inactivity timeout (24 hours) */
const CHATBOT_SESSION_TIMEOUT = 86400;

/** Rate limit: max messages per window */
const CHATBOT_RATE_LIMIT_MAX = 30;

/** Rate limit window in seconds */
const CHATBOT_RATE_LIMIT_WINDOW = 60;

/** Minimum confidence for intent match (0–1) */
const CHATBOT_DEFAULT_CONFIDENCE = 0.35;

/** Poll interval hint for clients (ms) */
const CHATBOT_POLL_INTERVAL_MS = 5000;

/** Typing simulation delay range (ms) */
const CHATBOT_TYPING_MIN_MS = 600;
const CHATBOT_TYPING_MAX_MS = 1800;

/** Intent engine cache TTL (seconds) */
const CHATBOT_INTENT_CACHE_TTL = 300;

/**
 * Load site settings merged with chatbot defaults.
 *
 * @return array<string, mixed>
 */
function chatbotSiteConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'siteName'      => 'Biver Royalty Homes',
        'contactEmail'  => 'biverroyaltyhomes01@gmail.com',
        'contactPhone'  => '+234 903 313 7432',
        'address'       => 'No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State',
        'aboutText'     => 'We are a real estate company built on integrity, helping clients find premium homes across Nigeria.',
        'whatsapp'      => '+2349033137432',
        'businessHours' => 'Monday – Saturday: 8:00 AM – 6:00 PM',
    ];

    $settingsFile = dirname(__DIR__) . '/config/site-settings.php';
    if (is_readable($settingsFile)) {
        $site = require $settingsFile;
        if (is_array($site)) {
            $defaults = array_merge($defaults, $site);
        }
    }

    $config = $defaults;
    return $config;
}

/**
 * @return array<string, mixed>
 */
function chatbotPublicConfig(): array
{
    $site = chatbotSiteConfig();

    return [
        'siteName'       => $site['siteName'],
        'apiUrl'         => siteUrl('chatbot/chatbot-api.php'),
        'pollInterval'   => CHATBOT_POLL_INTERVAL_MS,
        'typingMin'      => CHATBOT_TYPING_MIN_MS,
        'typingMax'      => CHATBOT_TYPING_MAX_MS,
        'welcomeDelay1'  => 3000,
        'welcomeDelay2'  => 8000,
        'welcomeDelay3'  => 15000,
        'soundEnabled'   => true,
        'agentName'      => 'Biver Royalty Homes Assistant',
        'agentSubtitle'  => 'Online Now',
        'agentAvatar'    => siteUrl('assets/images/biver-logo.png'),
        'userAvatar'     => null,
        'escalationText' => 'I couldn\'t find a reliable answer to that question.',
        'supportSubmitUrl' => siteUrl('admin/api/chatbot-contact.php'),
    ];
}

function chatbotEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function chatbotJsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chatbotGenerateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function chatbotGenerateTicketNumber(): string
{
    return 'BRH-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}
