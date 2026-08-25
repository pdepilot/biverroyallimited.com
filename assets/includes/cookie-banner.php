<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/site_paths.php';
?>
<div class="cookie-banner-backdrop" id="cookieBannerBackdrop" hidden aria-hidden="true"></div>

<aside
  class="cookie-banner"
  id="cookieBanner"
  role="dialog"
  aria-modal="true"
  aria-labelledby="cookieBannerTitle"
  aria-describedby="cookieBannerDesc"
  hidden
>
  <div class="cookie-banner-shimmer" aria-hidden="true"></div>
  <div class="cookie-banner-inner">
    <div class="cookie-banner-icon-wrap" aria-hidden="true">
      <span class="cookie-banner-ring cookie-banner-ring--1"></span>
      <span class="cookie-banner-ring cookie-banner-ring--2"></span>
      <span class="cookie-banner-icon">
        <ion-icon name="shield-checkmark-outline"></ion-icon>
      </span>
    </div>

    <div class="cookie-banner-content">
      <p class="cookie-banner-eyebrow">Your Privacy Matters</p>
      <h2 class="cookie-banner-title" id="cookieBannerTitle">We value your experience</h2>
      <p class="cookie-banner-desc" id="cookieBannerDesc">
        We use cookies to run the site, measure visits, and (if you allow marketing) show Google AdSense ads.
        Read our <a href="<?= htmlspecialchars(pageUrl('privacy'), ENT_QUOTES, 'UTF-8') ?>">Privacy Policy</a>
        and <a href="<?= htmlspecialchars(pageUrl('cookie-policy'), ENT_QUOTES, 'UTF-8') ?>">Cookie Policy</a>.
        You can change this anytime.
      </p>

      <div class="cookie-banner-prefs" id="cookieBannerPrefs" hidden>
        <label class="cookie-pref">
          <input type="checkbox" checked disabled>
          <span class="cookie-pref-copy">
            <strong>Essential</strong>
            <small>Required for the site to function</small>
          </span>
        </label>
        <label class="cookie-pref">
          <input type="checkbox" id="cookiePrefAnalytics">
          <span class="cookie-pref-copy">
            <strong>Analytics</strong>
            <small>Helps us understand how visitors use our site</small>
          </span>
        </label>
        <label class="cookie-pref">
          <input type="checkbox" id="cookiePrefMarketing">
          <span class="cookie-pref-copy">
            <strong>Marketing</strong>
            <small>Google AdSense and personalised advertising</small>
          </span>
        </label>
      </div>

      <div class="cookie-banner-actions">
        <button type="button" class="cookie-btn cookie-btn--ghost" id="cookieBtnManage">Manage</button>
        <button type="button" class="cookie-btn cookie-btn--outline" id="cookieBtnEssential">Essential Only</button>
        <button type="button" class="cookie-btn cookie-btn--primary" id="cookieBtnAccept">Accept All</button>
      </div>
    </div>
  </div>
</aside>
