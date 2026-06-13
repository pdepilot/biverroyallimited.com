/**
 * Promotional banner + cookie consent — Biver Royalty Homes
 * Flow: preloader → promo spotlight → cookie banner
 */
(function () {
  'use strict';

  const STORAGE_PROMO  = 'biver_promo_dismissed_v1';
  const STORAGE_COOKIE = 'biver_cookie_consent_v1';

  let cookieScheduled = false;
  let cookieReady     = false;

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function readCookieConsent() {
    try {
      const raw = localStorage.getItem(STORAGE_COOKIE);
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  }

  function saveCookieConsent(consent) {
    try {
      localStorage.setItem(STORAGE_COOKIE, JSON.stringify({
        ...consent,
        updatedAt: new Date().toISOString()
      }));
    } catch (_) { /* private browsing */ }
  }

  function scheduleCookieBanner() {
    if (cookieScheduled || readCookieConsent()) return;
    cookieScheduled = true;

    const delay = prefersReducedMotion() ? 300 : 700;
    setTimeout(showCookieBanner, delay);
  }

  function dismissPromo(section) {
    section.classList.remove('is-visible', 'is-spotlight');
    section.classList.add('is-hidden');
    document.body.classList.remove('promo-open');
    document.body.style.overflow = '';
    scheduleCookieBanner();
  }

  function openPromoSpotlight(section) {
    document.body.classList.add('promo-open');
    document.body.style.overflow = 'hidden';
    window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    section.classList.add('is-spotlight', 'is-visible');
  }

  function initPromoBanner() {
    const section = document.getElementById('promoBannerSection');
    if (!section) {
      scheduleCookieBanner();
      return;
    }

    if (sessionStorage.getItem(STORAGE_PROMO) === '1') {
      section.classList.add('is-hidden');
      scheduleCookieBanner();
      return;
    }

    const closeBtn = document.getElementById('promoBannerClose');
    const link     = document.getElementById('promoBannerLink');

    const finishPromo = () => {
      sessionStorage.setItem(STORAGE_PROMO, '1');
      dismissPromo(section);
    };

    closeBtn?.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      finishPromo();
    });

    link?.addEventListener('click', () => {
      sessionStorage.setItem(STORAGE_PROMO, '1');
      document.body.classList.remove('promo-open');
      document.body.style.overflow = '';
      scheduleCookieBanner();
    });

    const startPromo = () => openPromoSpotlight(section);

    const preloader = document.getElementById('preloader');
    if (!preloader || preloader.classList.contains('hidden')) {
      startPromo();
    } else {
      window.addEventListener('biver:preloader-done', startPromo, { once: true });
      setTimeout(startPromo, 4500);
    }
  }

  function hideCookieBanner(banner, backdrop) {
    banner.classList.remove('is-visible');
    banner.classList.add('is-exiting');
    backdrop?.classList.remove('is-visible');

    const done = () => {
      banner.hidden = true;
      banner.classList.remove('is-exiting');
      backdrop?.setAttribute('hidden', '');
      backdrop?.setAttribute('aria-hidden', 'true');
    };

    if (prefersReducedMotion()) {
      done();
    } else {
      banner.addEventListener('transitionend', done, { once: true });
      setTimeout(done, 900);
    }
  }

  function showCookieBanner() {
    if (cookieReady || readCookieConsent()) return;
    cookieReady = true;

    const banner   = document.getElementById('cookieBanner');
    const backdrop = document.getElementById('cookieBannerBackdrop');
    if (!banner) return;

    banner.hidden = false;
    backdrop?.removeAttribute('hidden');
    backdrop?.setAttribute('aria-hidden', 'false');

    requestAnimationFrame(() => {
      backdrop?.classList.add('is-visible');
      banner.classList.add('is-visible');
    });
  }

  function initCookieBanner() {
    const banner   = document.getElementById('cookieBanner');
    const backdrop = document.getElementById('cookieBannerBackdrop');
    if (!banner) return;

    if (readCookieConsent()) {
      banner.hidden = true;
      return;
    }

    const prefsPanel   = document.getElementById('cookieBannerPrefs');
    const btnAccept    = document.getElementById('cookieBtnAccept');
    const btnEssential = document.getElementById('cookieBtnEssential');
    const btnManage    = document.getElementById('cookieBtnManage');
    const prefAnalytics  = document.getElementById('cookiePrefAnalytics');
    const prefMarketing  = document.getElementById('cookiePrefMarketing');

    let prefsOpen = false;

    const finalize = (consent) => {
      saveCookieConsent(consent);
      hideCookieBanner(banner, backdrop);
    };

    btnManage?.addEventListener('click', () => {
      if (!prefsOpen) {
        prefsOpen = true;
        if (prefsPanel) prefsPanel.hidden = false;
        btnManage.textContent = 'Save Preferences';
        return;
      }
      finalize({
        essential: true,
        analytics: !!prefAnalytics?.checked,
        marketing: !!prefMarketing?.checked
      });
    });

    btnAccept?.addEventListener('click', () => {
      finalize({ essential: true, analytics: true, marketing: true });
    });

    btnEssential?.addEventListener('click', () => {
      finalize({ essential: true, analytics: false, marketing: false });
    });

    banner.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        finalize({ essential: true, analytics: false, marketing: false });
      }
    });
  }

  function reopenCookieSettings() {
    const banner   = document.getElementById('cookieBanner');
    const backdrop = document.getElementById('cookieBannerBackdrop');
    if (!banner) return;

    const existing = readCookieConsent();
    const prefAnalytics = document.getElementById('cookiePrefAnalytics');
    const prefMarketing = document.getElementById('cookiePrefMarketing');
    const prefsPanel = document.getElementById('cookieBannerPrefs');
    const btnManage = document.getElementById('cookieBtnManage');

    if (existing) {
      if (prefAnalytics) prefAnalytics.checked = !!existing.analytics;
      if (prefMarketing) prefMarketing.checked = !!existing.marketing;
    }

    if (prefsPanel) prefsPanel.hidden = false;
    if (btnManage) btnManage.textContent = 'Save Preferences';

    cookieReady = false;
    showCookieBanner();
  }

  document.addEventListener('DOMContentLoaded', () => {
    initCookieBanner();
    initPromoBanner();
  });

  window.BiverBanners = {
    initPromoBanner,
    initCookieBanner,
    reopenCookieSettings,
    scheduleCookieBanner
  };
})();
