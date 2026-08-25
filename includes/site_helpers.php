<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteSettingsService.php';
require_once __DIR__ . '/site_paths.php';

/** @return array<string, mixed> */
function siteSettings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = SiteSettingsService::get();
    }

    return $cache;
}

function siteName(): string
{
    return (string) (siteSettings()['siteName'] ?? 'Biver Royalty Homes');
}

function siteContactEmail(): string
{
    return (string) (siteSettings()['contactEmail'] ?? 'biverroyaltyhomes01@gmail.com');
}

function siteContactPhone(): string
{
    return (string) (siteSettings()['contactPhone'] ?? '+234 903 685 1168');
}

function siteContactPhoneTel(): string
{
    $phone = siteContactPhone();
    $digits = preg_replace('/[^\d+]/', '', $phone) ?? '';

    return $digits !== '' ? $digits : '+2349036851168';
}

function siteAddress(): string
{
    return (string) (siteSettings()['address'] ?? 'No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State');
}

function siteAboutText(): string
{
    return (string) (siteSettings()['aboutText'] ?? '');
}

function siteSocial(string $network): string
{
    $key = match (strtolower($network)) {
        'facebook'  => 'socialFacebook',
        'instagram' => 'socialInstagram',
        'tiktok'    => 'socialTiktok',
        'twitter'   => 'socialTwitter',
        default     => '',
    };

    return $key !== '' ? (string) (siteSettings()[$key] ?? '') : '';
}

function siteMailto(): string
{
    return 'mailto:' . rawurlencode(siteContactEmail());
}

function siteTelHref(): string
{
    return 'tel:' . siteContactPhoneTel();
}
