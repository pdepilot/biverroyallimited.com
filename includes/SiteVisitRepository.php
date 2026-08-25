<?php
/**
 * Site visit / pageview tracking.
 * Auto-created on first track or admin report load.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class SiteVisitRepository
{
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS site_visits (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                visitor_key CHAR(36) NOT NULL,
                ip_hash CHAR(64) NOT NULL DEFAULT \'\',
                page_path VARCHAR(255) NOT NULL,
                page_title VARCHAR(255) NOT NULL DEFAULT \'\',
                referrer VARCHAR(512) NOT NULL DEFAULT \'\',
                user_agent VARCHAR(512) NOT NULL DEFAULT \'\',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_site_visits_created (created_at),
                KEY idx_site_visits_visitor_created (visitor_key, created_at),
                KEY idx_site_visits_page_created (page_path, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$schemaReady = true;
    }

    /**
     * @param array{visitorKey:string,pagePath:string,pageTitle?:string,referrer?:string,userAgent?:string} $input
     */
    public static function record(array $input): bool
    {
        self::ensureSchema();

        $visitorKey = self::normalizeVisitorKey((string) ($input['visitorKey'] ?? ''));
        $pagePath = self::clip(self::normalizePath((string) ($input['pagePath'] ?? '/')), 255);
        if ($visitorKey === '' || $pagePath === '') {
            return false;
        }

        // Soft dedupe: same visitor + path within 30 seconds.
        $pdo = getDatabaseConnection();
        $dup = $pdo->prepare(
            'SELECT id FROM site_visits
             WHERE visitor_key = :vk AND page_path = :path
               AND created_at >= (NOW() - INTERVAL 30 SECOND)
             LIMIT 1'
        );
        $dup->execute(['vk' => $visitorKey, 'path' => $pagePath]);
        if ($dup->fetch()) {
            return true;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO site_visits (visitor_key, ip_hash, page_path, page_title, referrer, user_agent)
             VALUES (:vk, :ip, :path, :title, :ref, :ua)'
        );

        return $stmt->execute([
            'vk'    => $visitorKey,
            'ip'    => self::hashIp(self::clientIp()),
            'path'  => $pagePath,
            'title' => self::clip((string) ($input['pageTitle'] ?? ''), 255),
            'ref'   => self::clip((string) ($input['referrer'] ?? ''), 512),
            'ua'    => self::clip((string) ($input['userAgent'] ?? ''), 512),
        ]);
    }

    /** @return array<string, mixed> */
    public static function getStats(): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();

        $totals = $pdo->query(
            'SELECT
                COUNT(*) AS pageviews,
                COUNT(DISTINCT visitor_key) AS visitors,
                SUM(DATE(created_at) = CURDATE()) AS pageviews_today,
                COUNT(DISTINCT CASE WHEN DATE(created_at) = CURDATE() THEN visitor_key END) AS visitors_today,
                SUM(created_at >= (CURDATE() - INTERVAL 6 DAY)) AS pageviews_7d,
                COUNT(DISTINCT CASE WHEN created_at >= (CURDATE() - INTERVAL 6 DAY) THEN visitor_key END) AS visitors_7d,
                SUM(YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())) AS pageviews_month,
                COUNT(DISTINCT CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN visitor_key END) AS visitors_month
             FROM site_visits'
        )->fetch() ?: [];

        return [
            'pageviews'       => (int) ($totals['pageviews'] ?? 0),
            'visitors'        => (int) ($totals['visitors'] ?? 0),
            'pageviewsToday'  => (int) ($totals['pageviews_today'] ?? 0),
            'visitorsToday'   => (int) ($totals['visitors_today'] ?? 0),
            'pageviews7d'     => (int) ($totals['pageviews_7d'] ?? 0),
            'visitors7d'      => (int) ($totals['visitors_7d'] ?? 0),
            'pageviewsMonth'  => (int) ($totals['pageviews_month'] ?? 0),
            'visitorsMonth'   => (int) ($totals['visitors_month'] ?? 0),
            'daily'           => self::dailyCounts(14),
            'topPages'        => self::topPages(10),
            'recent'          => self::recent(15),
        ];
    }

    /** @return list<array{date:string,label:string,pageviews:int,visitors:int}> */
    public static function dailyCounts(int $days = 14): array
    {
        self::ensureSchema();
        $days = max(1, min(90, $days));
        $pdo = getDatabaseConnection();

        $stmt = $pdo->prepare(
            'SELECT DATE(created_at) AS d,
                    COUNT(*) AS pageviews,
                    COUNT(DISTINCT visitor_key) AS visitors
             FROM site_visits
             WHERE created_at >= (CURDATE() - INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY d ASC'
        );
        $stmt->bindValue('days', $days - 1, PDO::PARAM_INT);
        $stmt->execute();
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(string) $row['d']] = [
                'pageviews' => (int) $row['pageviews'],
                'visitors'  => (int) $row['visitors'],
            ];
        }

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime('-' . $i . ' days') ?: time();
            $date = date('Y-m-d', $ts);
            $out[] = [
                'date'      => $date,
                'label'     => date('M j', $ts),
                'pageviews' => (int) ($rows[$date]['pageviews'] ?? 0),
                'visitors'  => (int) ($rows[$date]['visitors'] ?? 0),
            ];
        }

        return $out;
    }

    /** @return list<array{path:string,pageviews:int,visitors:int}> */
    public static function topPages(int $limit = 10): array
    {
        self::ensureSchema();
        $limit = max(1, min(50, $limit));
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT page_path AS path,
                    COUNT(*) AS pageviews,
                    COUNT(DISTINCT visitor_key) AS visitors
             FROM site_visits
             GROUP BY page_path
             ORDER BY pageviews DESC
             LIMIT ' . $limit
        );

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'path'      => (string) $row['path'],
                'pageviews' => (int) $row['pageviews'],
                'visitors'  => (int) $row['visitors'],
            ];
        }

        return $out;
    }

    /** @return list<array{path:string,title:string,visitorKey:string,createdAt:string}> */
    public static function recent(int $limit = 15): array
    {
        self::ensureSchema();
        $limit = max(1, min(50, $limit));
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT page_path, page_title, visitor_key, created_at
             FROM site_visits
             ORDER BY id DESC
             LIMIT ' . $limit
        );

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'path'       => (string) $row['page_path'],
                'title'      => (string) $row['page_title'],
                'visitorKey' => substr((string) $row['visitor_key'], 0, 8) . '…',
                'createdAt'  => (string) $row['created_at'],
            ];
        }

        return $out;
    }

    private static function normalizeVisitorKey(string $key): string
    {
        $key = trim($key);
        if (preg_match('/^[a-f0-9-]{36}$/i', $key)) {
            return strtolower($key);
        }

        return '';
    }

    private static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        // Strip query/hash and collapse to path only.
        $parts = parse_url($path);
        if (is_array($parts) && isset($parts['path'])) {
            $path = (string) $parts['path'];
        }
        $path = preg_replace('#/+#', '/', $path) ?: '/';
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        return self::clip($path, 255);
    }

    private static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ($candidates as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            if (str_contains($raw, ',')) {
                $raw = trim(explode(',', $raw)[0]);
            }
            if (filter_var($raw, FILTER_VALIDATE_IP)) {
                return $raw;
            }
        }

        return '';
    }

    private static function hashIp(string $ip): string
    {
        if ($ip === '') {
            return '';
        }
        $salt = defined('DB_NAME') ? (string) DB_NAME : 'biver';

        return hash('sha256', $salt . '|' . $ip);
    }

    private static function clip(string $value, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }
}
