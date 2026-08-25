<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/site_helpers.php';
require_once __DIR__ . '/includes/SeoService.php';

$areas = [];
try {
    require_once __DIR__ . '/includes/ServiceAreaRepository.php';
    $areas = ServiceAreaRepository::getAll(true);
} catch (Throwable $e) {
    error_log('Locations page areas failed: ' . $e->getMessage());
}

if ($areas === []) {
    $areas = [
        ['title' => 'Aladinma', 'tag' => 'Residential', 'description' => 'Established family neighbourhood close to schools, churches, and everyday amenities in Owerri Municipal.', 'linkUrl' => ''],
        ['title' => 'Ikenegbu', 'tag' => 'Premium', 'description' => 'Central Owerri layout known for bungalows, duplexes, and strong rental demand near the city centre.', 'linkUrl' => ''],
        ['title' => 'New Owerri', 'tag' => 'New Layout', 'description' => 'Planned estates and newer builds around the capital territory, popular with professionals and investors.', 'linkUrl' => ''],
        ['title' => 'World Bank', 'tag' => 'Family', 'description' => 'Quiet residential streets with a mix of houses for sale and rent, convenient to Wetheral Road.', 'linkUrl' => ''],
        ['title' => 'Works Layout', 'tag' => 'City', 'description' => 'Well-connected area for buyers who want proximity to offices, markets, and the heart of Owerri.', 'linkUrl' => ''],
        ['title' => 'Orji', 'tag' => 'Growth', 'description' => 'Expanding corridor with land and housing options for first-time buyers and developers.', 'linkUrl' => ''],
    ];
}

$locationFaqs = [
    [
        'question' => 'Who is the best real estate agency in Owerri?',
        'answer' => 'Biver Royalty Homes Ltd is a local Owerri agency on Wetheral Road, Angelina Plaza. We help buyers, renters, and sellers with verified listings, transparent pricing, and on-ground inspection across Imo State.',
    ],
    [
        'question' => 'Where is Biver Royalty Homes Ltd located in Owerri?',
        'answer' => 'Our office is at No. 31 Wetheral Road, Angelina Plaza, opposite Reem Fuel Station, Owerri, Imo State. Visit Monday to Saturday, 9:00am to 6:00pm, or call +234 903 685 1168.',
    ],
    [
        'question' => 'Which Owerri neighbourhoods do you cover?',
        'answer' => 'We regularly list and inspect homes in Aladinma, Ikenegbu, New Owerri, World Bank, Works Layout, Prefab, Orji, Egbu, Nekede, and along Wetheral and Okigwe Roads, plus other parts of Imo State on request.',
    ],
    [
        'question' => 'Can I buy or rent a house in Owerri through Biver Royalty Homes?',
        'answer' => 'Yes. Browse current sale and rental listings on our properties page, or contact the office for a custom search. We also list homes for owners who want professional marketing in Owerri.',
    ],
];

$origin = SeoService::siteOrigin();
$faqSchema = SeoService::faqPageSchema($locationFaqs);
$jsonLd = array_values(array_filter([
    $faqSchema,
    [
        '@type' => 'Service',
        'name' => 'Real estate services in Owerri',
        'serviceType' => ['Property sales', 'House rentals', 'Estate management', 'Property listing'],
        'provider' => ['@id' => $origin . '#localbusiness'],
        'areaServed' => [
            ['@type' => 'City', 'name' => 'Owerri'],
            ['@type' => 'AdministrativeArea', 'name' => 'Imo State'],
        ],
        'url' => pageUrl('locations'),
    ],
]));

function locationsAreaHref(array $area): string
{
    $href = trim((string) ($area['linkUrl'] ?? ''));
    $title = trim((string) ($area['title'] ?? ''));
    if ($href === '' || preg_match('/^property(\.php)?$/i', $href)) {
        return pageHref('property', $title !== '' ? ['search' => $title] : []);
    }
    if (preg_match('#^https?://#i', $href)) {
        return $href;
    }

    return pageHref(preg_replace('/\.php$/i', '', $href) ?: 'property');
}
?>
<!DOCTYPE html>
<html lang="en-NG">
<head>
<?php
SeoService::renderHead([
    'title' => 'Real Estate Agency in Owerri | Biver Royalty Homes Ltd',
    'description' => 'Biver Royalty Homes Ltd is a trusted real estate agency in Owerri, Imo State. Buy, rent, or sell verified homes from our office on Wetheral Road, Angelina Plaza.',
    'keywords' => 'real estate agency Owerri, Biver Royalty Homes Ltd, houses for sale Owerri, rent house Imo State, property Wetheral Road, estate agent Owerri Nigeria',
    'page' => 'locations',
    'stylesheets' => ['./assets/css/terms.css', './assets/css/locations.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'Owerri Locations'],
    ],
    'jsonLd' => $jsonLd,
]);
?>
</head>
<body class="page-terms page-locations">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <section class="terms-hero">
    <div class="terms-hero-bg" aria-hidden="true"></div>
    <div class="container terms-hero-inner">
      <p class="terms-brand">Biver Royalty Homes Ltd</p>
      <p class="terms-eyebrow">Local expertise · Imo State</p>
      <h1>Real Estate Agency in Owerri</h1>
      <p class="terms-lead">Verified homes for sale and rent, estate management, and honest advice from our office on Wetheral Road.</p>
    </div>
  </section>

  <div class="container"><?php SeoService::breadcrumbs([
      ['name' => 'Home', 'url' => pageUrl('index')],
      ['name' => 'Owerri Locations'],
  ]); ?></div>

  <section class="terms-body loc-body">
    <div class="container loc-layout">
      <article class="loc-copy">
        <h2>Owerri’s local estate agency on Wetheral Road</h2>
        <p>
          <strong>Biver Royalty Homes Ltd</strong> is a real estate agency based in Owerri, Imo State.
          Families, professionals, and investors come to us to buy a house, rent an apartment, or sell land
          without guesswork. We work from <strong>No. 31 Wetheral Road, Angelina Plaza (opposite Reem Fuel Station)</strong>,
          so inspections, paperwork, and after-sale support stay close to the neighbourhoods we serve.
        </p>
        <p>
          Owerri’s property market moves quickly around Aladinma, Ikenegbu, New Owerri, World Bank, Works Layout,
          Prefab, Orji, Egbu, and Nekede. Prices and demand differ street by street. A local agent who walks
          those layouts can tell you which titles are clean, which rents are realistic, and which homes are worth
          a second visit. That is the work we do every week.
        </p>

        <h2>Buy, rent, or sell property in Imo State</h2>
        <p>
          If you are searching for <a href="<?= pageHref('property') ?>">houses for sale in Owerri</a> or a
          rental that fits your budget, start with our live listings, then book a viewing. Owners who want to
          <a href="<?= pageHref('list-your-property') ?>">list a property</a> get professional photos, honest
          pricing, and marketing to serious buyers and tenants. We also handle estate management for landlords
          who need a trusted team on the ground.
        </p>
        <p>
          Whether you are relocating to the Imo State capital, investing from outside Nigeria, or moving within
          the city, we keep communication clear: documented inspections, transparent fees, and no surprise
          “agency only” listings. Learn more about our story on the
          <a href="<?= pageHref('about') ?>">About</a> page or see the full
          <a href="<?= pageHref('services') ?>">services</a> we offer.
        </p>

        <h2>Neighbourhoods we know well</h2>
        <p>
          Wetheral Road remains one of Owerri’s main commercial spines — useful if you want to be near shops,
          offices, and the city centre. Aladinma and Ikenegbu stay popular with families who want established
          streets and schools nearby. New Owerri and surrounding planned layouts attract buyers looking for newer
          builds. World Bank and Works Layout offer a balance of access and quieter residential living. Growth
          corridors such as Orji and Egbu often suit first-time buyers and developers seeking land.
        </p>
        <p>
          Still unsure where to live? Tell us your budget, commute, and household size. We will shortlist verified
          homes in the right part of Owerri instead of sending you on a city-wide tour.
        </p>

        <div class="loc-cta">
          <a class="loc-btn" href="<?= pageHref('property') ?>">Browse Owerri listings</a>
          <a class="loc-btn loc-btn-alt" href="<?= pageHref('contact') ?>">Visit or call the office</a>
        </div>
      </article>

      <aside class="loc-nap" aria-label="Office details">
        <p class="loc-nap-label">Visit our office</p>
        <h2>Biver Royalty Homes Ltd</h2>
        <address>
          No. 31 Wetheral Road<br>
          Angelina Plaza, opposite Reem Fuel Station<br>
          Owerri, Imo State, Nigeria
        </address>
        <p><a href="tel:+2349036851168">+234 903 685 1168</a></p>
        <p><a href="mailto:biverroyaltyhomes01@gmail.com">biverroyaltyhomes01@gmail.com</a></p>
        <p class="loc-hours">Mon–Sat · 9:00am – 6:00pm</p>
        <a class="loc-map-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode('Biver Royalty Homes Ltd, 31 Wetheral Road, Angelina Plaza, Owerri') ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
      </aside>
    </div>

    <div class="container loc-map-wrap">
      <h2>Find us on the map</h2>
      <iframe
        title="Map of Biver Royalty Homes Ltd on Wetheral Road, Owerri"
        src="https://www.google.com/maps?q=31+Wetheral+Road+Angelina+Plaza+Owerri+Imo&amp;output=embed"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen
      ></iframe>
    </div>

    <div class="container">
      <h2 class="loc-areas-title">Areas we serve</h2>
      <p class="loc-areas-lead">Explore neighbourhoods across Owerri and request a search for anywhere else in Imo State.</p>
      <ul class="loc-areas">
        <?php foreach ($areas as $area): ?>
          <?php
            $title = (string) ($area['title'] ?? '');
            $tag = (string) ($area['tag'] ?? '');
            $desc = (string) ($area['description'] ?? '');
            $href = locationsAreaHref($area);
          ?>
          <li>
            <a href="<?= siteEscape($href) ?>">
              <?php if ($tag !== ''): ?><span><?= siteEscape($tag) ?></span><?php endif; ?>
              <strong><?= siteEscape($title) ?></strong>
              <?php if ($desc !== ''): ?><p><?= siteEscape($desc) ?></p><?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="container loc-faq">
      <h2>Owerri real estate FAQs</h2>
      <?php foreach ($locationFaqs as $faq): ?>
        <details>
          <summary><?= siteEscape((string) $faq['question']) ?></summary>
          <p><?= siteEscape((string) $faq['answer']) ?></p>
        </details>
      <?php endforeach; ?>
      <p class="loc-faq-more">More answers are on our <a href="<?= pageHref('faqs') ?>">full FAQ page</a>.</p>
    </div>
  </section>
</main>

<?php require __DIR__ . '/assets/includes/site-footer.php'; ?>
</body>
</html>
