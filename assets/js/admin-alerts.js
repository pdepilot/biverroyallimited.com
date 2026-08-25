/**
 * Admin live alerts — loud long sound when new contact inquiries arrive.
 * Included on all admin pages via admin_sidebar.php.
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'bre_admin_alert_v1';
  const POLL_MS = 12000;
  const API = (function () {
    const scripts = document.getElementsByTagName('script');
    const self = scripts[scripts.length - 1];
    const src = self && self.src ? self.src : '';
    if (src.includes('/assets/js/')) {
      return src.replace(/\/assets\/js\/.*$/, '/admin/api/notifications.php');
    }
    return 'api/notifications.php';
  })();

  let audioCtx = null;
  let unlocked = false;
  let polling = false;
  let lastToastAt = 0;

  function readState() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') || {};
    } catch (_) {
      return {};
    }
  }

  function writeState(state) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (_) { /* ignore */ }
  }

  function ensureAudio() {
    if (!audioCtx) {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return null;
      audioCtx = new Ctx();
    }
    if (audioCtx.state === 'suspended') {
      audioCtx.resume().catch(() => {});
    }
    return audioCtx;
  }

  function unlockAudio() {
    const ctx = ensureAudio();
    if (!ctx) return;
    try {
      const buffer = ctx.createBuffer(1, 1, 22050);
      const src = ctx.createBufferSource();
      src.buffer = buffer;
      src.connect(ctx.destination);
      src.start(0);
      unlocked = true;
    } catch (_) { /* ignore */ }
  }

  /**
   * Loud ~5 second alternating alarm (siren-style).
   */
  function playLoudLongAlert() {
    const ctx = ensureAudio();
    if (!ctx) return;

    const now = ctx.currentTime;
    const duration = 5.2;
    const master = ctx.createGain();
    master.gain.setValueAtTime(0.0001, now);
    master.gain.exponentialRampToValueAtTime(0.55, now + 0.08);
    master.gain.setValueAtTime(0.55, now + duration - 0.35);
    master.gain.exponentialRampToValueAtTime(0.0001, now + duration);
    master.connect(ctx.destination);

    // Dual oscillators for a thick, attention-grabbing tone
    const oscA = ctx.createOscillator();
    const oscB = ctx.createOscillator();
    const gainA = ctx.createGain();
    const gainB = ctx.createGain();

    oscA.type = 'sawtooth';
    oscB.type = 'square';
    gainA.gain.value = 0.45;
    gainB.gain.value = 0.28;

    oscA.connect(gainA);
    oscB.connect(gainB);
    gainA.connect(master);
    gainB.connect(master);

    // Alternate between high/low siren pitches
    let t = now;
    const steps = 14;
    for (let i = 0; i < steps; i++) {
      const hi = i % 2 === 0;
      const freqA = hi ? 880 : 520;
      const freqB = hi ? 990 : 440;
      oscA.frequency.setValueAtTime(freqA, t);
      oscB.frequency.setValueAtTime(freqB, t);
      t += duration / steps;
    }

    oscA.start(now);
    oscB.start(now);
    oscA.stop(now + duration);
    oscB.stop(now + duration);
  }

  function showAlertToast(message) {
    const now = Date.now();
    if (now - lastToastAt < 4000) return;
    lastToastAt = now;

    document.querySelector('.admin-alert-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'admin-alert-toast';
    el.setAttribute('role', 'alert');
    el.innerHTML = '<strong>New inquiry</strong><span></span>';
    el.querySelector('span').textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 8000);
  }

  function ensureToastStyles() {
    if (document.getElementById('adminAlertToastStyles')) return;
    const style = document.createElement('style');
    style.id = 'adminAlertToastStyles';
    style.textContent = `
      .admin-alert-toast {
        position: fixed; top: 18px; right: 18px; left: 18px; max-width: 420px; margin-left: auto;
        z-index: 12000; background: #371801; color: #fffdf8; padding: 16px 18px; border-radius: 14px;
        box-shadow: 0 16px 40px rgba(0,0,0,0.28); border-left: 5px solid #D4AF37;
        font-family: Outfit, system-ui, sans-serif; display: grid; gap: 4px;
        animation: adminAlertIn 0.35s ease;
      }
      .admin-alert-toast strong { color: #F0D78C; font-size: 0.95rem; }
      .admin-alert-toast span { font-size: 0.88rem; line-height: 1.45; opacity: 0.95; }
      @keyframes adminAlertIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
      @media (max-width: 640px) { .admin-alert-toast { left: 12px; right: 12px; max-width: none; } }
    `;
    document.head.appendChild(style);
  }

  async function poll() {
    if (polling) return;
    polling = true;
    try {
      const res = await fetch(API, { credentials: 'same-origin', cache: 'no-store' });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.success) return;

      const contacts = data.contacts || {};
      const latestNewId = Number(contacts.latestNewId || 0);
      const newCount = Number(contacts.newCount || 0);
      const state = readState();

      // First run: baseline without alarming
      if (state.initialized !== true) {
        writeState({
          initialized: true,
          lastNewId: latestNewId,
          lastNewCount: newCount
        });
        return;
      }

      const prevId = Number(state.lastNewId || 0);
      if (latestNewId > prevId && newCount > 0) {
        playLoudLongAlert();
        const name = contacts.latestName || 'A visitor';
        const subject = contacts.latestSubject || 'general';
        showAlertToast(`${name} sent a new ${subject} enquiry. Open Inquiries to reply.`);
        window.dispatchEvent(new CustomEvent('bre:new-contact', { detail: contacts }));
      }

      writeState({
        initialized: true,
        lastNewId: Math.max(prevId, latestNewId),
        lastNewCount: newCount
      });
    } catch (_) {
      /* ignore transient poll errors */
    } finally {
      polling = false;
    }
  }

  function boot() {
    ensureToastStyles();
    const unlock = () => {
      unlockAudio();
      document.removeEventListener('pointerdown', unlock, true);
      document.removeEventListener('keydown', unlock, true);
    };
    document.addEventListener('pointerdown', unlock, true);
    document.addEventListener('keydown', unlock, true);

    poll();
    setInterval(poll, POLL_MS);

    // Expose for manual test from console if needed
    window.BiverAdminAlerts = {
      poll,
      play: playLoudLongAlert,
      unlock: unlockAudio
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
