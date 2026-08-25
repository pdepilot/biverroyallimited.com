/**
 * Public newsletter signup — posts to api/newsletter-subscribe.php
 */
(function () {
  'use strict';

  function apiUrl() {
    if (window.BIVER_SITE?.newsletterApi) return window.BIVER_SITE.newsletterApi;
    const base = window.BIVER_SITE?.base || '';
    return (base || '') + '/api/newsletter-subscribe.php';
  }

  async function submitForm(form) {
    const input = form.querySelector('input[type="email"]');
    const status = form.querySelector('[data-newsletter-status]');
    const button = form.querySelector('button[type="submit"]');
    const email = (input?.value || '').trim();
    if (!email) {
      if (status) status.textContent = 'Please enter your email address.';
      return;
    }

    if (button) button.disabled = true;
    if (status) status.textContent = 'Subscribing…';

    try {
      let timezone = '';
      try {
        timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
      } catch (_) { /* ignore */ }

      const res = await fetch(apiUrl(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email: email, timezone: timezone })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        throw new Error(data.message || 'Unable to subscribe right now.');
      }
      if (status) status.textContent = data.message || 'Thank you for subscribing.';
      form.reset();
    } catch (err) {
      if (status) status.textContent = err.message || 'Something went wrong.';
    } finally {
      if (button) button.disabled = false;
    }
  }

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-newsletter-form]')) return;
    event.preventDefault();
    submitForm(form);
  });
})();
