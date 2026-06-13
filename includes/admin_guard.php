<?php
/**
 * Include at the top of every protected admin page.
 * Redirects unauthenticated users to admin-login.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/AuthSecurity.php';
require_once __DIR__ . '/AdminPermissions.php';

AuthSecurity::initSession();

if (!AuthSecurity::isAuthenticated()) {
    header('Location: admin-login.php');
    exit;
}

if (empty($_SESSION['admin_permissions']) && !empty($_SESSION['admin_id'])) {
    AdminPermissions::loadSessionPermissions((int) $_SESSION['admin_id']);
}
