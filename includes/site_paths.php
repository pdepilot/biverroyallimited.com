<?php
/**
 * Resolve public URLs for assets stored under the project root.
 */

declare(strict_types=1);

function siteRootPath(): string
{
    static $root = null;
    if ($root !== null) {
        return $root;
    }

    $projectRoot = realpath(dirname(__DIR__));
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

    if ($projectRoot && $documentRoot && str_starts_with($projectRoot, $documentRoot)) {
        $root = str_replace('\\', '/', substr($projectRoot, strlen($documentRoot)));
        if ($root !== '' && !str_starts_with($root, '/')) {
            $root = '/' . $root;
        }
        return $root === '' ? '' : $root;
    }

    return $root = '';
}

function publicAssetUrl(?string $relativePath): ?string
{
    if ($relativePath === null || $relativePath === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $relativePath)) {
        return $relativePath;
    }

    $base = siteRootPath();
    if ($base !== '' && str_starts_with(str_replace('\\', '/', $relativePath), $base . '/')) {
        return str_replace('\\', '/', $relativePath);
    }

    $normalized = str_replace('\\', '/', ltrim($relativePath, '/'));

    return ($base !== '' ? $base : '') . '/' . $normalized;
}

/**
 * @param list<string|null> $paths
 * @return list<string>
 */
function publicAssetUrls(array $paths): array
{
    $urls = [];
    foreach ($paths as $path) {
        $url = publicAssetUrl($path);
        if ($url !== null) {
            $urls[] = $url;
        }
    }

    return $urls;
}

function siteUrl(string $path = ''): string
{
    $base = siteRootPath();
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($path === '') {
        return $base === '' ? '/' : $base;
    }

    if ($base === '') {
        return '/' . $path;
    }

    return rtrim($base, '/') . '/' . $path;
}

/**
 * @param array<string, scalar|null> $query
 */
function pageUrl(string $page, array $query = []): string
{
    $page = trim(str_replace('\\', '/', $page), '/');
    $page = preg_replace('/\.php$/i', '', $page) ?? $page;
    $url = siteUrl($page);

    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function propertyDetailUrl(int|string $id): string
{
    return pageUrl('property-detail', ['id' => $id]);
}

function siteEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Clean extensionless URL for use in href attributes. */
function pageHref(string $page, array $query = []): string
{
    return siteEscape(pageUrl($page, $query));
}
