<?php
/**
 * Forgot-password request handler — processes POST from admin-forgot-password.php.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AuthSecurity.php';

AuthSecurity::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-forgot-password.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!AuthSecurity::validateCsrfToken($csrfToken)) {
    AuthSecurity::auditLog('csrf_failure', null, 'Invalid CSRF token on password reset request');
    AuthSecurity::setFlash('error', 'Security validation failed. Please refresh the page and try again.');
    header('Location: admin-forgot-password.php');
    exit;
}

$email  = trim((string) ($_POST['email'] ?? ''));
$result = AuthSecurity::requestPasswordReset($email);

if (!empty($result['ok'])) {
    AuthSecurity::setFlash('success', (string) $result['message']);
} else {
    AuthSecurity::setFlash('error', (string) ($result['message'] ?? 'Unable to process that request.'));
}

header('Location: admin-forgot-password.php');
exit;
