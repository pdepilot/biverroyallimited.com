<?php
/**
 * Password reset handler — processes POST from admin-reset-password.php.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AuthSecurity.php';

AuthSecurity::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-login.php');
    exit;
}

$token            = trim((string) ($_POST['token'] ?? ''));
$password         = (string) ($_POST['password'] ?? '');
$passwordConfirm  = (string) ($_POST['password_confirm'] ?? '');
$csrfToken        = (string) ($_POST['csrf_token'] ?? '');
$redirectWithToken = 'admin-reset-password.php?token=' . rawurlencode($token);

if (!AuthSecurity::validateCsrfToken($csrfToken)) {
    AuthSecurity::auditLog('csrf_failure', null, 'Invalid CSRF token on password reset');
    AuthSecurity::setFlash('error', 'Security validation failed. Please refresh the page and try again.');
    header('Location: ' . $redirectWithToken);
    exit;
}

if ($token === '') {
    AuthSecurity::setFlash('error', 'Missing reset token.');
    header('Location: admin-forgot-password.php');
    exit;
}

if ($password === '' || $passwordConfirm === '') {
    AuthSecurity::setFlash('error', 'Please enter and confirm your new password.');
    header('Location: ' . $redirectWithToken);
    exit;
}

if ($password !== $passwordConfirm) {
    AuthSecurity::setFlash('error', 'Passwords do not match.');
    header('Location: ' . $redirectWithToken);
    exit;
}

try {
    AuthSecurity::resetPasswordWithToken($token, $password);
    AuthSecurity::setFlash('success', 'Your password has been updated. You can now log in with your new password.');
    header('Location: admin-login.php');
    exit;
} catch (InvalidArgumentException $e) {
    AuthSecurity::setFlash('error', $e->getMessage());
    header('Location: ' . $redirectWithToken);
    exit;
} catch (RuntimeException $e) {
    AuthSecurity::setFlash('error', $e->getMessage());
    header('Location: admin-forgot-password.php');
    exit;
} catch (Throwable $e) {
    error_log('Admin password reset failed: ' . $e->getMessage());
    AuthSecurity::setFlash('error', 'Unable to reset password right now. Please try again.');
    header('Location: ' . $redirectWithToken);
    exit;
}
