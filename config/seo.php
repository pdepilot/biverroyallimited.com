<?php
/**
 * SEO, AdSense, and analytics configuration.
 * Override any value in config/seo.local.php (gitignored).
 */

declare(strict_types=1);

$seo = [
    // Public site origin used in canonical, Open Graph, sitemap, and schema.
    // Leave empty to auto-detect from the current request (useful on XAMPP).
    'siteUrl' => 'https://biverroyaltyhomesltd.com',

    'siteName' => 'Biver Royalty Homes',
    'legalName' => 'Biver Royalty Homes Ltd',
    'defaultTitle' => 'Real Estate Agency in Owerri | Biver Royalty Homes Ltd',
    'defaultDescription' => 'Biver Royalty Homes Ltd is a trusted real estate agency in Owerri, Imo State. Buy, rent, or sell verified homes on Wetheral Road and across Imo.',
    'defaultKeywords' => 'real estate agency Owerri, Biver Royalty Homes Ltd, houses for sale Owerri, rent apartment Imo State, property Wetheral Road, real estate Owerri Nigeria',
    'defaultOgImage' => 'assets/images/biver-logo.png',
    'locale' => 'en_NG',

    // Google AdSense publisher ID — required in <head> on every public page.
    'adsensePublisherId' => 'ca-pub-4828740366189357',

    // After AdSense approval, paste unit slot IDs here to render live ads.
    'adsenseSlots' => [
        'top' => '',
        'in_content' => '',
        'sidebar' => '',
        'between' => '',
        'article_end' => '',
    ],

    // Google Analytics 4 measurement ID, e.g. G-XXXXXXXXXX. Leave blank until created.
    'ga4MeasurementId' => 'G-CKHV70D59C',

    // Google Search Console HTML tag verification token (content= value only).
    'googleSiteVerification' => '',

    'twitterHandle' => '',
];

$local = __DIR__ . '/seo.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $seo = array_replace_recursive($seo, $override);
    }
}

return $seo;
