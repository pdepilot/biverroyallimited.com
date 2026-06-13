<?php
/**
 * Secure admin logout — destroys session and records audit event.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AuthSecurity.php';

AuthSecurity::initSession();

$adminId = !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;

if ($adminId !== null) {
    AuthSecurity::auditLog(
        'logout',
        $adminId,
        'Administrator logged out'
    );
}

AuthSecurity::destroySession();

header('Location: admin-login.php?logged_out=1');
exit;
