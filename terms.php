<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/PageContentService.php';
require_once __DIR__ . '/includes/site_helpers.php';
require_once __DIR__ . '/includes/SeoService.php';

$terms = PageContentService::getPage('terms');
$hero = $terms['hero'] ?? [];
$sections = is_array($terms['sections'] ?? null) ? $terms['sections'] : [];
$intro = (string) ($terms['intro'] ?? '');
$updatedLabel = (string) ($terms['updatedLabel'] ?? 'Last updated');
$updatedAt = PageContentService::get()['updatedAt'] ?? null;
$updatedDisplay = is_string($updatedAt) && $updatedAt !== ''
    ? date('j F Y', strtotime($updatedAt))
    : date('j F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'Terms & Conditions | Biver Royalty Homes',
    'description' => 'Terms of use for the Biver Royalty Homes website, listings, and real estate services in Nigeria.',
    'keywords' => 'terms and conditions, Biver Royalty Homes legal, real estate Nigeria terms',
    'page' => 'terms',
    'stylesheets' => ['./assets/css/terms.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'Terms & Conditions'],
    ],
]);
?>
</head>
<body class="page-terms">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <section class="terms-hero">
    <div class="terms-hero-bg" aria-hidden="true"></div>
    <div class="container terms-hero-inner">
      <p class="terms-brand">Biver Royalty Homes</p>
      <p class="terms-eyebrow"><?= siteEscape((string) ($hero['eyebrow'] ?? 'Legal')) ?></p>
      <h1><?= siteEscape((string) ($hero['title'] ?? 'Terms & Conditions')) ?></h1>
      <p class="terms-lead"><?= siteEscape((string) ($hero['lead'] ?? '')) ?></p>
      <p class="terms-updated"><?= siteEscape($updatedLabel) ?>: <?= siteEscape($updatedDisplay) ?></p>
    </div>
  </section>

  <section class="terms-body">
    <div class="container terms-layout">
      <aside class="terms-toc" aria-label="On this page">
        <p class="terms-toc-title">Contents</p>
        <ol>
          <?php foreach ($sections as $i => $section): ?>
            <?php $anchor = 'section-' . ($i + 1); ?>
            <li><a href="#<?= siteEscape($anchor) ?>"><?= siteEscape((string) ($section['title'] ?? 'Section')) ?></a></li>
          <?php endforeach; ?>
        </ol>
      </aside>

      <div class="terms-content">
        <?php if ($intro !== ''): ?>
          <p class="terms-intro"><?= nl2br(siteEscape($intro)) ?></p>
        <?php endif; ?>

        <?php foreach ($sections as $i => $section): ?>
          <?php $anchor = 'section-' . ($i + 1); ?>
          <article class="terms-section" id="<?= siteEscape($anchor) ?>">
            <h2><?= siteEscape((string) ($section['title'] ?? '')) ?></h2>
            <div class="terms-section-body"><?= nl2br(siteEscape((string) ($section['content'] ?? ''))) ?></div>
          </article>
        <?php endforeach; ?>

        <div class="terms-footer-note">
          <p>Need clarification? <a href="<?= pageHref('contact') ?>">Contact our team</a> or call <a href="tel:<?= siteEscape(siteContactPhoneTel()) ?>"><?= siteEscape(siteContactPhone()) ?></a>.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/assets/includes/site-footer.php'; ?>

<button id="scrollToTop" type="button" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>
<script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
<?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>
