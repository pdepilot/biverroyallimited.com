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

/** Remember-me cookie lifetime (30 days). */
const ADMIN_REMEMBER_DURATION = 30 * 24 * 3600;

/** Remember-me cookie name. */
const ADMIN_REMEMBER_COOKIE = 'BRE_ADMIN_REMEMBER';

/** Password-reset token lifetime (1 hour). */
const ADMIN_PASSWORD_RESET_DURATION = 3600;

/** Max forgot-password requests per IP per hour. */
const ADMIN_PASSWORD_RESET_MAX_PER_HOUR = 5;

class AuthSecurity
{
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto === 'https') {
            return true;
        }

        $forwardedSsl = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));

        return $forwardedSsl === 'on';
    }

    /**
     * Shared cookie path for all admin pages and admin API routes.
     */
    public static function adminCookiePath(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/');
        $pos = strpos($script, '/admin/');

        if ($pos !== false) {
            return substr($script, 0, $pos + 7);
        }

        return '/';
    }

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

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => self::adminCookiePath(),
            'domain'   => '',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('BRE_ADMIN_SID');
        session_start();
    }

    /**
     * Create remember-me table if missing.
     */
    public static function ensureRememberSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS admin_remember_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT UNSIGNED NOT NULL,
                selector VARCHAR(32) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_selector (selector),
                KEY idx_admin (admin_id),
                KEY idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $done = true;
    }

    /**
     * Cookie path scoped to the admin directory.
     */
    private static function rememberCookiePath(): string
    {
        return self::adminCookiePath();
    }

    /** @param array<string, mixed> $options */
    private static function setRememberCookie(string $value, int $expires): void
    {
        setcookie(ADMIN_REMEMBER_COOKIE, $value, [
            'expires'  => $expires,
            'path'     => self::adminCookiePath(),
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Issue a persistent login token when "Remember me" is checked.
     */
    public static function issueRememberToken(int $adminId): void
    {
        self::ensureRememberSchema();

        $pdo = getDatabaseConnection();
        $pdo->prepare('DELETE FROM admin_remember_tokens WHERE admin_id = :admin_id')
            ->execute(['admin_id' => $adminId]);

        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_REMEMBER_DURATION);

        $pdo = getDatabaseConnection();
        $pdo->prepare(
            'INSERT INTO admin_remember_tokens (admin_id, selector, token_hash, expires_at)
             VALUES (:admin_id, :selector, :token_hash, :expires_at)'
        )->execute([
            'admin_id'   => $adminId,
            'selector'   => $selector,
            'token_hash' => hash('sha256', $validator),
            'expires_at' => $expiresAt,
        ]);

        self::setRememberCookie($selector . ':' . $validator, time() + ADMIN_REMEMBER_DURATION);
        self::purgeExpiredRememberTokens();
    }

    /**
     * Restore session from remember-me cookie when valid.
     */
    public static function attemptRememberLogin(): bool
    {
        if (!empty($_SESSION['admin_logged_in'])) {
            return true;
        }

        $raw = $_COOKIE[ADMIN_REMEMBER_COOKIE] ?? '';
        if ($raw === '' || !str_contains($raw, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $raw, 2);
        if ($selector === '' || $validator === '' || strlen($selector) !== 32 || strlen($validator) !== 64) {
            self::clearRememberCookie();
            return false;
        }

        self::ensureRememberSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT t.id AS token_id, t.token_hash, t.expires_at, u.id, u.email, u.full_name, u.role, u.permissions_json, u.is_active
             FROM admin_remember_tokens t
             INNER JOIN admin_users u ON u.id = t.admin_id
             WHERE t.selector = :selector
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $row = $stmt->fetch();

        if (!$row || !(int) $row['is_active']) {
            self::clearRememberCookie();
            return false;
        }

        if (strtotime((string) $row['expires_at']) <= time()) {
            self::revokeRememberTokenById((int) $row['token_id']);
            self::clearRememberCookie();
            return false;
        }

        if (!hash_equals((string) $row['token_hash'], hash('sha256', $validator))) {
            self::revokeRememberTokenById((int) $row['token_id']);
            self::clearRememberCookie();
            self::auditLog('remember_token_invalid', (int) $row['id'], 'Invalid remember-me token rejected');
            return false;
        }

        self::createAdminSession([
            'id'        => (int) $row['id'],
            'email'     => (string) $row['email'],
            'full_name' => (string) $row['full_name'],
            'role'      => (string) $row['role'],
        ]);
        $_SESSION['remember_me'] = true;
        self::auditLog('remember_login', (int) $row['id'], 'Session restored via remember-me cookie');

        return true;
    }

    public static function clearRememberCookie(): void
    {
        if (!isset($_COOKIE[ADMIN_REMEMBER_COOKIE])) {
            return;
        }

        self::setRememberCookie('', time() - 3600);
        unset($_COOKIE[ADMIN_REMEMBER_COOKIE]);
    }

    /**
     * Remove remember token from DB and clear cookie.
     */
    public static function clearRememberToken(?string $rawCookie = null): void
    {
        $raw = $rawCookie ?? ($_COOKIE[ADMIN_REMEMBER_COOKIE] ?? '');
        if ($raw !== '' && str_contains($raw, ':')) {
            [$selector] = explode(':', $raw, 2);
            if ($selector !== '') {
                self::ensureRememberSchema();
                $pdo = getDatabaseConnection();
                $pdo->prepare('DELETE FROM admin_remember_tokens WHERE selector = :selector')
                    ->execute(['selector' => $selector]);
            }
        }

        self::clearRememberCookie();
    }

    public static function revokeRememberTokensForAdmin(int $adminId): void
    {
        self::ensureRememberSchema();
        $pdo = getDatabaseConnection();
        $pdo->prepare('DELETE FROM admin_remember_tokens WHERE admin_id = :admin_id')
            ->execute(['admin_id' => $adminId]);
    }

    private static function revokeRememberTokenById(int $tokenId): void
    {
        self::ensureRememberSchema();
        $pdo = getDatabaseConnection();
        $pdo->prepare('DELETE FROM admin_remember_tokens WHERE id = :id')
            ->execute(['id' => $tokenId]);
    }

    public static function purgeExpiredRememberTokens(): void
    {
        self::ensureRememberSchema();
        $pdo = getDatabaseConnection();
        $pdo->exec('DELETE FROM admin_remember_tokens WHERE expires_at <= NOW()');
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
            if (self::attemptRememberLogin()) {
                return true;
            }

            return false;
        }

        if (!self::validateSessionFingerprint()) {
            self::destroySession(false);
            if (self::attemptRememberLogin()) {
                return true;
            }

            return false;
        }

        if (self::isSessionExpired()) {
            $expiredAdminId = (int) ($_SESSION['admin_id'] ?? 0);
            self::destroySession(false);
            if (self::attemptRememberLogin()) {
                return true;
            }

            if ($expiredAdminId > 0) {
                self::auditLog('session_expired', $expiredAdminId, 'Session timed out after inactivity');
            }

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
        if (!empty($_SESSION['remember_me'])) {
            return false;
        }

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
     *
     * @param bool $clearRemember When true, also revoke remember-me token (logout).
     */
    public static function destroySession(bool $clearRemember = true): void
    {
        if ($clearRemember) {
            self::clearRememberToken();
        }

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

        self::revokeRememberTokensForAdmin($adminId);
        self::clearRememberCookie();
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
        self::revokeRememberTokensForAdmin($adminId);
        self::auditLog('logout', $adminId, 'Admin account deactivated by user');
        self::destroySession();
    }

    /**
     * Ensure password-reset token table exists.
     */
    public static function ensurePasswordResetSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS admin_password_resets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT UNSIGNED NOT NULL,
                selector VARCHAR(32) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                requested_ip VARCHAR(45) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_selector (selector),
                KEY idx_admin (admin_id),
                KEY idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $done = true;
    }

    /**
     * Absolute URL helper for admin reset emails.
     */
    public static function absoluteAdminUrl(string $relativePath, array $query = []): string
    {
        require_once __DIR__ . '/site_paths.php';

        $path = siteUrl(ltrim($relativePath, '/'));
        if ($query !== []) {
            $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
        }

        $scheme = self::isHttps() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * Request a password reset. Always returns a generic success payload
     * so callers do not leak whether an account exists.
     *
     * @return array{ok:bool,message:string,rate_limited?:bool}
     */
    public static function requestPasswordReset(string $email): array
    {
        self::ensurePasswordResetSchema();

        $generic = 'If an account exists for that email, a password reset link has been sent. Check your inbox and spam folder.';
        $email = mb_strtolower(trim($email));
        $ip = self::getClientIp();

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Please enter a valid email address.'];
        }

        $pdo = getDatabaseConnection();
        $rate = $pdo->prepare(
            'SELECT COUNT(*) FROM admin_password_resets
             WHERE requested_ip = :ip AND created_at >= (NOW() - INTERVAL 1 HOUR)'
        );
        $rate->execute(['ip' => $ip]);
        if ((int) $rate->fetchColumn() >= ADMIN_PASSWORD_RESET_MAX_PER_HOUR) {
            self::auditLog('password_reset_rate_limited', null, 'Too many reset requests from ' . $ip, $ip);
            return [
                'ok'           => false,
                'message'      => 'Too many reset requests from this network. Please try again later.',
                'rate_limited' => true,
            ];
        }

        require_once __DIR__ . '/AdminUserRepository.php';
        AdminUserRepository::ensureSchema();

        $stmt = $pdo->prepare(
            'SELECT id, email, full_name, is_active
             FROM admin_users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        // Always acknowledge the same way for valid-looking requests.
        if (!$admin || !(int) $admin['is_active']) {
            self::auditLog(
                'password_reset_request',
                null,
                'Reset requested for unknown/inactive email: ' . $email,
                $ip
            );
            return ['ok' => true, 'message' => $generic];
        }

        $adminId = (int) $admin['id'];
        $pdo->prepare(
            'UPDATE admin_password_resets SET used_at = NOW()
             WHERE admin_id = :id AND used_at IS NULL'
        )->execute(['id' => $adminId]);

        $pdo->prepare('DELETE FROM admin_password_resets WHERE expires_at < (NOW() - INTERVAL 7 DAY)')
            ->execute();

        $selector  = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_PASSWORD_RESET_DURATION);

        $pdo->prepare(
            'INSERT INTO admin_password_resets (admin_id, selector, token_hash, expires_at, requested_ip)
             VALUES (:admin_id, :selector, :token_hash, :expires_at, :ip)'
        )->execute([
            'admin_id'   => $adminId,
            'selector'   => $selector,
            'token_hash' => hash('sha256', $validator),
            'expires_at' => $expiresAt,
            'ip'         => $ip,
        ]);

        $resetLink = self::absoluteAdminUrl('admin/admin-reset-password.php', [
            'token' => $selector . ':' . $validator,
        ]);

        $name = trim((string) ($admin['full_name'] ?? '')) ?: 'Administrator';

        try {
            require_once __DIR__ . '/AutomatedEmailService.php';
            AutomatedEmailService::onPasswordResetRequested(
                (string) $admin['email'],
                $name,
                $resetLink
            );
        } catch (Throwable $e) {
            error_log('Admin password reset email failed: ' . $e->getMessage());
        }

        self::auditLog('password_reset_request', $adminId, 'Password reset link issued', $ip);

        return ['ok' => true, 'message' => $generic];
    }

    /**
     * Validate a reset token from the email link.
     *
     * @return array{id:int,admin_id:int,email:string,full_name:string}|null
     */
    public static function validatePasswordResetToken(string $token): ?array
    {
        self::ensurePasswordResetSchema();

        if ($token === '' || !str_contains($token, ':')) {
            return null;
        }

        [$selector, $validator] = explode(':', $token, 2);
        if (
            $selector === ''
            || $validator === ''
            || strlen($selector) !== 32
            || strlen($validator) !== 64
        ) {
            return null;
        }

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT r.id, r.token_hash, r.expires_at, r.used_at, r.admin_id,
                    u.email, u.full_name, u.is_active
             FROM admin_password_resets r
             INNER JOIN admin_users u ON u.id = r.admin_id
             WHERE r.selector = :selector
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $row = $stmt->fetch();

        if (
            !$row
            || $row['used_at'] !== null
            || !(int) $row['is_active']
            || strtotime((string) $row['expires_at']) < time()
        ) {
            return null;
        }

        if (!hash_equals((string) $row['token_hash'], hash('sha256', $validator))) {
            return null;
        }

        return [
            'id'        => (int) $row['id'],
            'admin_id'  => (int) $row['admin_id'],
            'email'     => (string) $row['email'],
            'full_name' => (string) ($row['full_name'] ?? 'Administrator'),
        ];
    }

    /**
     * Consume a valid reset token and set a new password.
     */
    public static function resetPasswordWithToken(string $token, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('New password must be at least 8 characters.');
        }

        $payload = self::validatePasswordResetToken($token);
        if ($payload === null) {
            throw new RuntimeException('This reset link is invalid or has expired. Please request a new one.');
        }

        $pdo = getDatabaseConnection();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id')
                ->execute(['hash' => $hash, 'id' => $payload['admin_id']]);

            $pdo->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE id = :id')
                ->execute(['id' => $payload['id']]);

            $pdo->prepare('DELETE FROM admin_password_resets WHERE admin_id = :admin_id AND id != :id')
                ->execute(['admin_id' => $payload['admin_id'], 'id' => $payload['id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        self::revokeRememberTokensForAdmin($payload['admin_id']);
        self::clearRememberCookie();
        self::auditLog(
            'password_reset_completed',
            $payload['admin_id'],
            'Password reset completed via email link for ' . $payload['email']
        );
    }
}
