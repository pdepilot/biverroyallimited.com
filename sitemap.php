<?php
/**
 * Dynamic XML sitemap for Google Search Console / AdSense indexing.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/site_paths.php';
require_once __DIR__ . '/includes/SeoService.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$origin = rtrim((string) (SeoService::config()['siteUrl'] ?? ''), '/');
if ($origin === '') {
    $origin = SeoService::siteOrigin();
}

$now = date('c');

$static = [
    ['loc' => $origin . '/', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $now],
    ['loc' => $origin . '/about', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => $origin . '/services', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => $origin . '/property', 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => $now],
    ['loc' => $origin . '/locations', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $now],
    ['loc' => $origin . '/blog', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $now],
    ['loc' => $origin . '/faqs', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => $origin . '/contact', 'priority' => '0.7', 'changefreq' => 'yearly', 'lastmod' => $now],
    ['loc' => $origin . '/list-your-property', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => $origin . '/terms', 'priority' => '0.4', 'changefreq' => 'yearly', 'lastmod' => $now],
    ['loc' => $origin . '/privacy', 'priority' => '0.5', 'changefreq' => 'yearly', 'lastmod' => $now],
    ['loc' => $origin . '/cookie-policy', 'priority' => '0.4', 'changefreq' => 'yearly', 'lastmod' => $now],
];

$urls = $static;

try {
    require_once __DIR__ . '/includes/BlogRepository.php';
    BlogRepository::ensureSchema();
    foreach (BlogRepository::getPublic() as $post) {
        $slug = trim((string) ($post['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $last = (string) ($post['updatedAt'] ?? $post['publishedAt'] ?? $post['createdAt'] ?? $now);
        $ts = strtotime($last);
        $urls[] = [
            'loc' => $origin . '/blog-post?slug=' . rawurlencode($slug),
            'priority' => '0.7',
            'changefreq' => 'monthly',
            'lastmod' => $ts ? date('c', $ts) : $now,
        ];
    }
} catch (Throwable $e) {
    error_log('Sitemap blog failed: ' . $e->getMessage());
}

try {
    require_once __DIR__ . '/includes/PropertyRepository.php';
    foreach (PropertyRepository::getPublic(500) as $property) {
        $id = (int) ($property['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $last = (string) ($property['updatedAt'] ?? $property['createdAt'] ?? $now);
        $ts = strtotime($last);
        $urls[] = [
            'loc' => $origin . '/property-detail?id=' . $id,
            'priority' => '0.8',
            'changefreq' => 'weekly',
            'lastmod' => $ts ? date('c', $ts) : $now,
        ];
    }
} catch (Throwable $e) {
    error_log('Sitemap properties failed: ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $row) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars((string) $row['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo '    <lastmod>' . htmlspecialchars((string) $row['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    echo '    <changefreq>' . htmlspecialchars((string) $row['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars((string) $row['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
