<?php
/**
 * Core security services: sessions, CSRF, lockouts, audit logging, authentication.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

/** Session inactivity timeout in seconds (15 minutes). */
const ADMIN_SESSION_TIMEOUT = 900;

/** Failed attempts before final warning (3 failures = warning on 3rd). */
const MAX_ATTEMPTS_BEFORE_WARNING = 3;

/** Failed attempts that trigger IP lockout. */
const MAX_ATTEMPTS_BEFORE_LOCKOUT = 4;

/** First lockout duration: 72 hours in seconds. */
const LOCKOUT_DURATION_LEVEL_1 = 72 * 3600;

/** Second lockout duration: 30 days in seconds. */
const LOCKOUT_DURATION_LEVEL_2 = 30 * 24 * 3600;

class AuthSecurity
{
    /**
     * Start a hardened PHP session with hijacking-resistant settings.
     */
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }

        session_name('BRE_ADMIN_SID');
        session_start();
    }

    /**
     * Resolve client IP (IPv4/IPv6). Uses REMOTE_ADDR only for trust.
     */
    public static function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Sanitized browser user agent string.
     */
    public static function getUserAgent(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return mb_substr($ua, 0, 512);
    }

    /**
     * Fingerprint used to detect session hijacking (IP + user agent hash).
     */
    public static function buildSessionFingerprint(): string
    {
        return hash('sha256', self::getClientIp() . '|' . self::getUserAgent());
    }

    /**
     * Generate and store a CSRF token in the session.
     */
    public static function generateCsrfToken(): string
    {
        self::initSession();

        if (
            empty($_SESSION['csrf_token'])
            || empty($_SESSION['csrf_token_time'])
            || (time() - (int) $_SESSION['csrf_token_time']) > 3600
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate submitted CSRF token (timing-safe comparison).
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::initSession();

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check whether an admin is logged in with a valid, non-expired session.
     */
    public static function isAuthenticated(): bool
    {
        self::initSession();

        if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
            return false;
        }

        if (!self::validateSessionFingerprint()) {
            self::destroySession();
            return false;
        }

        if (self::isSessionExpired()) {
            self::auditLog('session_expired', (int) $_SESSION['admin_id'], 'Session timed out after inactivity');
            self::destroySession();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Enforce session timeout (15 minutes idle).
     */
    public static function isSessionExpired(): bool
    {
        $last = $_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? 0;
        return (time() - (int) $last) > ADMIN_SESSION_TIMEOUT;
    }

    /**
     * Compare stored session fingerprint with current request.
     */
    public static function validateSessionFingerprint(): bool
    {
        if (empty($_SESSION['session_fingerprint'])) {
            return false;
        }

        return hash_equals($_SESSION['session_fingerprint'], self::buildSessionFingerprint());
    }

    /**
     * Establish authenticated admin session after successful login.
     */
    public static function createAdminSession(array $admin): void
    {
        self::initSession();

        session_regenerate_id(true);

        $_SESSION['admin_logged_in']      = true;
        $_SESSION['admin_id']             = (int) $admin['id'];
        $_SESSION['admin_email']          = $admin['email'];
        $_SESSION['admin_name']           = $admin['full_name'] ?? 'Administrator';
        $_SESSION['admin_role']           = $admin['role'] ?? 'administrator';
        $_SESSION['login_time']           = time();
        $_SESSION['last_activity']        = time();
        $_SESSION['session_fingerprint']  = self::buildSessionFingerprint();

        require_once __DIR__ . '/AdminPermissions.php';
        AdminPermissions::loadSessionPermissions((int) $admin['id']);

        self::generateCsrfToken();
    }

    /**
     * Fully destroy session data and cookie.
     */
    public static function destroySession(): void
    {
        self::initSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Remove expired automatic lockouts so IPs can log in again.
     */
    public static function purgeExpiredLockouts(): void
    {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare(
            'SELECT id, ip_address, lockout_level
             FROM ip_lockouts
             WHERE is_active = 1
               AND requires_manual_review = 0
               AND expires_at IS NOT NULL
               AND expires_at <= NOW()'
        );
        $stmt->execute();
        $expired = $stmt->fetchAll();

        if (empty($expired)) {
            return;
        }

        $deactivate = $pdo->prepare(
            'UPDATE ip_lockouts SET is_active = 0 WHERE id = :id'
        );
        $history = $pdo->prepare(
            'UPDATE lockout_history
             SET lifted_at = NOW(), lift_method = \'auto_expiry\'
             WHERE ip_address = :ip AND lifted_at IS NULL
             ORDER BY locked_at DESC LIMIT 1'
        );

        foreach ($expired as $row) {
            $deactivate->execute(['id' => $row['id']]);
            $history->execute(['ip' => $row['ip_address']]);
            self::auditLog(
                'lockout_lifted',
                null,
                'Automatic 72h/30d lockout expired for IP ' . $row['ip_address'],
                $row['ip_address']
            );
        }
    }

    /**
     * Return active lockout row for IP, or null if not restricted.
     *
     * @return array<string, mixed>|null
     */
    public static function getActiveLockout(string $ip): ?array
    {
        self::purgeExpiredLockouts();

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ip_lockouts
             WHERE ip_address = :ip AND is_active = 1
             ORDER BY locked_at DESC LIMIT 1'
        );
        $stmt->execute(['ip' => $ip]);
        $lockout = $stmt->fetch();

        if (!$lockout) {
            return null;
        }

        if (
            !(int) $lockout['requires_manual_review']
            && !empty($lockout['expires_at'])
            && strtotime($lockout['expires_at']) <= time()
        ) {
            return null;
        }

        return $lockout;
    }

    /**
     * Human-readable remaining lockout time.
     */
    public static function formatRemainingTime(?string $expiresAt): string
    {
        if ($expiresAt === null) {
            return 'pending administrator review';
        }

        $remaining = strtotime($expiresAt) - time();
        if ($remaining <= 0) {
            return 'expired';
        }

        $days  = (int) floor($remaining / 86400);
        $hours = (int) floor(($remaining % 86400) / 3600);
        $mins  = (int) floor(($remaining % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' day' . ($days !== 1 ? 's' : '');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' hour' . ($hours !== 1 ? 's' : '');
        }
        if ($mins > 0 && $days === 0) {
            $parts[] = $mins . ' minute' . ($mins !== 1 ? 's' : '');
        }

        return implode(', ', $parts) ?: 'less than a minute';
    }

    /**
     * Count prior completed lockouts for progressive penalty calculation.
     */
    public static function countPriorLockouts(string $ip): int
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total FROM lockout_history WHERE ip_address = :ip'
        );
        $stmt->execute(['ip' => $ip]);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * Apply progressive IP lockout (never permanent lifetime ban).
     *
     * @return array{level: int, expires_at: ?string, manual: bool}
     */
    public static function applyIpLockout(string $ip, string $reason): array
    {
        $priorCount = self::countPriorLockouts($ip);
        $pdo = getDatabaseConnection();

        if ($priorCount >= 2) {
            $level = 3;
            $expiresAt = null;
            $manual = true;
            $durationLabel = 'manual administrator review required';
        } elseif ($priorCount === 1) {
            $level = 2;
            $expiresAt = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION_LEVEL_2);
            $manual = false;
            $durationLabel = '30 days';
        } else {
            $level = 1;
            $expiresAt = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION_LEVEL_1);
            $manual = false;
            $durationLabel = '72 hours';
        }

        $stmt = $pdo->prepare(
            'INSERT INTO ip_lockouts
                (ip_address, ban_reason, lockout_level, requires_manual_review, locked_at, expires_at, is_active)
             VALUES
                (:ip, :reason, :level, :manual, NOW(), :expires, 1)'
        );
        $stmt->execute([
            'ip'      => $ip,
            'reason'  => $reason,
            'level'   => $level,
            'manual'  => $manual ? 1 : 0,
            'expires' => $expiresAt,
        ]);

        $hist = $pdo->prepare(
            'INSERT INTO lockout_history
                (ip_address, lockout_level, ban_reason, locked_at, expires_at)
             VALUES
                (:ip, :level, :reason, NOW(), :expires)'
        );
        $hist->execute([
            'ip'      => $ip,
            'level'   => $level,
            'reason'  => $reason,
            'expires' => $expiresAt,
        ]);

        self::resetLoginAttempts($ip);

        self::auditLog(
            'ip_restricted',
            null,
            sprintf('Lockout level %d (%s). Reason: %s', $level, $durationLabel, $reason),
            $ip
        );

        return [
            'level'      => $level,
            'expires_at' => $expiresAt,
            'manual'     => $manual,
        ];
    }

    /**
     * Increment failed attempt counter for IP.
     *
     * @return int New failed attempt count
     */
    public static function recordFailedAttempt(string $ip): int
    {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare(
            'SELECT failed_attempts FROM login_attempts WHERE ip_address = :ip'
        );
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();

        if ($row) {
            $count = (int) $row['failed_attempts'] + 1;
            $upd = $pdo->prepare(
                'UPDATE login_attempts
                 SET failed_attempts = :count, last_attempt_at = NOW()
                 WHERE ip_address = :ip'
            );
            $upd->execute(['count' => $count, 'ip' => $ip]);
        } else {
            $count = 1;
            $ins = $pdo->prepare(
                'INSERT INTO login_attempts (ip_address, failed_attempts, first_attempt_at, last_attempt_at)
                 VALUES (:ip, 1, NOW(), NOW())'
            );
            $ins->execute(['ip' => $ip]);
        }

        return $count;
    }

    /**
     * Current failed attempt count for IP.
     */
    public static function getFailedAttemptCount(string $ip): int
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT failed_attempts FROM login_attempts WHERE ip_address = :ip'
        );
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();
        return $row ? (int) $row['failed_attempts'] : 0;
    }

    /**
     * Clear failed attempts after successful login.
     */
    public static function resetLoginAttempts(string $ip): void
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
        $stmt->execute(['ip' => $ip]);
    }

    /**
     * Verify admin credentials against database.
     *
     * @return array<string, mixed>|null Admin row or null
     */
    public static function verifyCredentials(string $email, string $password): ?array
    {
        $pdo = getDatabaseConnection();
        require_once __DIR__ . '/AdminUserRepository.php';
        AdminUserRepository::ensureSchema();

        $stmt = $pdo->prepare(
            'SELECT id, email, password_hash, full_name, is_active, role, permissions_json
             FROM admin_users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $admin = $stmt->fetch();

        if (!$admin || !(int) $admin['is_active']) {
            return null;
        }

        if (!password_verify($password, $admin['password_hash'])) {
            return null;
        }

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare(
                'UPDATE admin_users SET password_hash = :hash WHERE id = :id'
            );
            $upd->execute(['hash' => $newHash, 'id' => $admin['id']]);
        }

        return $admin;
    }

    /**
     * Write security event to audit log.
     */
    public static function auditLog(
        string $eventType,
        ?int $adminId = null,
        ?string $details = null,
        ?string $ip = null
    ): void {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO admin_audit_log (event_type, admin_id, ip_address, user_agent, details)
                 VALUES (:event, :admin_id, :ip, :ua, :details)'
            );
            $stmt->execute([
                'event'    => $eventType,
                'admin_id' => $adminId,
                'ip'       => $ip ?? self::getClientIp(),
                'ua'       => self::getUserAgent(),
                'details'  => $details,
            ]);
        } catch (Throwable $e) {
            error_log('Audit log failure: ' . $e->getMessage());
        }
    }

    /**
     * Store flash message for next request.
     */
    public static function setFlash(string $key, mixed $value): void
    {
        self::initSession();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Retrieve and remove flash message.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::initSession();
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /** @return array<string, mixed>|null */
    public static function getCurrentAdmin(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        $pdo = getDatabaseConnection();
        require_once __DIR__ . '/AdminUserRepository.php';
        AdminUserRepository::ensureSchema();

        $stmt = $pdo->prepare(
            'SELECT id, email, full_name, is_active, role, permissions_json, created_at, updated_at
             FROM admin_users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => (int) $_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public static function updateAdminProfile(int $adminId, string $fullName, string $email): void
    {
        $fullName = trim($fullName);
        $email    = mb_strtolower(trim($email));

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid name and email are required.');
        }

        $pdo = getDatabaseConnection();
        $check = $pdo->prepare(
            'SELECT id FROM admin_users WHERE email = :email AND id != :id LIMIT 1'
        );
        $check->execute(['email' => $email, 'id' => $adminId]);
        if ($check->fetch()) {
            throw new RuntimeException('That email is already used by another admin.');
        }

        $stmt = $pdo->prepare(
            'UPDATE admin_users SET full_name = :name, email = :email WHERE id = :id'
        );
        $stmt->execute([
            'name'  => $fullName,
            'email' => $email,
            'id'    => $adminId,
        ]);

        $_SESSION['admin_name']  = $fullName;
        $_SESSION['admin_email'] = $email;
    }

    public static function changeAdminPassword(int $adminId, string $currentPassword, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('New password must be at least 8 characters.');
        }

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $adminId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, (string) $row['password_hash'])) {
            throw new RuntimeException('Current password is incorrect.');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id');
        $upd->execute(['hash' => $hash, 'id' => $adminId]);

        self::auditLog('password_changed', $adminId, 'Admin password updated');
    }

    public static function deactivateAdmin(int $adminId): void
    {
        $pdo = getDatabaseConnection();
        $count = (int) $pdo->query('SELECT COUNT(*) FROM admin_users WHERE is_active = 1')->fetchColumn();
        if ($count <= 1) {
            throw new RuntimeException('Cannot deactivate the only active admin account.');
        }

        $stmt = $pdo->prepare('UPDATE admin_users SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $adminId]);
        self::auditLog('logout', $adminId, 'Admin account deactivated by user');
        self::destroySession();
    }
}
