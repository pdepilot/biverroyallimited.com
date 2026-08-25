<?php
/**
 * Resolve a visitor IP to country code + name for newsletter analytics.
 * Uses CDN headers first (Cloudflare), then a short HTTPS lookup.
 */

declare(strict_types=1);

final class GeoIpService
{
    /**
     * @return array{ip:string,country_code:?string,country_name:?string}
     */
    public static function lookup(?string $ip = null): array
    {
        $ip = $ip !== null && $ip !== '' ? $ip : self::clientIp();

        if ($ip === '' || self::isPrivateIp($ip)) {
            return [
                'ip'           => $ip !== '' ? $ip : null,
                'country_code' => 'LO',
                'country_name' => 'Local network',
            ];
        }

        $headerCode = self::headerCountryCode();
        if ($headerCode !== null) {
            return [
                'ip'           => $ip,
                'country_code' => $headerCode,
                'country_name' => self::countryNameFromCode($headerCode),
            ];
        }

        $remote = self::lookupRemote($ip);
        if ($remote !== null) {
            return $remote;
        }

        return [
            'ip'           => $ip,
            'country_code' => null,
            'country_name' => null,
        ];
    }

    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['HTTP_X_REAL_IP'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $raw) {
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            $first = trim(explode(',', $raw)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        return '';
    }

    private static function headerCountryCode(): ?string
    {
        foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_APPENGINE_COUNTRY'] as $key) {
            $code = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
            if ($code !== '' && $code !== 'XX' && preg_match('/^[A-Z]{2}$/', $code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @return array{ip:string,country_code:?string,country_name:?string}|null
     */
    private static function lookupRemote(string $ip): ?array
    {
        $url = 'https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country,country_code';
        $json = self::httpGet($url);
        if ($json !== null) {
            $data = json_decode($json, true);
            if (is_array($data) && !empty($data['success'])) {
                $code = strtoupper(trim((string) ($data['country_code'] ?? '')));
                $name = trim((string) ($data['country'] ?? ''));
                if ($code !== '' || $name !== '') {
                    return [
                        'ip'           => $ip,
                        'country_code' => $code !== '' ? $code : null,
                        'country_name' => $name !== '' ? $name : ($code !== '' ? self::countryNameFromCode($code) : null),
                    ];
                }
            }
        }

        $fallback = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,countryCode';
        $json = self::httpGet($fallback);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return null;
        }
        $code = strtoupper(trim((string) ($data['countryCode'] ?? '')));
        $name = trim((string) ($data['country'] ?? ''));

        return [
            'ip'           => $ip,
            'country_code' => $code !== '' ? $code : null,
            'country_name' => $name !== '' ? $name : ($code !== '' ? self::countryNameFromCode($code) : null),
        ];
    }

    private static function httpGet(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 2.5,
                'ignore_errors' => true,
                'header'        => "Accept: application/json\r\nUser-Agent: BiverRoyaltyHomes/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if (!is_string($body) || $body === '') {
            return null;
        }

        return $body;
    }

    private static function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    public static function countryNameFromCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $map = [
            'NG' => 'Nigeria', 'GH' => 'Ghana', 'KE' => 'Kenya', 'ZA' => 'South Africa',
            'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada',
            'AE' => 'United Arab Emirates', 'CN' => 'China', 'IN' => 'India',
            'DE' => 'Germany', 'FR' => 'France', 'IT' => 'Italy', 'ES' => 'Spain',
            'NL' => 'Netherlands', 'IE' => 'Ireland', 'AU' => 'Australia',
            'CM' => 'Cameroon', 'CI' => 'Côte d’Ivoire', 'SN' => 'Senegal',
            'TG' => 'Togo', 'BJ' => 'Benin', 'NE' => 'Niger', 'TD' => 'Chad',
            'EG' => 'Egypt', 'MA' => 'Morocco', 'TZ' => 'Tanzania', 'UG' => 'Uganda',
            'RW' => 'Rwanda', 'ET' => 'Ethiopia', 'BR' => 'Brazil', 'MX' => 'Mexico',
            'PH' => 'Philippines', 'PK' => 'Pakistan', 'BD' => 'Bangladesh',
            'LO' => 'Local network',
        ];
        if (isset($map[$code])) {
            return $map[$code];
        }
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-' . $code, 'en');
            if (is_string($name) && $name !== '' && strtoupper($name) !== $code) {
                return $name;
            }
        }

        return $code;
    }
}
