<?php

/**
 * Outbound mail settings for contact replies (PHPMailer + SMTP).
 *
 * 1. Copy config/mail.local.php.example → config/mail.local.php
 * 2. Or configure via Admin → SMTP Settings (writes mail.local.php)
 * 3. Run: composer install (from project root)
 */

declare(strict_types=1);

$mailLocal = __DIR__ . '/mail.local.php';
if (is_readable($mailLocal)) {
    require $mailLocal;
}

if (!defined('MAIL_PROVIDER')) {
    define('MAIL_PROVIDER', 'hostinger');
}
if (!defined('MAIL_USE_SMTP')) {
    define('MAIL_USE_SMTP', true);
}
if (!defined('MAIL_SMTP_HOST')) {
    define('MAIL_SMTP_HOST', 'smtp.hostinger.com');
}
if (!defined('MAIL_SMTP_PORT')) {
    define('MAIL_SMTP_PORT', 587);
}
if (!defined('MAIL_SMTP_ENCRYPTION')) {
    define('MAIL_SMTP_ENCRYPTION', 'tls');
}
if (!defined('MAIL_SMTP_USERNAME')) {
    define('MAIL_SMTP_USERNAME', '');
}
if (!defined('MAIL_SMTP_PASSWORD')) {
    define('MAIL_SMTP_PASSWORD', '');
}
if (!defined('MAIL_SMTP_TIMEOUT')) {
    define('MAIL_SMTP_TIMEOUT', 30);
}
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', '');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', 'Biver Royalty Homes');
}
if (!defined('MAIL_REPLY_TO')) {
    define('MAIL_REPLY_TO', '');
}
if (!defined('MAIL_NOTIFY_EMAIL')) {
    define('MAIL_NOTIFY_EMAIL', '');
}
if (!defined('MAIL_NOTIFY_ON_CONTACT')) {
    define('MAIL_NOTIFY_ON_CONTACT', true);
}
