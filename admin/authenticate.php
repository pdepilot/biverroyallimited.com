<?php
/**
 * Login authentication handler — processes POST from admin-login.php.
 * Uses PDO prepared statements, CSRF validation, and progressive IP lockouts.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AuthSecurity.php';

AuthSecurity::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-login.php');
    exit;
}

$ip        = AuthSecurity::getClientIp();
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

if (!AuthSecurity::validateCsrfToken($csrfToken)) {
    AuthSecurity::auditLog('csrf_failure', null, 'Invalid or missing CSRF token on login');
    AuthSecurity::setFlash('error', 'Security validation failed. Please refresh the page and try again.');
    header('Location: admin-login.php');
    exit;
}

$lockout = AuthSecurity::getActiveLockout($ip);

if ($lockout !== null) {
    if ((int) $lockout['requires_manual_review'] === 1) {
        AuthSecurity::auditLog('manual_review_block', null, 'Login blocked — manual review required', $ip);
        AuthSecurity::setFlash(
            'lockout',
            'Your IP address has been flagged for repeated failed login attempts. Access requires manual administrator review. Please contact support.'
        );
        AuthSecurity::setFlash('lockout_manual', true);
    } else {
        $remaining = AuthSecurity::formatRemainingTime($lockout['expires_at']);
        AuthSecurity::setFlash(
            'lockout',
            'Access temporarily restricted due to multiple failed login attempts. Time remaining: ' . $remaining . '.'
        );
        AuthSecurity::setFlash('lockout_expires', $lockout['expires_at']);
    }
    header('Location: admin-login.php');
    exit;
}

if ($email === '' || $password === '') {
    AuthSecurity::setFlash('error', 'Please enter both email and password.');
    header('Location: admin-login.php');
    exit;
}

$admin = AuthSecurity::verifyCredentials($email, $password);

if ($admin !== null) {
    AuthSecurity::resetLoginAttempts($ip);
    require_once dirname(__DIR__) . '/includes/AdminUserRepository.php';
    AdminUserRepository::recordLogin((int) $admin['id']);
    AuthSecurity::createAdminSession($admin);
    AuthSecurity::auditLog(
        'login_success',
        (int) $admin['id'],
        'Successful login for ' . $admin['email']
    );

    header('Location: admin-dashboard.php');
    exit;
}

$failedCount = AuthSecurity::recordFailedAttempt($ip);

AuthSecurity::auditLog(
    'login_failed',
    null,
    sprintf('Failed login attempt #%d for email: %s', $failedCount, $email)
);

if ($failedCount >= MAX_ATTEMPTS_BEFORE_LOCKOUT) {
    $lockoutInfo = AuthSecurity::applyIpLockout(
        $ip,
        'Exceeded maximum failed login attempts (' . MAX_ATTEMPTS_BEFORE_LOCKOUT . ')'
    );

    if ($lockoutInfo['manual']) {
        AuthSecurity::setFlash(
            'lockout',
            'Too many failed attempts. Your IP has been flagged and requires manual administrator review before you can log in again.'
        );
        AuthSecurity::setFlash('lockout_manual', true);
    } else {
        $remaining = AuthSecurity::formatRemainingTime($lockoutInfo['expires_at']);
        $levelMsg = $lockoutInfo['level'] === 2
            ? 'Due to repeated violations, access is restricted for 30 days.'
            : 'Access has been temporarily restricted for 72 hours.';
        AuthSecurity::setFlash(
            'lockout',
            $levelMsg . ' Time remaining: ' . $remaining . '.'
        );
        AuthSecurity::setFlash('lockout_expires', $lockoutInfo['expires_at']);
    }

    header('Location: admin-login.php');
    exit;
}

if ($failedCount === MAX_ATTEMPTS_BEFORE_WARNING) {
    AuthSecurity::setFlash('warning', true);
    AuthSecurity::setFlash(
        'error',
        'Invalid credentials. Warning: You have 1 login attempt remaining. One more failed attempt will result in a temporary restriction.'
    );
} else {
    $remaining = MAX_ATTEMPTS_BEFORE_LOCKOUT - $failedCount;
    AuthSecurity::setFlash(
        'error',
        'Invalid email or password. ' . $remaining . ' attempt' . ($remaining !== 1 ? 's' : '') . ' remaining before restriction.'
    );
}

header('Location: admin-login.php');
exit;
