<?php

/**

 * Outbound mail settings for contact replies (PHPMailer + SMTP).

 *

 * 1. Copy config/mail.local.php.example → config/mail.local.php

 * 2. Or configure via Admin → Settings → Email Configuration

 * 3. Run: composer install (from project root)

 */



declare(strict_types=1);



define('MAIL_PROVIDER', 'gmail');

define('MAIL_USE_SMTP', true);

define('MAIL_SMTP_HOST', 'smtp.gmail.com');

define('MAIL_SMTP_PORT', 587);

define('MAIL_SMTP_ENCRYPTION', 'tls');

define('MAIL_SMTP_USERNAME', 'biverroyaltyhomes01@gmail.com');

define('MAIL_SMTP_PASSWORD', '');

define('MAIL_SMTP_TIMEOUT', 30);



define('MAIL_FROM_EMAIL', 'biverroyaltyhomes01@gmail.com');

define('MAIL_FROM_NAME', 'Biver Royalty Homes');

define('MAIL_REPLY_TO', 'biverroyaltyhomes01@gmail.com');

define('MAIL_NOTIFY_EMAIL', 'biverroyaltyhomes01@gmail.com');

define('MAIL_NOTIFY_ON_CONTACT', true);



/** Optional: load secrets from mail.local.php (overrides values above). */

$mailLocal = __DIR__ . '/mail.local.php';

if (is_readable($mailLocal)) {

    require $mailLocal;

}


