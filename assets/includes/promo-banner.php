<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/BannerService.php';

if (!BannerService::shouldShowPromo()) {
    return;
}

$config = BannerService::promoConfig();
$flier  = BannerService::promoFlierUrls($config);
$link   = BannerService::promoLinkUrl($config);

$eyebrow     = siteEscape((string) ($config['eyebrow'] ?? 'Limited Time Offer'));
$headline    = siteEscape((string) ($config['headline'] ?? 'New Listings Just Dropped'));
$subheadline = siteEscape((string) ($config['subheadline'] ?? ''));
$ctaLabel    = siteEscape((string) ($config['ctaLabel'] ?? 'Browse Properties'));
$altText     = siteEscape((string) ($config['altText'] ?? 'Promotional banner'));
$badgeText   = siteEscape((string) ($config['badgeText'] ?? 'Featured'));
$showBadge   = !empty($config['showBadge']);
?>
<section class="promo-banner-section" id="promoBannerSection" aria-label="Promotional offer">
  <div class="promo-banner-shell">
    <header class="promo-banner-header">
      <p class="promo-banner-section-eyebrow"><?= $eyebrow ?></p>
      <h2 class="promo-banner-section-title"><?= $headline ?></h2>
      <?php if ($subheadline !== ''): ?>
        <p class="promo-banner-section-subtitle"><?= $subheadline ?></p>
      <?php endif; ?>
    </header>

    <div class="promo-banner-glow" aria-hidden="true"></div>
    <div class="promo-banner-sparkle promo-banner-sparkle--1" aria-hidden="true"></div>
    <div class="promo-banner-sparkle promo-banner-sparkle--2" aria-hidden="true"></div>

    <div class="promo-banner-frame">
      <button type="button" class="promo-banner-close" id="promoBannerClose" aria-label="Dismiss promotion">
        <ion-icon name="close-outline"></ion-icon>
      </button>

      <a href="<?= siteEscape($link) ?>" class="promo-banner-link" id="promoBannerLink" aria-label="<?= $altText ?>">
        <?php if ($flier['hasFlier']): ?>
          <div class="promo-banner-media">
            <?php if ($showBadge && $badgeText !== ''): ?>
              <span class="promo-banner-badge"><?= $badgeText ?></span>
            <?php endif; ?>
            <picture class="promo-banner-picture">
              <?php if ($flier['mobile']): ?>
                <source media="(max-width: 767px)" srcset="<?= siteEscape($flier['mobile']) ?>">
              <?php endif; ?>
              <img
                src="<?= siteEscape($flier['desktop'] ?? $flier['mobile'] ?? '') ?>"
                alt="<?= $altText ?>"
                class="promo-banner-flier"
                loading="lazy"
                decoding="async"
              >
            </picture>
            <div class="promo-banner-overlay" aria-hidden="true"></div>
            <span class="promo-banner-hover-cta">
              <span><?= $ctaLabel ?></span>
              <ion-icon name="arrow-forward-outline"></ion-icon>
            </span>
          </div>
        <?php else: ?>
          <div class="promo-banner-fallback">
            <div class="promo-banner-fallback-bg" aria-hidden="true"></div>
            <div class="promo-banner-fallback-content">
              <?php if ($showBadge && $badgeText !== ''): ?>
                <span class="promo-banner-badge promo-banner-badge--inline"><?= $badgeText ?></span>
              <?php endif; ?>
              <p class="promo-banner-eyebrow"><?= $eyebrow ?></p>
              <h3 class="promo-banner-title"><?= $headline ?></h3>
              <?php if ($subheadline !== ''): ?>
                <p class="promo-banner-subtitle"><?= $subheadline ?></p>
              <?php endif; ?>
              <span class="promo-banner-cta">
                <?= $ctaLabel ?>
                <ion-icon name="arrow-forward-outline"></ion-icon>
              </span>
            </div>
          </div>
        <?php endif; ?>
      </a>
    </div>

    <p class="promo-banner-footnote">
      <ion-icon name="sparkles-outline"></ion-icon>
      Tap the banner to explore our latest property listings
    </p>
  </div>
</section>
