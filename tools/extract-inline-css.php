<?php
/**
 * One-off: extract <style> blocks from PHP pages into assets/css/*.css
 * Run: php tools/extract-inline-css.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);

$map = [
    'index.php' => ['css' => 'assets/css/index.css', 'href' => './assets/css/index.css'],
    'about.php' => ['css' => 'assets/css/about.css', 'href' => './assets/css/about.css'],
    'services.php' => ['css' => 'assets/css/services.css', 'href' => './assets/css/services.css'],
    'contact.php' => ['css' => 'assets/css/contact.css', 'href' => './assets/css/contact.css'],
    'property.php' => ['css' => 'assets/css/property.css', 'href' => './assets/css/property.css'],
    'property-detail.php' => ['css' => 'assets/css/property-detail.css', 'href' => './assets/css/property-detail.css'],
    'list-your-property.php' => ['css' => 'assets/css/list-your-property.css', 'href' => './assets/css/list-your-property.css'],
    'admin/admin-dashboard.php' => ['css' => 'assets/css/admin-dashboard.css', 'href' => '../assets/css/admin-dashboard.css'],
    'admin/admin-setting.php' => ['css' => 'assets/css/admin-setting.css', 'href' => '../assets/css/admin-setting.css'],
    'admin/admin-testimonial.php' => ['css' => 'assets/css/admin-testimonial.css', 'href' => '../assets/css/admin-testimonial.css'],
    'admin/admin-analytics.php' => ['css' => 'assets/css/admin-analytics.css', 'href' => '../assets/css/admin-analytics.css'],
    'admin/admin-property.php' => ['css' => 'assets/css/admin-property.css', 'href' => '../assets/css/admin-property.css'],
    'admin/admin-contact.php' => ['css' => 'assets/css/admin-contact.css', 'href' => '../assets/css/admin-contact.css', 'extraStyles' => true],
    'admin/admin-list-your-property.php' => ['css' => 'assets/css/admin-list-your-property.css', 'href' => '../assets/css/admin-list-your-property.css', 'extraStyles' => true],
    'admin/admin-promo-banner.php' => ['css' => 'assets/css/admin-promo-banner.css', 'href' => '../assets/css/admin-promo-banner.css', 'extraStyles' => true],
    'admin/admin-locations.php' => ['css' => 'assets/css/admin-locations.css', 'href' => '../assets/css/admin-locations.css', 'extraStyles' => true],
    'admin/admin-login.php' => ['css' => 'assets/css/admin-login.css', 'href' => '../assets/css/admin-login.css'],
    'admin/admin-live-chat.php' => ['css' => 'assets/css/admin-live-chat.css', 'href' => '../assets/css/admin-live-chat.css', 'extraStyles' => true],
];

function extractStyleBlocks(string $html): array
{
    preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $matches);
    return $matches[1] ?? [];
}

foreach ($map as $phpRel => $info) {
    $phpPath = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $phpRel);
    if (!is_readable($phpPath)) {
        echo "SKIP (missing): {$phpRel}\n";
        continue;
    }

    $content = file_get_contents($phpPath);
    $cssParts = [];

    if (!empty($info['extraStyles'])) {
        if (preg_match("/\\\$extraStyles\s*=\s*<<<'CSS'\\s*\\n(.*?)\\nCSS;/s", $content, $m)) {
            $block = trim($m[1]);
            if (str_starts_with($block, '<style>')) {
                $block = preg_replace('/^<style>\s*/i', '', $block);
                $block = preg_replace('/\s*<\/style>\s*$/i', '', $block);
            }
            $cssParts[] = $block;
        }
    }

    $cssParts = array_merge($cssParts, extractStyleBlocks($content));
    if ($cssParts === []) {
        echo "SKIP (no styles): {$phpRel}\n";
        continue;
    }

    $cssBody = trim(implode("\n\n", array_map('trim', $cssParts)));
    $cssPath = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $info['css']);
    $cssDir = dirname($cssPath);
    if (!is_dir($cssDir)) {
        mkdir($cssDir, 0755, true);
    }

    $header = "/* Extracted from {$phpRel} */\n";
    file_put_contents($cssPath, $header . $cssBody . "\n");

    $link = '  <link rel="stylesheet" href="' . $info['href'] . '">' . "\n";

    if (!empty($info['extraStyles'])) {
        $content = preg_replace(
            "/\\\$extraStyles\s*=\s*<<<'CSS'\\s*\\n.*?\\nCSS;/s",
            "\$pageStylesheet = '" . $info['href'] . "';\n",
            $content,
            1
        );
        if (str_contains($content, '<?= $extraStyles ?>')) {
            $content = str_replace(
                "<?= \$extraStyles ?>\n",
                $link,
                $content
            );
        }
    }

    $content = preg_replace('/<style\b[^>]*>.*?<\/style>\s*/is', '', $content);

    if (!str_contains($content, $info['href'])) {
        if (preg_match('/(<link rel="stylesheet" href="\.\.\/assets\/css\/admin-common\.css">)/', $content, $m)) {
            $content = str_replace($m[1], $m[1] . "\n" . $link, $content);
        } elseif (preg_match('/(require[^;]*admin_assets\.php[^;]*;\s*\?>)/', $content, $m)) {
            $content = str_replace($m[1], $m[1] . "\n" . $link, $content);
        } elseif (preg_match('/(<link rel="stylesheet" href="\.\/assets\/css\/site-variables\.css">)/', $content, $m)) {
            $content = str_replace($m[1], $m[1] . "\n" . $link, $content);
        } elseif (preg_match('/(<\?php require __DIR__ \. \'\/includes\/site_bootstrap\.php\'; \?>)/', $content, $m)) {
            $content = str_replace($m[1], $m[1] . "\n" . $link, $content);
        } else {
            $content = preg_replace('/<\/head>/', $link . '</head>', $content, 1);
        }
    }

    file_put_contents($phpPath, $content);
    echo "OK: {$phpRel} -> {$info['css']}\n";
}

// admin_sidebar.php inline fix -> admin-common.css append
$sidebarPath = $root . '/includes/admin_sidebar.php';
$sidebar = file_get_contents($sidebarPath);
$sidebarCss = extractStyleBlocks($sidebar);
if ($sidebarCss !== []) {
    $commonPath = $root . '/assets/css/admin-common.css';
    $append = "\n/* Sidebar layout fix (from admin_sidebar.php) */\n" . trim($sidebarCss[0]) . "\n";
    if (!str_contains(file_get_contents($commonPath), 'Sidebar layout fix')) {
        file_put_contents($commonPath, file_get_contents($commonPath) . $append);
    }
    $sidebar = preg_replace('/<style\b[^>]*>.*?<\/style>\s*/is', '', $sidebar);
    file_put_contents($sidebarPath, $sidebar);
    echo "OK: includes/admin_sidebar.php -> admin-common.css\n";
}

echo "Done.\n";
