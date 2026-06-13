<?php
/**
 * Optional: redirect legacy .php URLs to extensionless paths (PHP, not .htaccess).
 * Include at top of public pages if you want browser URLs without .php.
 */
declare(strict_types=1);

require_once __DIR__ . '/site_paths.php';

$uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($uri, PHP_URL_PATH) ?? '';

if ($path === '' || !str_ends_with($path, '.php')) {
    return;
}

$skipPrefixes = ['/api/', '/admin/', '/chatbot/', '/sql/'];
foreach ($skipPrefixes as $prefix) {
    if (str_contains($path, $prefix)) {
        return;
    }
}

$base = siteRootPath();
$relative = $path;
if ($base !== '' && str_starts_with($relative, $base)) {
    $relative = substr($relative, strlen($base)) ?: '/';
}

$clean = preg_replace('/\.php$/i', '', $relative) ?? $relative;
$query = parse_url($uri, PHP_URL_QUERY);
$target = ($base !== '' ? $base : '') . $clean . ($query ? '?' . $query : '');

if ($target !== $path . ($query ? '?' . $query : '')) {
    header('Location: ' . $target, true, 301);
    exit;
}
