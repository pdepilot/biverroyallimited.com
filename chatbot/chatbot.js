/**
 * Biver Royalty Homes — AI Chat Assistant Frontend
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'brh_chat_visitor_uuid';
  const EMOJIS = ['😊', '👋', '🏡', '🔑', '💰', '📍', '📞', '✅', '❤️', '🙏', '👍', '🏠', '🌟', '💬', '📅'];

  const state = {
    apiUrl: '',
    csrfToken: '',
    visitorUuid: '',
    sessionUuid: '',
    config: {},
    messages: [],
    lastMessageId: 0,
    isOpen: false,
    isMinimized: false,
    hasInteracted: false,
    pollTimer: null,
    welcomeTimers: [],
    unreadCount: 0,
    mode: 'bot',
    status: 'active',
    soundEnabled: true,
    isSending: false,
    initialized: false,
    initializing: false,
  };

  const els = {};

  function $(id) {
    return document.getElementById(id);
  }

  function resolveApiUrl(url) {
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url;
    // Server already returns absolute path e.g. /BIVER_ROYAL_ESTATE/chatbot/chatbot-api.php
    if (url.startsWith('/')) return url;
    const base = ((window.BIVER_SITE && window.BIVER_SITE.base) || '').replace(/\/$/, '');
    const clean = url.replace(/^\//, '');
    const baseKey = base.replace(/^\//, '');
    if (baseKey && clean.startsWith(baseKey)) {
      return '/' + clean;
    }
    return (base ? (base.startsWith('/') ? base : '/' + base) : '') + '/' + clean;
  }

  function setChatReady(ready, message) {
    if (!els.input || !els.sendBtn) return;
    els.input.disabled = !ready;
    els.sendBtn.disabled = !ready;
    if (!ready && message) {
      els.input.placeholder = message;
    } else if (ready) {
      els.input.placeholder = 'Type your message…';
    }
  }

  function getVisitorUuid() {
    let uuid = localStorage.getItem(STORAGE_KEY);
    if (!uuid || !/^[a-f0-9-]{36}$/i.test(uuid)) {
      uuid = crypto.randomUUID();
      localStorage.setItem(STORAGE_KEY, uuid);
    }
    return uuid;
  }

  function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
  }

  function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function playNotificationSound() {
    if (!state.soundEnabled) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.08, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.25);
    } catch (_) { /* silent */ }
  }

  async function parseJsonResponse(res) {
    const text = await res.text();
    if (!text) {
      throw new Error('Empty response from server. Check that chatbot tables are installed.');
    }
    try {
      return JSON.parse(text);
    } catch (_) {
      throw new Error('Server returned an invalid response. Please run sql/install_chatbot.php.');
    }
  }

  async function api(action, body = {}, method = 'POST') {
    const url = `${state.apiUrl}?action=${encodeURIComponent(action)}`;
    const opts = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Chatbot-CSRF': state.csrfToken || '',
      },
      credentials: 'same-origin',
    };

    if (method !== 'GET') {
      opts.body = JSON.stringify({
        ...body,
        csrf_token: state.csrfToken,
        session_uuid: state.sessionUuid,
      });
    }

    const res = await fetch(url, opts);
    const data = await parseJsonResponse(res);

    if (!res.ok || data.success === false) {
      throw new Error(data.error || `Request failed (${res.status})`);
    }
    return data;
  }

  async function fetchCsrf() {
    const res = await fetch(`${state.apiUrl}?action=csrf`, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await parseJsonResponse(res);
    if (data.csrf_token) state.csrfToken = data.csrf_token;
  }

  async function initSession() {
    if (state.initialized || state.initializing) return;
    state.initializing = true;
    setChatReady(false, 'Connecting…');
    try {
      await fetchCsrf();
      const data = await api('init', {
        visitor_uuid: state.visitorUuid,
        page_url: window.location.href,
      });

      state.sessionUuid = data.session_uuid;
      state.csrfToken = data.csrf_token || state.csrfToken;
      state.config = { ...state.config, ...(data.config || {}) };
      state.mode = data.mode || 'bot';
      state.status = data.status || 'active';
      state.messages = data.messages || [];
      state.lastMessageId = state.messages.reduce((max, m) => Math.max(max, m.id || 0), 0);
      state.initialized = true;

      renderAllMessages();
      startPolling();
      setChatReady(true);
    } finally {
      state.initializing = false;
    }
  }

  function renderAllMessages() {
    els.messages.innerHTML = '';
    state.messages.forEach(renderMessage);
    scrollToBottom();
  }

  function renderMessage(msg) {
    if (!msg || !msg.message) return;

    const isUser = msg.senderType === 'visitor';
    const row = document.createElement('div');
    row.className = `brh-msg-row${isUser ? ' is-user' : ''}`;
    row.dataset.id = msg.id;

    let avatarHtml;
    if (isUser) {
      avatarHtml = '<div class="brh-msg-avatar is-user" aria-hidden="true">You</div>';
    } else {
      const src = state.config.agentAvatar || '';
      avatarHtml = `<img class="brh-msg-avatar" src="${escapeHtml(src)}" alt="Assistant" width="32" height="32">`;
    }

    const readIcon = isUser
      ? `<span class="brh-read-icon" title="${msg.deliveryStatus || 'sent'}">${msg.deliveryStatus === 'read' ? '✓✓' : msg.deliveryStatus === 'delivered' ? '✓✓' : '✓'}</span>`
      : '';

    let extra = '';
    if (!isUser && msg.metadata) {
      if (msg.metadata.show_resume_bot_button) {
        extra = `
          <div class="brh-support-actions">
            <button type="button" class="brh-resume-bot-btn brh-pointer">Continue with AI</button>
          </div>`;
      } else if (msg.metadata.show_support_options || msg.metadata.offer_human_support || msg.metadata.show_escalation_button) {
        extra = `
          <div class="brh-support-actions">
            <button type="button" class="brh-agent-connect-btn brh-pointer">Chat With Agent</button>
            <button type="button" class="brh-support-form-btn brh-pointer">Request Human Response</button>
          </div>`;
      }
    }

    row.innerHTML = `
      ${avatarHtml}
      <div class="brh-msg-body">
        <div class="brh-msg-bubble">${escapeHtml(msg.message).replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')}</div>
        ${extra}
        <div class="brh-msg-meta">
          <span>${formatTime(msg.createdAt)}</span>
          ${readIcon}
        </div>
      </div>`;

    row.querySelector('.brh-agent-connect-btn')?.addEventListener('click', handleAgentConnect);
    row.querySelector('.brh-support-form-btn')?.addEventListener('click', openSupportModal);
    row.querySelector('.brh-resume-bot-btn')?.addEventListener('click', handleResumeBot);

    els.messages.appendChild(row);
  }

  function appendMessages(newMsgs) {
    let added = false;
    newMsgs.forEach((msg) => {
      if (state.messages.some((m) => m.id === msg.id)) return;
      state.messages.push(msg);
      state.lastMessageId = Math.max(state.lastMessageId, msg.id);
      renderMessage(msg);
      added = true;

      if (msg.senderType !== 'visitor' && !state.isOpen) {
        state.unreadCount++;
        updateBadge();
        playNotificationSound();
      }
    });

    if (added) {
      scrollToBottom();
      if (state.isOpen) markRead();
    }
  }

  function scrollToBottom() {
    requestAnimationFrame(() => {
      els.messages.scrollTop = els.messages.scrollHeight;
    });
  }

  function updateBadge() {
    if (state.unreadCount > 0) {
      els.badge.textContent = state.unreadCount > 9 ? '9+' : String(state.unreadCount);
      els.badge.classList.add('is-visible');
    } else {
      els.badge.classList.remove('is-visible');
    }
  }

  function showTyping(show) {
    els.typing.classList.toggle('is-visible', show);
    if (show) scrollToBottom();
  }

  function randomTypingDelay() {
    const min = state.config.typingMin || 600;
    const max = state.config.typingMax || 1800;
    return min + Math.random() * (max - min);
  }

  async function sendMessage(text, isRetry = false) {
    const message = text.trim();
    if (!message || state.isSending) return;

    state.hasInteracted = true;
    clearWelcomeTimers();
    state.isSending = true;
    els.sendBtn.disabled = true;

    try {
      if (!state.initialized) {
        await initSession();
      }
      if (!state.sessionUuid) {
        throw new Error('Chat session not ready. Please wait a moment and try again.');
      }

      showTyping(true);
      const delay = state.mode === 'bot' ? randomTypingDelay() : 200;
      await new Promise((r) => setTimeout(r, delay));

      const data = await api('send', { message });
      showTyping(false);

      if (data.messages) appendMessages(data.messages);
      if (data.mode === 'human') {
        state.mode = 'human';
      } else if (data.mode) {
        state.mode = data.mode;
      }
      if (data.status) state.status = data.status;
    } catch (err) {
      showTyping(false);
      if (!isRetry && /session|token|401|403/i.test(err.message)) {
        state.initialized = false;
        try {
          await initSession();
          state.isSending = false;
          els.sendBtn.disabled = false;
          await sendMessage(message, true);
          return;
        } catch (retryErr) {
          console.error('Chat retry failed:', retryErr.message);
        }
      }
      const hint = err.message && !/try again/i.test(err.message)
        ? err.message
        : 'Sorry, I could not send your message. Please try again.';
      appendMessages([{
        id: Date.now(),
        senderType: 'system',
        message: hint,
        createdAt: new Date().toISOString(),
        deliveryStatus: 'delivered',
      }]);
      console.error('Chat send error:', err.message);
    } finally {
      state.isSending = false;
      els.sendBtn.disabled = false;
    }
  }

  async function markRead() {
    if (!state.sessionUuid) return;
    state.unreadCount = 0;
    updateBadge();
    try {
      await api('mark_read', {});
    } catch (_) { /* silent */ }
  }

  function openSupportModal() {
    const modal = $('brh-support-modal');
    const lastUserMsg = [...state.messages].reverse().find((m) => m.senderType === 'visitor');
    const q = $('brh-support-question');
    if (q && lastUserMsg) q.value = lastUserMsg.message;
    modal?.classList.add('is-open');
  }

  function closeSupportModal() {
    $('brh-support-modal')?.classList.remove('is-open');
  }

  async function handleResumeBot() {
    try {
      if (!state.initialized) await initSession();
      const data = await api('resume_bot', {});
      if (data.messages) appendMessages(data.messages);
      else if (data.message) appendMessages([data.message]);
      state.mode = 'bot';
      state.status = data.status || 'active';
    } catch (err) {
      appendMessages([{
        id: Date.now(),
        senderType: 'system',
        message: err.message || 'Could not switch back to AI mode.',
        createdAt: new Date().toISOString(),
        deliveryStatus: 'delivered',
      }]);
    }
  }

  async function handleAgentConnect() {
    try {
      if (!state.initialized) await initSession();
      const data = await api('agent_connect', {});
      if (data.message) appendMessages([data.message]);
      state.mode = 'human';
      state.status = 'waiting';
    } catch (err) {
      appendMessages([{
        id: Date.now(),
        senderType: 'system',
        message: err.message || 'Could not connect to an agent. Please try again.',
        createdAt: new Date().toISOString(),
        deliveryStatus: 'delivered',
      }]);
    }
  }

  async function submitSupportForm(ev) {
    ev.preventDefault();
    const name = ($('brh-support-name')?.value || '').trim();
    const phone = ($('brh-support-phone')?.value || '').trim();
    const email = ($('brh-support-email')?.value || '').trim();
    const question = ($('brh-support-question')?.value || '').trim();

    if (!name || !phone || !question) return;

    try {
      if (!state.initialized) await initSession();
      const submitUrl = resolveApiUrl(state.config.supportSubmitUrl || '');
      const res = await fetch(submitUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-Chatbot-CSRF': state.csrfToken || '',
        },
        body: JSON.stringify({
          name,
          phone,
          email: email || null,
          question,
          session_uuid: state.sessionUuid,
          csrf_token: state.csrfToken,
        }),
      });
      const data = await parseJsonResponse(res);
      if (!data.success) throw new Error(data.error || 'Submission failed');
      if (data.chat) appendMessages([data.chat]);
      state.mode = 'human';
      state.status = 'waiting';
      closeSupportModal();
      $('brh-support-form')?.reset();
    } catch (err) {
      try {
        const data = await api('support_request', { name, phone, email, question });
        if (data.message) appendMessages([data.message]);
        state.mode = 'human';
        state.status = 'waiting';
        closeSupportModal();
        $('brh-support-form')?.reset();
      } catch (fallbackErr) {
        alert(fallbackErr.message || err.message || 'Could not submit your request.');
      }
    }
  }

  function startPolling() {
    stopPolling();
    const interval = state.config.pollInterval || 3000;
    state.pollTimer = setInterval(pollMessages, interval);
  }

  function stopPolling() {
    if (state.pollTimer) {
      clearInterval(state.pollTimer);
      state.pollTimer = null;
    }
  }

  async function pollMessages() {
    if (!state.sessionUuid) return;
    try {
      const res = await fetch(
        `${state.apiUrl}?action=poll&session_uuid=${encodeURIComponent(state.sessionUuid)}&after_id=${state.lastMessageId}`,
        { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }
      );
      const data = await res.json();
      if (data.success && data.messages && data.messages.length) {
        appendMessages(data.messages);
      }
      if (data.mode) state.mode = data.mode;
      if (data.status) state.status = data.status;
    } catch (_) { /* silent */ }
  }

  function openChat() {
    state.isOpen = true;
    state.isMinimized = false;
    state.hasInteracted = true;
    clearWelcomeTimers();
    els.window.classList.add('is-open');
    els.window.classList.remove('is-minimized');
    document.body.classList.add('brh-chat-active', 'brh-chat-present');
    state.unreadCount = 0;
    updateBadge();
    markRead();
    scrollToBottom();

    if (!state.initialized && !state.initializing) {
      initSession().catch((err) => {
        console.error('Chat init error:', err.message);
        appendMessages([{
          id: Date.now(),
          senderType: 'system',
          message: err.message || 'Chat is temporarily unavailable. Please run sql/install_chatbot.php.',
          createdAt: new Date().toISOString(),
          deliveryStatus: 'delivered',
        }]);
      });
    } else if (state.initialized) {
      setChatReady(true);
    }
  }

  function closeChat() {
    state.isOpen = false;
    els.window.classList.remove('is-open');
    document.body.classList.remove('brh-chat-active');
  }

  function toggleMinimize() {
    state.isMinimized = !state.isMinimized;
    els.window.classList.toggle('is-minimized', state.isMinimized);
  }

  function clearWelcomeTimers() {
    state.welcomeTimers.forEach(clearTimeout);
    state.welcomeTimers = [];
    document.querySelectorAll('.brh-welcome-bubble').forEach((b) => b.remove());
  }

  function scheduleWelcomeBubbles() {
    const bubbles = [
      { delay: state.config.welcomeDelay1 || 3000, text: '👋 Welcome to Biver Royalty Homes. I\'m your virtual property assistant. How may I help you today?' },
      { delay: state.config.welcomeDelay2 || 8000, text: '🏡 Looking for land, rental properties, luxury homes, or investment opportunities? I\'m here to help.' },
      { delay: state.config.welcomeDelay3 || 15000, text: '💬 Click here to chat with our intelligent property assistant.' },
    ];

    bubbles.forEach(({ delay, text }) => {
      const timer = setTimeout(() => {
        if (state.hasInteracted || state.isOpen) return;

        const bubble = document.createElement('button');
        bubble.type = 'button';
        bubble.className = 'brh-welcome-bubble brh-pointer';
        bubble.textContent = text;
        bubble.addEventListener('click', openChat);
        els.welcomeStack.appendChild(bubble);

        requestAnimationFrame(() => bubble.classList.add('is-visible'));
      }, delay);
      state.welcomeTimers.push(timer);
    });
  }

  function toggleEmojiPanel() {
    els.emojiPanel.classList.toggle('is-open');
    els.searchPanel.classList.remove('is-open');
  }

  function toggleSearchPanel() {
    els.searchPanel.classList.toggle('is-open');
    els.emojiPanel.classList.remove('is-open');
  }

  async function handleSearch(query) {
    if (!query.trim() || !state.initialized) return;
    try {
      const data = await api('search', { query: query.trim() });
      els.messages.querySelectorAll('.brh-msg-bubble').forEach((b) => {
        b.style.outline = '';
      });
      (data.results || []).forEach((r) => {
        const row = els.messages.querySelector(`[data-id="${r.id}"] .brh-msg-bubble`);
        if (row) row.style.outline = '2px solid var(--brh-chat-gold)';
      });
    } catch (_) { /* silent */ }
  }

  function openInspectionModal() {
    els.inspectionModal.classList.add('is-open');
  }

  function closeInspectionModal() {
    els.inspectionModal.classList.remove('is-open');
  }

  async function submitInspection(e) {
    e.preventDefault();
    const form = e.target;
    const payload = {
      property: form.property.value,
      preferred_date: form.preferred_date.value,
      name: form.name.value,
      phone: form.phone.value,
      email: form.email.value,
      notes: form.notes.value,
    };

    try {
      if (!state.initialized) await initSession();
      const data = await api('inspection', payload);
      if (data.message) appendMessages([data.message]);
      closeInspectionModal();
      form.reset();
    } catch (err) {
      alert(err.message || 'Could not submit inspection request.');
    }
  }

  function exportConversation() {
    const lines = state.messages.map((m) => {
      const who = m.senderType === 'visitor' ? 'You' : m.senderType === 'agent' ? 'Agent' : 'Assistant';
      return `[${formatTime(m.createdAt)}] ${who}: ${m.message}`;
    });
    const blob = new Blob([lines.join('\n\n')], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `biver-chat-${state.sessionUuid.slice(0, 8)}.txt`;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function handleInputKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      const text = els.input.value;
      els.input.value = '';
      els.input.style.height = 'auto';
      sendMessage(text);
    }
  }

  function autoResizeInput() {
    els.input.style.height = 'auto';
    els.input.style.height = Math.min(els.input.scrollHeight, 120) + 'px';
  }

  function bindEvents() {
    els.launcher.addEventListener('click', () => {
      if (state.isOpen) closeChat();
      else openChat();
    });

    els.closeBtn.addEventListener('click', closeChat);
    els.minBtn.addEventListener('click', toggleMinimize);
    els.sendBtn.addEventListener('click', () => {
      const text = els.input.value;
      els.input.value = '';
      els.input.style.height = 'auto';
      sendMessage(text);
    });

    els.input.addEventListener('keydown', handleInputKeydown);
    els.input.addEventListener('input', autoResizeInput);

    els.emojiToggle.addEventListener('click', toggleEmojiPanel);
    els.searchToggle.addEventListener('click', toggleSearchPanel);
    els.exportToggle.addEventListener('click', exportConversation);
    els.inspectionToggle.addEventListener('click', openInspectionModal);
    els.soundToggle.addEventListener('click', () => {
      state.soundEnabled = !state.soundEnabled;
      els.soundToggle.textContent = state.soundEnabled ? '🔔' : '🔕';
    });

    EMOJIS.forEach((emoji) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'brh-emoji-btn brh-pointer';
      btn.textContent = emoji;
      btn.addEventListener('click', () => {
        els.input.value += emoji;
        els.input.focus();
      });
      els.emojiPanel.appendChild(btn);
    });

    els.searchInput.addEventListener('input', (e) => {
      clearTimeout(els.searchInput._timer);
      els.searchInput._timer = setTimeout(() => handleSearch(e.target.value), 400);
    });

    els.inspectionForm.addEventListener('submit', submitInspection);
    els.inspectionCancel.addEventListener('click', closeInspectionModal);
    els.inspectionModal.addEventListener('click', (e) => {
      if (e.target === els.inspectionModal) closeInspectionModal();
    });

    if (els.supportForm) {
      els.supportForm.addEventListener('submit', submitSupportForm);
    }
    els.supportCancel?.addEventListener('click', closeSupportModal);
    els.supportModal?.addEventListener('click', (e) => {
      if (e.target === els.supportModal) closeSupportModal();
    });

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible' && state.isOpen) markRead();
    });
  }

  function cacheElements() {
    els.root = $('brh-chat-root');
    if (!els.root) return false;

    els.launcher = $('brh-chat-launcher');
    els.badge = $('brh-chat-badge');
    els.window = $('brh-chat-window');
    els.messages = $('brh-chat-messages');
    els.typing = $('brh-chat-typing');
    els.input = $('brh-chat-input');
    els.sendBtn = $('brh-chat-send');
    els.closeBtn = $('brh-chat-close');
    els.minBtn = $('brh-chat-minimize');
    els.welcomeStack = $('brh-welcome-stack');
    els.emojiPanel = $('brh-emoji-panel');
    els.emojiToggle = $('brh-emoji-toggle');
    els.searchPanel = $('brh-search-panel');
    els.searchInput = $('brh-search-input');
    els.searchToggle = $('brh-search-toggle');
    els.exportToggle = $('brh-export-toggle');
    els.inspectionToggle = $('brh-inspection-toggle');
    els.soundToggle = $('brh-sound-toggle');
    els.inspectionModal = $('brh-inspection-modal');
    els.inspectionForm = $('brh-inspection-form');
    els.inspectionCancel = $('brh-inspection-cancel');
    els.supportModal = $('brh-support-modal');
    els.supportForm = $('brh-support-form');
    els.supportCancel = $('brh-support-cancel');

    return true;
  }

  function init() {
    if (!cacheElements()) return;

    document.body.classList.add('brh-chat-present');

    const boot = window.BRH_CHAT_BOOT || {};
    state.apiUrl = resolveApiUrl(
      boot.apiUrl || (window.BIVER_SITE && window.BIVER_SITE.chatbotApi) || ''
    );
    state.config = boot.config || {};
    state.visitorUuid = getVisitorUuid();
    state.soundEnabled = boot.config?.soundEnabled !== false;

    setChatReady(false, 'Connecting…');
    bindEvents();

    const bootSession = () => {
      initSession().catch((err) => {
        state.initializing = false;
        console.error('Chat init error:', err.message);
        setChatReady(false, 'Chat unavailable — refresh the page');
        if (state.isOpen) {
          appendMessages([{
            id: Date.now(),
            senderType: 'system',
            message: err.message || 'Chat is temporarily unavailable. Run sql/install_chatbot.php if this is a new install.',
            createdAt: new Date().toISOString(),
            deliveryStatus: 'delivered',
          }]);
        }
      });
    };

    if (boot.autoInit !== false) {
      bootSession();
    } else {
      fetchCsrf().then(() => setChatReady(false, 'Open chat to connect')).catch(() => {});
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.BRHChat = { open: openChat, close: closeChat, send: sendMessage };
})();
