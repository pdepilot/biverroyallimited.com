<?php
/**
 * Chatbot security: sessions, CSRF, rate limiting, sanitization.
 */

declare(strict_types=1);

require_once __DIR__ . '/../chatbot-config.php';
require_once dirname(__DIR__, 2) . '/includes/site_paths.php';

class ChatbotSecurity
{
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');

        $base = siteRootPath();
        $cookiePath = $base === '' ? '/' : $base . '/';
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $cookiePath,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name(CHATBOT_SESSION_NAME);
        session_start();
    }

    public static function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function getUserAgent(): string
    {
        return mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 512);
    }

    public static function generateCsrfToken(): string
    {
        self::initSession();

        if (
            empty($_SESSION['chatbot_csrf'])
            || empty($_SESSION['chatbot_csrf_time'])
            || (time() - (int) $_SESSION['chatbot_csrf_time']) > 3600
        ) {
            $_SESSION['chatbot_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['chatbot_csrf_time'] = time();
        }

        return $_SESSION['chatbot_csrf'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        self::initSession();

        if ($token === null || $token === '' || empty($_SESSION['chatbot_csrf'])) {
            return false;
        }

        return hash_equals($_SESSION['chatbot_csrf'], $token);
    }

    public static function sanitizeText(string $input, int $maxLength = 4000): string
    {
        $clean = strip_tags($input);
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        if (mb_strlen($clean) > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }

    public static function sanitizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+\-\s()]/', '', trim($phone)) ?? '';
        return $phone !== '' ? mb_substr($phone, 0, 40) : null;
    }

    /**
     * Rate limit by IP using visitor_logs.
     */
    public static function checkRateLimit(PDO $pdo): bool
    {
        try {
            $ip = self::getClientIp();
            $since = date('Y-m-d H:i:s', time() - CHATBOT_RATE_LIMIT_WINDOW);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS cnt FROM visitor_logs
                 WHERE ip_address = :ip AND event_type = 'message_sent'
                 AND created_at >= :since"
            );
            $stmt->execute(['ip' => $ip, 'since' => $since]);
            $count = (int) ($stmt->fetch()['cnt'] ?? 0);

            return $count < CHATBOT_RATE_LIMIT_MAX;
        } catch (PDOException) {
            return true;
        }
    }

    public static function requireAjaxOrigin(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-Chatbot-CSRF');
            http_response_code(204);
            exit;
        }
    }

    public static function validateVisitorSession(?string $sessionUuid): bool
    {
        if ($sessionUuid === null || !preg_match('/^[a-f0-9\-]{36}$/i', $sessionUuid)) {
            return false;
        }

        self::initSession();
        return !empty($_SESSION['chatbot_session_uuid'])
            && hash_equals($_SESSION['chatbot_session_uuid'], $sessionUuid);
    }

    public static function bindVisitorSession(string $sessionUuid): void
    {
        self::initSession();
        $_SESSION['chatbot_session_uuid'] = $sessionUuid;
        $_SESSION['chatbot_session_started'] = time();
    }
}
