<?php
declare(strict_types=1);

/**
 * Mail configuration — providers, local secrets, admin read/write.
 */
final class MailConfigService
{
    private const LOCAL_FILE = 'config/mail.local.php';

    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /** @return array<string, string> */
    public static function providers(): array
    {
        return [
            'gmail'    => 'Gmail (Google SMTP)',
            'sendgrid' => 'SendGrid',
            'brevo'    => 'Brevo (Sendinblue)',
            'custom'   => 'Custom SMTP',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'provider'          => 'gmail',
            'useSmtp'           => true,
            'host'              => 'smtp.gmail.com',
            'port'              => 587,
            'encryption'        => 'tls',
            'username'          => 'biverroyaltyhomes01@gmail.com',
            'password'          => 'ntsn fqrc ceay xnui',
            'timeout'           => 30,
            'fromEmail'         => 'biverroyaltyhomes01@gmail.com',
            'fromName'          => 'Biver Royalty Homes',
            'replyTo'           => 'biverroyaltyhomes01@gmail.com',
            'notifyEmail'       => 'biverroyaltyhomes01@gmail.com',
            'notifyOnContact'   => true,
        ];
    }

    public static function localPath(): string
    {
        return dirname(__DIR__) . '/' . self::LOCAL_FILE;
    }

    public static function ensureLoaded(): void
    {
        if (self::$cache !== null) {
            return;
        }

        require_once dirname(__DIR__) . '/config/mail.php';

        self::$cache = [
            'provider'        => self::envOrConst('SMTP_PROVIDER', 'MAIL_PROVIDER', 'gmail'),
            'useSmtp'         => self::envBool('SMTP_USE', 'MAIL_USE_SMTP', true),
            'host'            => self::envOrConst('SMTP_HOST', 'MAIL_SMTP_HOST', ''),
            'port'            => (int) self::envOrConst('SMTP_PORT', 'MAIL_SMTP_PORT', '587'),
            'encryption'      => self::envOrConst('SMTP_ENCRYPTION', 'MAIL_SMTP_ENCRYPTION', 'tls'),
            'username'        => self::envOrConst('SMTP_USERNAME', 'MAIL_SMTP_USERNAME', ''),
            'password'        => self::envOrConst('SMTP_PASSWORD', 'MAIL_SMTP_PASSWORD', ''),
            'timeout'         => (int) self::envOrConst('SMTP_TIMEOUT', 'MAIL_SMTP_TIMEOUT', '30'),
            'fromEmail'       => self::envOrConst('SMTP_FROM_EMAIL', 'MAIL_FROM_EMAIL', ''),
            'fromName'        => self::envOrConst('SMTP_FROM_NAME', 'MAIL_FROM_NAME', 'Biver Royalty Homes'),
            'replyTo'         => self::envOrConst('SMTP_REPLY_TO', 'MAIL_REPLY_TO', ''),
            'notifyEmail'     => self::envOrConst('SMTP_NOTIFY_EMAIL', 'MAIL_NOTIFY_EMAIL', ''),
            'notifyOnContact' => self::envBool('SMTP_NOTIFY_ON_CONTACT', 'MAIL_NOTIFY_ON_CONTACT', true),
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        self::ensureLoaded();
        return self::$cache ?? self::defaults();
    }

    /** Safe for admin JSON — never exposes password. */
    /** @return array<string, mixed> */
    public static function getPublic(): array
    {
        $config = self::get();

        return [
            'provider'          => $config['provider'],
            'useSmtp'           => (bool) $config['useSmtp'],
            'host'              => $config['host'],
            'port'              => (int) $config['port'],
            'encryption'        => $config['encryption'],
            'username'          => $config['username'],
            'fromEmail'         => $config['fromEmail'],
            'fromName'          => $config['fromName'],
            'replyTo'           => $config['replyTo'],
            'notifyEmail'       => $config['notifyEmail'],
            'notifyOnContact'   => (bool) $config['notifyOnContact'],
            'passwordSet'       => ($config['password'] ?? '') !== '',
            'composerInstalled' => is_readable(dirname(__DIR__) . '/vendor/autoload.php'),
            'isReady'           => self::isReady(),
        ];
    }

    public static function isReady(): bool
    {
        $config = self::get();
        if (!$config['useSmtp']) {
            return true;
        }

        return ($config['host'] ?? '') !== ''
            && ($config['username'] ?? '') !== ''
            && ($config['password'] ?? '') !== ''
            && is_readable(dirname(__DIR__) . '/vendor/autoload.php');
    }

    /** @param array<string, mixed> $input */
    public static function save(array $input): bool
    {
        $current = self::get();
        $provider = self::sanitizeProvider((string) ($input['provider'] ?? $current['provider']));

        $config = array_merge($current, [
            'provider'        => $provider,
            'useSmtp'         => filter_var($input['useSmtp'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'host'            => trim((string) ($input['host'] ?? '')),
            'port'            => max(1, min(65535, (int) ($input['port'] ?? 587))),
            'encryption'      => self::sanitizeEncryption((string) ($input['encryption'] ?? 'tls')),
            'username'        => trim((string) ($input['username'] ?? '')),
            'fromEmail'       => trim((string) ($input['fromEmail'] ?? '')),
            'fromName'        => self::clip((string) ($input['fromName'] ?? ''), 120),
            'replyTo'         => trim((string) ($input['replyTo'] ?? '')),
            'notifyEmail'     => trim((string) ($input['notifyEmail'] ?? '')),
            'notifyOnContact' => filter_var($input['notifyOnContact'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'timeout'         => max(5, min(120, (int) ($input['timeout'] ?? 30))),
        ]);

        $newPassword = trim((string) ($input['password'] ?? ''));
        if ($newPassword !== '') {
            $config['password'] = $newPassword;
        } else {
            $config['password'] = (string) ($current['password'] ?? '');
        }

        $config = self::applyProviderDefaults($config);

        if ($config['fromEmail'] !== '' && !filter_var($config['fromEmail'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid From email address.');
        }
        if ($config['replyTo'] !== '' && !filter_var($config['replyTo'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid Reply-To email address.');
        }
        if ($config['notifyEmail'] !== '' && !filter_var($config['notifyEmail'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid notification email address.');
        }

        if ($config['useSmtp'] && ($config['password'] ?? '') === '') {
            throw new InvalidArgumentException('SMTP password is required.');
        }

        $php = self::buildLocalFile($config);
        $written = file_put_contents(self::localPath(), $php) !== false;

        if ($written) {
            self::$cache = null;
            self::ensureLoaded();
        }

        return $written;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private static function applyProviderDefaults(array $config): array
    {
        $presets = [
            'gmail' => [
                'host'       => 'smtp.gmail.com',
                'port'       => 587,
                'encryption' => 'tls',
            ],
            'sendgrid' => [
                'host'       => 'smtp.sendgrid.net',
                'port'       => 587,
                'encryption' => 'tls',
            ],
            'brevo' => [
                'host'       => 'smtp-relay.brevo.com',
                'port'       => 587,
                'encryption' => 'tls',
            ],
        ];

        $provider = $config['provider'] ?? 'custom';
        if ($provider !== 'custom' && isset($presets[$provider])) {
            $config = array_merge($config, $presets[$provider]);
            if ($provider === 'sendgrid' && ($config['username'] ?? '') === '') {
                $config['username'] = 'apikey';
            }
        }

        return $config;
    }

    /** @param array<string, mixed> $config */
    private static function buildLocalFile(array $config): string
    {
        $bool = static fn (bool $v): string => $v ? 'true' : 'false';

        return "<?php\n"
            . "declare(strict_types=1);\n\n"
            . "/** Auto-generated by Admin → Settings → Email. Do not commit to git. */\n"
            . "define('MAIL_PROVIDER', " . var_export((string) $config['provider'], true) . ");\n"
            . "define('MAIL_USE_SMTP', " . $bool((bool) $config['useSmtp']) . ");\n"
            . "define('MAIL_SMTP_HOST', " . var_export((string) $config['host'], true) . ");\n"
            . "define('MAIL_SMTP_PORT', " . (int) $config['port'] . ");\n"
            . "define('MAIL_SMTP_ENCRYPTION', " . var_export((string) $config['encryption'], true) . ");\n"
            . "define('MAIL_SMTP_USERNAME', " . var_export((string) $config['username'], true) . ");\n"
            . "define('MAIL_SMTP_PASSWORD', " . var_export((string) $config['password'], true) . ");\n"
            . "define('MAIL_SMTP_TIMEOUT', " . (int) $config['timeout'] . ");\n"
            . "define('MAIL_FROM_EMAIL', " . var_export((string) $config['fromEmail'], true) . ");\n"
            . "define('MAIL_FROM_NAME', " . var_export((string) $config['fromName'], true) . ");\n"
            . "define('MAIL_REPLY_TO', " . var_export((string) $config['replyTo'], true) . ");\n"
            . "define('MAIL_NOTIFY_EMAIL', " . var_export((string) $config['notifyEmail'], true) . ");\n"
            . "define('MAIL_NOTIFY_ON_CONTACT', " . $bool((bool) $config['notifyOnContact']) . ");\n";
    }

    private static function sanitizeProvider(string $provider): string
    {
        return array_key_exists($provider, self::providers()) ? $provider : 'gmail';
    }

    private static function sanitizeEncryption(string $encryption): string
    {
        $encryption = strtolower(trim($encryption));
        return in_array($encryption, ['tls', 'ssl', 'none'], true) ? $encryption : 'tls';
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }

    private static function envOrConst(string $envKey, string $constName, string $default = ''): string
    {
        $env = getenv($envKey);
        if ($env !== false && $env !== '') {
            return (string) $env;
        }
        if (defined($constName)) {
            return (string) constant($constName);
        }

        return $default;
    }

    private static function envBool(string $envKey, string $constName, bool $default = true): bool
    {
        $env = getenv($envKey);
        if ($env !== false && $env !== '') {
            return filter_var($env, FILTER_VALIDATE_BOOLEAN);
        }
        if (defined($constName)) {
            return (bool) constant($constName);
        }

        return $default;
    }
}
