<?php
/**
 * Biver Royalty Homes — Chat Widget (include on public pages)
 */
declare(strict_types=1);

require_once __DIR__ . '/chatbot-config.php';

$chatConfig = chatbotPublicConfig();
$cssUrl = siteUrl('chatbot/chatbot.css');
$jsUrl = siteUrl('chatbot/chatbot.js');
$agentAvatar = chatbotEscape($chatConfig['agentAvatar'] ?? '');
$agentName = chatbotEscape($chatConfig['agentName'] ?? 'Biver Royalty Homes Assistant');
$agentSubtitle = chatbotEscape($chatConfig['agentSubtitle'] ?? 'Online Now');
?>
<link rel="stylesheet" href="<?= chatbotEscape($cssUrl) ?>">
<div id="brh-chat-root" aria-live="polite">
  <div id="brh-welcome-stack" class="brh-welcome-stack brh-pointer"></div>

  <div id="brh-chat-window" class="brh-chat-window" role="dialog" aria-label="Property chat assistant">
    <header class="brh-chat-header">
      <img id="brh-agent-avatar" class="brh-chat-header-avatar" src="<?= $agentAvatar ?>" alt="" width="44" height="44">
      <div class="brh-chat-header-info">
        <h2 class="brh-chat-header-title"><?= $agentName ?></h2>
        <div class="brh-chat-header-sub">
          <span class="brh-online-dot" aria-hidden="true"></span>
          <span><?= $agentSubtitle ?></span>
        </div>
      </div>
      <div class="brh-chat-header-actions">
        <button type="button" id="brh-chat-minimize" class="brh-chat-icon-btn brh-pointer" aria-label="Minimize chat">
          <svg viewBox="0 0 24 24"><path d="M6 19h12v2H6z"/></svg>
        </button>
        <button type="button" id="brh-chat-close" class="brh-chat-icon-btn brh-pointer" aria-label="Close chat">
          <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
      </div>
    </header>

    <div id="brh-chat-messages" class="brh-chat-messages" role="log" aria-relevant="additions"></div>

    <div id="brh-chat-typing" class="brh-typing" aria-hidden="true">
      <img class="brh-msg-avatar" src="<?= $agentAvatar ?>" alt="" width="32" height="32">
      <div class="brh-typing-dots"><span></span><span></span><span></span></div>
    </div>

    <footer class="brh-chat-footer">
      <div id="brh-search-panel" class="brh-search-panel">
        <input type="search" id="brh-search-input" class="brh-search-input" placeholder="Search conversation…" aria-label="Search conversation">
      </div>
      <div id="brh-emoji-panel" class="brh-emoji-panel" role="listbox" aria-label="Emoji picker"></div>
      <div class="brh-chat-toolbar">
        <button type="button" id="brh-emoji-toggle" class="brh-toolbar-btn brh-pointer" aria-label="Emoji picker">😊</button>
        <button type="button" id="brh-search-toggle" class="brh-toolbar-btn brh-pointer" aria-label="Search conversation">🔍</button>
        <button type="button" id="brh-inspection-toggle" class="brh-toolbar-btn brh-pointer" aria-label="Book inspection">📅</button>
        <button type="button" id="brh-export-toggle" class="brh-toolbar-btn brh-pointer" aria-label="Export conversation">📥</button>
        <button type="button" id="brh-sound-toggle" class="brh-toolbar-btn brh-pointer" aria-label="Toggle sound">🔔</button>
      </div>
      <div class="brh-chat-input-wrap">
        <textarea id="brh-chat-input" class="brh-chat-input brh-pointer" rows="1" placeholder="Type your message…" aria-label="Message"></textarea>
        <button type="button" id="brh-chat-send" class="brh-chat-send brh-pointer" aria-label="Send message">
          <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </div>
    </footer>
  </div>

  <button type="button" id="brh-chat-launcher" class="brh-chat-launcher brh-pointer" aria-label="Open chat assistant">
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
      <circle cx="8" cy="10" r="1.2"/>
      <circle cx="12" cy="10" r="1.2"/>
      <circle cx="16" cy="10" r="1.2"/>
    </svg>
    <span id="brh-chat-badge" class="brh-chat-badge" aria-live="polite">0</span>
  </button>
</div>

<div id="brh-support-modal" class="brh-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="brh-support-title">
  <div class="brh-modal">
    <h3 id="brh-support-title">Request Human Response</h3>
    <p class="brh-modal-hint">A consultant will reply here in the chat — no redirects.</p>
    <form id="brh-support-form">
      <div class="brh-form-group">
        <label for="brh-support-name">Full Name *</label>
        <input type="text" id="brh-support-name" name="name" required maxlength="120" autocomplete="name">
      </div>
      <div class="brh-form-group">
        <label for="brh-support-phone">Phone Number *</label>
        <input type="tel" id="brh-support-phone" name="phone" required maxlength="40" autocomplete="tel">
      </div>
      <div class="brh-form-group">
        <label for="brh-support-email">Email Address</label>
        <input type="email" id="brh-support-email" name="email" maxlength="190" autocomplete="email">
      </div>
      <div class="brh-form-group">
        <label for="brh-support-question">Question or Message *</label>
        <textarea id="brh-support-question" name="question" rows="4" required maxlength="2000"></textarea>
      </div>
      <div class="brh-form-actions">
        <button type="button" id="brh-support-cancel" class="brh-btn-secondary brh-pointer">Cancel</button>
        <button type="submit" class="brh-btn-primary brh-pointer">Submit Request</button>
      </div>
    </form>
  </div>
</div>

<div id="brh-inspection-modal" class="brh-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="brh-inspection-title">
  <div class="brh-modal">
    <h3 id="brh-inspection-title">Book a Property Inspection</h3>
    <form id="brh-inspection-form">
      <div class="brh-form-group">
        <label for="brh-insp-property">Property / Location</label>
        <input type="text" id="brh-insp-property" name="property" placeholder="e.g. Luxury duplex in Owerri">
      </div>
      <div class="brh-form-group">
        <label for="brh-insp-date">Preferred Date</label>
        <input type="date" id="brh-insp-date" name="preferred_date">
      </div>
      <div class="brh-form-group">
        <label for="brh-insp-name">Full Name *</label>
        <input type="text" id="brh-insp-name" name="name" required maxlength="120">
      </div>
      <div class="brh-form-group">
        <label for="brh-insp-phone">Phone Number *</label>
        <input type="tel" id="brh-insp-phone" name="phone" required maxlength="40">
      </div>
      <div class="brh-form-group">
        <label for="brh-insp-email">Email</label>
        <input type="email" id="brh-insp-email" name="email" maxlength="190">
      </div>
      <div class="brh-form-group">
        <label for="brh-insp-notes">Additional Notes</label>
        <textarea id="brh-insp-notes" name="notes" rows="3" maxlength="500"></textarea>
      </div>
      <div class="brh-form-actions">
        <button type="button" id="brh-inspection-cancel" class="brh-btn-secondary brh-pointer">Cancel</button>
        <button type="submit" class="brh-btn-primary brh-pointer">Submit Request</button>
      </div>
    </form>
  </div>
</div>

<script>
window.BRH_CHAT_BOOT = {
  apiUrl: <?= json_encode($chatConfig['apiUrl'], JSON_UNESCAPED_SLASHES) ?>,
  config: <?= json_encode($chatConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  autoInit: true
};
</script>
<script src="<?= chatbotEscape($jsUrl) ?>" defer></script>
