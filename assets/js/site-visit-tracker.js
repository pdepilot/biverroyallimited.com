/**
 * Lightweight site visit tracker — only runs when analytics cookies are accepted.
 */
(function () {
  'use strict';

  const CONSENT_KEY = 'biver_cookie_consent_v1';
  const VISITOR_KEY = 'biver_visitor_id_v1';
  const SESSION_HIT = 'biver_visit_hit_';

  function readConsent() {
    try {
      const raw = localStorage.getItem(CONSENT_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  }

  function analyticsAllowed() {
    const c = readConsent();
    return !!(c && c.analytics);
  }

  function uuid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (ch) => {
      const r = (Math.random() * 16) | 0;
      const v = ch === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  function getVisitorId() {
    try {
      let id = localStorage.getItem(VISITOR_KEY);
      if (!id || !/^[a-f0-9-]{36}$/i.test(id)) {
        id = uuid();
        localStorage.setItem(VISITOR_KEY, id);
      }
      return id;
    } catch (_) {
      return uuid();
    }
  }

  function trackEndpoint() {
    if (window.BIVER_SITE && window.BIVER_SITE.trackVisitApi) {
      return window.BIVER_SITE.trackVisitApi;
    }
    const base = (window.BIVER_SITE && window.BIVER_SITE.base) || '';
    return (base || '') + '/api/track-visit.php';
  }

  function pagePath() {
    try {
      return window.location.pathname + (window.location.search || '');
    } catch (_) {
      return '/';
    }
  }

  function alreadyHitThisLoad() {
    const key = SESSION_HIT + pagePath();
    try {
      if (sessionStorage.getItem(key) === '1') return true;
      sessionStorage.setItem(key, '1');
    } catch (_) { /* ignore */ }
    return false;
  }

  function sendVisit() {
    if (!analyticsAllowed()) return;
    if (alreadyHitThisLoad()) return;

    const payload = {
      visitorKey: getVisitorId(),
      pagePath: pagePath(),
      pageTitle: document.title || '',
      referrer: document.referrer || '',
      userAgent: navigator.userAgent || ''
    };

    const url = trackEndpoint();
    const body = JSON.stringify(payload);

    try {
      if (navigator.sendBeacon) {
        const blob = new Blob([body], { type: 'application/json' });
        if (navigator.sendBeacon(url, blob)) return;
      }
    } catch (_) { /* fall through */ }

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body,
      keepalive: true,
      credentials: 'same-origin'
    }).catch(() => {});
  }

  function onConsent(consent) {
    if (consent && consent.analytics) {
      try { sessionStorage.removeItem(SESSION_HIT + pagePath()); } catch (_) {}
      sendVisit();
    }
  }

  window.BiverVisitTracker = { track: sendVisit, onConsent };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', sendVisit);
  } else {
    sendVisit();
  }
})();
