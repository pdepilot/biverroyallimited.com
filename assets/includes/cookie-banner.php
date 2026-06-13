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
        We use cookies to improve browsing, analyse site traffic, and personalise content.
        Choose what you're comfortable with — you can update preferences anytime.
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
          <input type="checkbox" id="cookiePrefAnalytics" checked>
          <span class="cookie-pref-copy">
            <strong>Analytics</strong>
            <small>Helps us understand how visitors use our site</small>
          </span>
        </label>
        <label class="cookie-pref">
          <input type="checkbox" id="cookiePrefMarketing" checked>
          <span class="cookie-pref-copy">
            <strong>Marketing</strong>
            <small>Personalised offers and promotional content</small>
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
