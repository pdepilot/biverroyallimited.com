<?php
/**
 * One-time mail config check (safe — does not print passwords).
 * Visit: https://biverroyaltyhomesltd.com/tools/check-mail-config.php
 * DELETE this file after email is working.
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/includes/MailConfigService.php';

MailConfigService::ensureLoaded();
$public = MailConfigService::getPublic();
$config = MailConfigService::get();

echo "Biver Royalty Homes — Mail Config Check\n";
echo "========================================\n\n";
echo 'mail.local.php exists: ' . ($public['mailLocalExists'] ? 'YES' : 'NO — UPLOAD config/mail.local.php') . "\n";
echo 'vendor/autoload.php:   ' . ($public['composerInstalled'] ? 'YES' : 'NO — run composer install or upload vendor/') . "\n";
echo 'SMTP password saved:   ' . ($public['passwordSet'] ? 'YES' : 'NO') . "\n";
echo 'SMTP ready:            ' . ($public['isReady'] ? 'YES' : 'NO') . "\n\n";
echo 'Provider:    ' . ($public['provider'] ?? '') . "\n";
echo 'Host:        ' . ($public['host'] ?? '') . "\n";
echo 'Port:        ' . ($public['port'] ?? '') . "\n";
echo 'Encryption:  ' . ($public['encryption'] ?? '') . "\n";
echo 'Username:    ' . ($public['username'] ?? '') . "\n";
echo 'From email:  ' . ($public['fromEmail'] ?? '') . "\n";
echo 'Reply-To:    ' . ($public['replyTo'] ?? '') . "\n\n";

if (($config['password'] ?? '') === '') {
    echo "FAIL: Password is empty — mail.local.php is not loading correctly.\n";
    echo "Fix: Upload the fixed config/mail.php (loads mail.local.php FIRST).\n";
    exit(1);
}

if (!($public['composerInstalled'] ?? false)) {
    echo "FAIL: Upload the vendor/ folder or run composer install on the server.\n";
    exit(1);
}

echo "Config looks OK. Use Admin → SMTP Settings → Diagnose SMTP, then Send Test Email.\n";
echo "Delete tools/check-mail-config.php when done.\n";
