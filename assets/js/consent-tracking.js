/**
 * Google Consent Mode v2 + GA4 updates after cookie banner choice.
 * AdSense script is always in <head>; this only updates storage consent.
 */
(function () {
  'use strict';

  var STORAGE = 'biver_cookie_consent_v1';

  function readConsent() {
    try {
      var raw = localStorage.getItem(STORAGE);
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  }

  function applyConsent(consent) {
    if (!consent || typeof gtag !== 'function') return;
    gtag('consent', 'update', {
      ad_storage: consent.marketing ? 'granted' : 'denied',
      ad_user_data: consent.marketing ? 'granted' : 'denied',
      ad_personalization: consent.marketing ? 'granted' : 'denied',
      analytics_storage: consent.analytics ? 'granted' : 'denied',
      personalization_storage: consent.marketing ? 'granted' : 'denied'
    });
    if (typeof gtag === 'function') {
      gtag('set', 'ads_data_redaction', !consent.marketing);
    }
  }

  document.addEventListener('biver:consent', function (event) {
    applyConsent(event.detail || readConsent());
  });

  var existing = readConsent();
  if (existing) {
    applyConsent(existing);
  }
})();
