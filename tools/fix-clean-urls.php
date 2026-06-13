<?php
/**
 * One-time: convert href="page.php" to extensionless pageHref() in public PHP pages.
 * Run: php tools/fix-clean-urls.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    'about.php',
    'services.php',
    'contact.php',
    'property.php',
    'property-detail.php',
    'list-your-property.php',
];
$pages = [
    'index', 'about', 'services', 'property', 'contact',
    'list-your-property', 'property-detail',
    'addCart', 'favorites', 'login', 'userDashboard',
];
$requireLine = "<?php require_once __DIR__ . '/includes/site_paths.php'; ?>\n";

foreach ($files as $file) {
    $path = $root . DIRECTORY_SEPARATOR . $file;
    if (!is_readable($path)) {
        echo "Skip missing: {$file}\n";
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    foreach ($pages as $page) {
        $content = str_replace(
            'href="' . $page . '.php"',
            'href="<?= pageHref(\'' . $page . '\') ?>"',
            $content
        );
    }

    if (!str_contains($content, 'site_paths.php') && !str_contains($content, 'site_bootstrap.php')) {
        $needle = '<link rel="stylesheet" href="./assets/css/site-variables.css">';
        if (str_contains($content, $needle)) {
            $content = str_replace(
                $needle,
                $needle . "\n  " . trim($requireLine),
                $content
            );
        }
    }

    file_put_contents($path, $content);
    echo "Updated: {$file}\n";
}

echo "Done.\n";
