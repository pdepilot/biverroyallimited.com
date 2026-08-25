<?php
/**
 * Central SEO, AdSense, schema, and tracking output for public pages.
 * This is a PHP site (not Laravel) — include SeoService::renderHead() in every <head>.
 */

declare(strict_types=1);

require_once __DIR__ . '/site_paths.php';
require_once __DIR__ . '/site_helpers.php';

final class SeoService
{
    /** @return array<string, mixed> */
    public static function config(): array
    {
        static $config = null;
        if ($config === null) {
            $file = dirname(__DIR__) . '/config/seo.php';
            $loaded = is_file($file) ? require $file : [];
            $config = is_array($loaded) ? $loaded : [];
        }

        return $config;
    }

    public static function publisherId(): string
    {
        return trim((string) (self::config()['adsensePublisherId'] ?? ''));
    }

    public static function ga4Id(): string
    {
        return trim((string) (self::config()['ga4MeasurementId'] ?? ''));
    }

    /** Scheme + host only, e.g. https://biverroyaltyhomesltd.com */
    public static function requestOrigin(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            $cfg = (string) (self::config()['siteUrl'] ?? '');
            if ($cfg !== '') {
                $parts = parse_url($cfg);

                return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'biverroyaltyhomesltd.com');
            }
            $host = 'biverroyaltyhomesltd.com';
            $scheme = 'https';
        }

        return $scheme . '://' . $host;
    }

    /** Public site origin including subdirectory when running under XAMPP. */
    public static function siteOrigin(): string
    {
        $cfg = rtrim((string) (self::config()['siteUrl'] ?? ''), '/');
        if ($cfg !== '' && empty($_SERVER['HTTP_HOST'])) {
            return $cfg;
        }

        // Prefer the live request so local XAMPP and production both get correct canonicals.
        return rtrim(self::requestOrigin() . siteRootPath(), '/');
    }

    public static function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return self::defaultOgImage();
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        if (str_starts_with($url, '/')) {
            return self::requestOrigin() . $url;
        }

        return self::siteOrigin() . '/' . ltrim(preg_replace('#^\./#', '', $url) ?? $url, '/');
    }

    public static function canonicalForPage(string $page, array $query = []): string
    {
        return self::absoluteUrl(pageUrl($page, $query));
    }

    public static function defaultOgImage(): string
    {
        $img = (string) (self::config()['defaultOgImage'] ?? 'assets/images/biver-logo.png');

        return self::absoluteUrl($img);
    }

    /**
     * Render complete public <head> internals: meta, OG, Twitter, canonical,
     * AdSense verification script, Consent Mode, optional GA4, JSON-LD, CSS.
     *
     * @param array{
     *   title?: string,
     *   description?: string,
     *   keywords?: string,
     *   canonical?: string,
     *   page?: string,
     *   query?: array<string, scalar|null>,
     *   ogType?: string,
     *   ogImage?: string|null,
     *   robots?: string,
     *   stylesheets?: list<string>,
     *   breadcrumbs?: list<array{name:string,url?:string}>,
     *   jsonLd?: list<array<string, mixed>>,
     *   article?: ?array<string, mixed>,
     *   property?: ?array<string, mixed>
     * } $meta
     */
    public static function renderHead(array $meta = []): void
    {
        $cfg = self::config();
        $title = self::limit((string) ($meta['title'] ?? $cfg['defaultTitle'] ?? siteName()), 70);
        $description = self::limit((string) ($meta['description'] ?? $cfg['defaultDescription'] ?? ''), 170);
        $keywords = (string) ($meta['keywords'] ?? $cfg['defaultKeywords'] ?? '');
        $ogType = (string) ($meta['ogType'] ?? 'website');
        $robots = (string) ($meta['robots'] ?? 'index, follow');
        $canonical = (string) ($meta['canonical'] ?? '');
        if ($canonical === '' && !empty($meta['page'])) {
            $canonical = self::canonicalForPage((string) $meta['page'], is_array($meta['query'] ?? null) ? $meta['query'] : []);
        }
        if ($canonical === '') {
            $canonical = self::absoluteUrl($_SERVER['REQUEST_URI'] ?? '/');
        }
        $ogImage = !empty($meta['ogImage']) ? self::absoluteUrl((string) $meta['ogImage']) : self::defaultOgImage();
        $siteName = (string) ($cfg['siteName'] ?? siteName());
        $locale = (string) ($cfg['locale'] ?? 'en_NG');
        $publisherId = self::publisherId();
        $ga4 = self::ga4Id();
        $gsc = trim((string) ($cfg['googleSiteVerification'] ?? ''));
        $twitter = trim((string) ($cfg['twitterHandle'] ?? ''));

        $stylesheets = $meta['stylesheets'] ?? [];
        if (!is_array($stylesheets)) {
            $stylesheets = [];
        }

        echo "  <meta charset=\"UTF-8\">\n";
        echo "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
        echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        echo '  <title>' . siteEscape($title) . "</title>\n";
        echo '  <meta name="description" content="' . siteEscape($description) . "\">\n";
        if ($keywords !== '') {
            echo '  <meta name="keywords" content="' . siteEscape($keywords) . "\">\n";
        }
        echo '  <meta name="author" content="' . siteEscape((string) ($cfg['legalName'] ?? $siteName)) . "\">\n";
        echo '  <meta name="robots" content="' . siteEscape($robots) . "\">\n";
        echo "  <meta name=\"theme-color\" content=\"#371801\">\n";
        echo "  <meta name=\"geo.region\" content=\"NG-IM\">\n";
        echo "  <meta name=\"geo.placename\" content=\"Owerri, Imo State\">\n";
        echo "  <meta name=\"geo.position\" content=\"5.4891;7.0256\">\n";
        echo "  <meta name=\"ICBM\" content=\"5.4891, 7.0256\">\n";
        echo '  <link rel="canonical" href="' . siteEscape($canonical) . "\">\n";
        echo '  <link rel="alternate" hreflang="en-NG" href="' . siteEscape($canonical) . "\">\n";
        echo '  <link rel="alternate" hreflang="x-default" href="' . siteEscape($canonical) . "\">\n";

        if ($gsc !== '') {
            echo '  <meta name="google-site-verification" content="' . siteEscape($gsc) . "\">\n";
        }
        if ($publisherId !== '') {
            echo '  <meta name="google-adsense-account" content="' . siteEscape($publisherId) . "\">\n";
        }

        echo '  <meta property="og:site_name" content="' . siteEscape($siteName) . "\">\n";
        echo '  <meta property="og:locale" content="' . siteEscape($locale) . "\">\n";
        echo '  <meta property="og:type" content="' . siteEscape($ogType) . "\">\n";
        echo '  <meta property="og:title" content="' . siteEscape($title) . "\">\n";
        echo '  <meta property="og:description" content="' . siteEscape($description) . "\">\n";
        echo '  <meta property="og:url" content="' . siteEscape($canonical) . "\">\n";
        echo '  <meta property="og:image" content="' . siteEscape($ogImage) . "\">\n";
        echo "  <meta property=\"og:image:alt\" content=\"" . siteEscape($siteName) . "\">\n";

        echo "  <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        echo '  <meta name="twitter:title" content="' . siteEscape($title) . "\">\n";
        echo '  <meta name="twitter:description" content="' . siteEscape($description) . "\">\n";
        echo '  <meta name="twitter:image" content="' . siteEscape($ogImage) . "\">\n";
        if ($twitter !== '') {
            echo '  <meta name="twitter:site" content="' . siteEscape($twitter) . "\">\n";
        }

        echo "  <link rel=\"shortcut icon\" href=\"./assets/images/biver-logo.png\" type=\"image/png\">\n";
        echo "  <link rel=\"apple-touch-icon\" href=\"./assets/images/biver-logo.png\">\n";
        echo "  <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
        echo "  <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
        echo "  <link href=\"https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap\" rel=\"stylesheet\">\n";
        echo "  <link rel=\"stylesheet\" href=\"./assets/css/site-variables.css\">\n";
        echo "  <link rel=\"stylesheet\" href=\"./assets/css/site-utilities.css\">\n";
        echo "  <link rel=\"stylesheet\" href=\"./assets/css/seo.css\">\n";
        foreach ($stylesheets as $href) {
            $href = trim((string) $href);
            if ($href === '') {
                continue;
            }
            echo '  <link rel="stylesheet" href="' . siteEscape($href) . "\">\n";
        }
        echo "  <link rel=\"stylesheet\" href=\"./assets/css/site-header.css\">\n";

        require __DIR__ . '/site_bootstrap.php';

        self::renderConsentAndTracking($publisherId, $ga4);

        $graphs = [];
        $graphs[] = self::organizationSchema();
        $graphs[] = self::websiteSchema();
        $graphs[] = self::localBusinessSchema();
        if (!empty($meta['breadcrumbs']) && is_array($meta['breadcrumbs'])) {
            $graphs[] = self::breadcrumbSchema($meta['breadcrumbs']);
        }
        if (!empty($meta['article']) && is_array($meta['article'])) {
            $graphs[] = self::articleSchema($meta['article'], $canonical);
        }
        if (!empty($meta['property']) && is_array($meta['property'])) {
            $graphs[] = self::realEstateListingSchema($meta['property'], $canonical);
        }
        if (!empty($meta['jsonLd']) && is_array($meta['jsonLd'])) {
            foreach ($meta['jsonLd'] as $extra) {
                if (is_array($extra)) {
                    $graphs[] = $extra;
                }
            }
        }

        echo "  <script type=\"application/ld+json\">\n";
        echo json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graphs,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n  </script>\n";
    }

    public static function renderConsentAndTracking(string $publisherId, string $ga4): void
    {
        $gaJson = json_encode($ga4, JSON_UNESCAPED_SLASHES);
        echo <<<HTML
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }
    gtag('consent', 'default', {
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'denied',
      functionality_storage: 'granted',
      personalization_storage: 'denied',
      security_storage: 'granted',
      wait_for_update: 500
    });
    gtag('set', 'ads_data_redaction', true);
    gtag('set', 'url_passthrough', true);
    window.BIVER_SEO = window.BIVER_SEO || { ga4Id: {$gaJson} };
  </script>

HTML;
        if ($publisherId !== '') {
            $src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode($publisherId);
            echo '  <!-- Google AdSense: required in <head> on every public page for verification -->' . "\n";
            echo '  <script async src="' . siteEscape($src) . "\" crossorigin=\"anonymous\"></script>\n";
        }
        if ($ga4 !== '') {
            echo '  <script async src="https://www.googletagmanager.com/gtag/js?id=' . siteEscape($ga4) . "\"></script>\n";
            echo "  <script>\n";
            echo "    gtag('js', new Date());\n";
            echo "    gtag('config', " . json_encode($ga4, JSON_UNESCAPED_SLASHES) . ", { anonymize_ip: true });\n";
            echo "  </script>\n";
        }
    }

    /**
     * @param list<array{name:string,url?:string}> $items
     */
    public static function breadcrumbs(array $items): void
    {
        if ($items === []) {
            return;
        }
        echo '<nav class="seo-breadcrumbs" aria-label="Breadcrumb"><ol>';
        $last = count($items) - 1;
        foreach ($items as $i => $item) {
            $name = siteEscape((string) ($item['name'] ?? ''));
            $url = isset($item['url']) ? trim((string) $item['url']) : '';
            echo '<li>';
            if ($url !== '' && $i !== $last) {
                echo '<a href="' . siteEscape($url) . '">' . $name . '</a>';
            } else {
                echo '<span aria-current="page">' . $name . '</span>';
            }
            echo '</li>';
        }
        echo '</ol></nav>';
    }

    public static function shareButtons(string $url, string $title): void
    {
        $abs = self::absoluteUrl($url);
        $encUrl = rawurlencode($abs);
        $encTitle = rawurlencode($title);
        echo '<div class="seo-share" aria-label="Share this page">';
        echo '<p class="seo-share-label">Share</p>';
        echo '<div class="seo-share-actions">';
        echo '<a class="seo-share-btn" href="https://www.facebook.com/sharer/sharer.php?u=' . $encUrl . '" target="_blank" rel="noopener noreferrer">Facebook</a>';
        echo '<a class="seo-share-btn" href="https://twitter.com/intent/tweet?url=' . $encUrl . '&text=' . $encTitle . '" target="_blank" rel="noopener noreferrer">X</a>';
        echo '<a class="seo-share-btn" href="https://wa.me/?text=' . rawurlencode($title . ' ' . $abs) . '" target="_blank" rel="noopener noreferrer">WhatsApp</a>';
        echo '<a class="seo-share-btn" href="mailto:?subject=' . $encTitle . '&body=' . $encUrl . '">Email</a>';
        echo '</div></div>';
    }

    public static function adSlot(string $placement): void
    {
        $publisherId = self::publisherId();
        $slots = self::config()['adsenseSlots'] ?? [];
        $slotId = is_array($slots) ? trim((string) ($slots[$placement] ?? '')) : '';
        $safePlace = preg_replace('/[^a-z0-9_-]+/i', '-', $placement) ?: 'slot';

        if ($publisherId === '' || $slotId === '') {
            echo '<!-- AdSense unit "' . siteEscape($safePlace) . '" ready. Add slot ID in config/seo.php after approval. -->' . "\n";
            echo '<div class="adsense-slot adsense-slot--' . siteEscape($safePlace) . '" id="adsense-' . siteEscape($safePlace) . '" hidden aria-hidden="true"></div>' . "\n";

            return;
        }

        echo '<aside class="adsense-slot adsense-slot--' . siteEscape($safePlace) . '" id="adsense-' . siteEscape($safePlace) . '" aria-label="Advertisement">';
        echo '<ins class="adsbygoogle" style="display:block" data-ad-client="' . siteEscape($publisherId) . '" data-ad-slot="' . siteEscape($slotId) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>';
        echo '<script>(window.adsbygoogle = window.adsbygoogle || []).push({});</script>';
        echo '</aside>' . "\n";
    }

    /** @return array<string, mixed> */
    public static function organizationSchema(): array
    {
        $cfg = self::config();
        $origin = self::siteOrigin();

        return [
            '@type' => 'Organization',
            '@id' => $origin . '#organization',
            'name' => $cfg['legalName'] ?? siteName(),
            'alternateName' => ['Biver Royalty Homes', 'Biver Royalty Homes Ltd', 'Biver Royalty'],
            'legalName' => $cfg['legalName'] ?? 'Biver Royalty Homes Ltd',
            'url' => $origin . '/',
            'logo' => self::defaultOgImage(),
            'image' => self::defaultOgImage(),
            'email' => siteContactEmail(),
            'telephone' => siteContactPhoneTel(),
            'foundingDate' => '2015',
            'slogan' => 'Owerri real estate built on integrity',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'No. 31 Wetheral Road, Angelina Plaza Opposite Reem Fuel Station',
                'addressLocality' => 'Owerri',
                'addressRegion' => 'Imo State',
                'postalCode' => '460242',
                'addressCountry' => 'NG',
            ],
            'sameAs' => array_values(array_filter([
                siteSocial('facebook'),
                siteSocial('instagram'),
                siteSocial('tiktok'),
                siteSocial('twitter'),
            ])),
        ];
    }

    /** @return array<string, mixed> */
    public static function websiteSchema(): array
    {
        $origin = self::siteOrigin();

        return [
            '@type' => 'WebSite',
            '@id' => $origin . '#website',
            'url' => $origin . '/',
            'name' => self::config()['siteName'] ?? siteName(),
            'description' => 'Trusted real estate agency in Owerri, Imo State. Buy, rent, or sell verified homes with Biver Royalty Homes Ltd.',
            'inLanguage' => 'en-NG',
            'publisher' => ['@id' => $origin . '#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $origin . '/property?search={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function localBusinessSchema(): array
    {
        $origin = self::siteOrigin();

        $maps = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Biver Royalty Homes Ltd, 31 Wetheral Road, Angelina Plaza, Owerri, Imo State');

        return [
            '@type' => ['RealEstateAgent', 'LocalBusiness'],
            '@id' => $origin . '#localbusiness',
            'name' => self::config()['legalName'] ?? siteName(),
            'alternateName' => 'Biver Royalty Homes',
            'image' => self::defaultOgImage(),
            'logo' => self::defaultOgImage(),
            'url' => $origin . '/',
            'telephone' => siteContactPhoneTel(),
            'email' => siteContactEmail(),
            'hasMap' => $maps,
            'currenciesAccepted' => 'NGN',
            'paymentAccepted' => 'Cash, Bank Transfer',
            'availableLanguage' => ['English', 'Igbo'],
            'knowsAbout' => [
                'Real estate in Owerri',
                'Property sales Imo State',
                'House rentals Owerri',
                'Estate management',
                'Land and residential listings',
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'No. 31 Wetheral Road, Angelina Plaza Opposite Reem Fuel Station',
                'addressLocality' => 'Owerri',
                'addressRegion' => 'Imo State',
                'postalCode' => '460242',
                'addressCountry' => 'NG',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '5.4891',
                'longitude' => '7.0256',
            ],
            'openingHoursSpecification' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'opens' => '09:00',
                'closes' => '18:00',
            ],
            'priceRange' => '$$',
            'areaServed' => self::areaServedPlaces(),
        ];
    }

    /** @return list<array<string, string>> */
    private static function areaServedPlaces(): array
    {
        $places = [
            ['@type' => 'City', 'name' => 'Owerri'],
            ['@type' => 'AdministrativeArea', 'name' => 'Imo State'],
            ['@type' => 'Country', 'name' => 'Nigeria'],
        ];
        $seen = ['owerri' => true, 'imo state' => true, 'nigeria' => true];

        try {
            require_once __DIR__ . '/ServiceAreaRepository.php';
            foreach (ServiceAreaRepository::getAll(true) as $area) {
                $name = trim((string) ($area['title'] ?? ''));
                $key = strtolower($name);
                if ($name === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $places[] = ['@type' => 'Place', 'name' => $name];
            }
        } catch (Throwable $e) {
            // Service areas table may not exist yet on a fresh install.
        }

        return $places;
    }

    /**
     * @param list<array<string, mixed>> $faqs
     * @return array<string, mixed>|null
     */
    public static function faqPageSchema(array $faqs): ?array
    {
        $entities = [];
        foreach (array_slice($faqs, 0, 30) as $faq) {
            $q = trim(strip_tags((string) ($faq['question'] ?? '')));
            $a = trim(strip_tags((string) ($faq['answer'] ?? '')));
            if ($q === '' || $a === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }
        if ($entities === []) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @param list<array{name:string,url?:string}> $items
     * @return array<string, mixed>
     */
    public static function breadcrumbSchema(array $items): array
    {
        $elements = [];
        $position = 1;
        foreach ($items as $item) {
            $name = (string) ($item['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $entry = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
            ];
            if (!empty($item['url'])) {
                $entry['item'] = self::absoluteUrl((string) $item['url']);
            }
            $elements[] = $entry;
            $position++;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public static function articleSchema(array $post, string $canonical): array
    {
        $image = trim((string) ($post['coverImage'] ?? $post['image'] ?? ''));

        return [
            '@type' => 'BlogPosting',
            'headline' => (string) ($post['title'] ?? ''),
            'description' => (string) ($post['excerpt'] ?? $post['description'] ?? ''),
            'image' => $image !== '' ? self::absoluteUrl($image) : self::defaultOgImage(),
            'datePublished' => (string) ($post['publishedAt'] ?? $post['createdAt'] ?? ''),
            'dateModified' => (string) ($post['updatedAt'] ?? $post['publishedAt'] ?? ''),
            'author' => [
                '@type' => 'Person',
                'name' => (string) ($post['authorName'] ?? siteName()),
            ],
            'publisher' => ['@id' => self::siteOrigin() . '#organization'],
            'mainEntityOfPage' => $canonical,
            'inLanguage' => 'en-NG',
        ];
    }

    /**
     * @param array<string, mixed> $property
     * @return array<string, mixed>
     */
    public static function realEstateListingSchema(array $property, string $canonical): array
    {
        $images = $property['images'] ?? [];
        $imageList = [];
        if (is_array($images)) {
            foreach ($images as $img) {
                if (is_string($img) && $img !== '') {
                    $imageList[] = self::absoluteUrl($img);
                }
            }
        }
        if ($imageList === [] && !empty($property['imageUrl'])) {
            $imageList[] = self::absoluteUrl((string) $property['imageUrl']);
        }

        return [
            '@type' => 'RealEstateListing',
            'name' => (string) ($property['title'] ?? 'Property'),
            'description' => self::limit(strip_tags((string) ($property['description'] ?? '')), 300),
            'url' => $canonical,
            'image' => $imageList !== [] ? $imageList : [self::defaultOgImage()],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => (string) ($property['propertyAddress'] ?? $property['location'] ?? ''),
                'addressLocality' => (string) ($property['location'] ?? 'Owerri'),
                'addressRegion' => 'Imo State',
                'addressCountry' => 'NG',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => (int) ($property['price'] ?? 0),
                'priceCurrency' => 'NGN',
                'availability' => 'https://schema.org/InStock',
            ],
        ];
    }

    public static function limit(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if (function_exists('mb_strlen') && mb_strlen($text) > $max) {
            return rtrim(mb_substr($text, 0, $max - 1)) . '…';
        }
        if (strlen($text) > $max) {
            return rtrim(substr($text, 0, $max - 1)) . '…';
        }

        return $text;
    }
}
