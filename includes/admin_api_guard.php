<?php
/**
 * JSON auth guard for admin API endpoints.
 */

declare(strict_types=1);

require_once __DIR__ . '/AuthSecurity.php';
require_once __DIR__ . '/AdminPermissions.php';

AuthSecurity::initSession();

if (!AuthSecurity::isAuthenticated()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

if (empty($_SESSION['admin_permissions']) && !empty($_SESSION['admin_id'])) {
    AdminPermissions::loadSessionPermissions((int) $_SESSION['admin_id']);
}
