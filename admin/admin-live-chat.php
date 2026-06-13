<?php
/**
 * Live Chat, Support & Lead CRM — Biver Royalty Homes
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/chatbot/chatbot-config.php';

$activeNav = 'chatbot';
$pageTitle = 'Live Chat & Leads | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$apiUrl = siteUrl('chatbot/chatbot-api.php');
$csrfToken = AuthSecurity::generateCsrfToken();

$pageStylesheet = '../assets/css/admin-live-chat.css';


require_once dirname(__DIR__) . '/includes/admin_head.php';
?>
<body>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>
  <main class="main-content">
    <header class="topbar">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Menu"><ion-icon name="menu-outline"></ion-icon></button>
      <h1 class="page-title">Live Chat &amp; Leads</h1>
      <span class="admin-badge"><?= $adminName ?></span>
    </header>
    <div class="content-area live-content-area">
      <div class="live-toolbar">
        <p class="live-intro">Real-time visitor chat, human support, and lead CRM.</p>
        <button type="button" class="btn-gold" id="refreshBtn">Refresh</button>
      </div>

      <div class="live-stats" id="statsRow">
        <div class="live-stat"><span>Total Leads</span><strong id="sLeads">—</strong></div>
        <div class="live-stat"><span>Today's Leads</span><strong id="sToday">—</strong></div>
        <div class="live-stat"><span>Open Chats</span><strong id="sOpen">—</strong></div>
        <div class="live-stat"><span>Pending</span><strong id="sPending">—</strong></div>
        <div class="live-stat"><span>Closed</span><strong id="sClosed">—</strong></div>
        <div class="live-stat"><span>Waiting</span><strong id="sWaiting">—</strong></div>
      </div>

      <div class="live-grid live-grid-spaced">
        <div class="live-panel">
          <div class="live-panel-head">
            <strong>Open Chats</strong>
            <input type="search" id="convSearch" class="conv-search" placeholder="Search…">
          </div>
          <div class="conv-list" id="convList"><p class="live-placeholder">Loading…</p></div>
        </div>
        <div class="live-panel">
          <div class="live-panel-head"><strong>Conversation</strong></div>
          <div class="chat-msgs" id="chatMsgs"><p class="live-msg-hint">Select a conversation</p></div>
          <div class="chat-compose u-hidden" id="chatCompose">
            <textarea id="replyInput" rows="2" placeholder="Reply to visitor…"></textarea>
            <button type="button" class="btn-gold" id="sendReplyBtn">Send</button>
            <select id="agentSelect" class="assign-input"><option value="">Assign agent</option></select>
            <input type="text" id="assignLabel" class="assign-input" placeholder="Assigned to (e.g. Sarah)">
            <button type="button" class="btn-dark" id="assignLabelBtn">Assign</button>
            <button type="button" class="btn-dark" id="closeChatBtn">Close</button>
          </div>
        </div>
      </div>

      <div class="live-panel">
        <div class="live-panel-head"><strong>Lead Management (CRM)</strong></div>
        <div class="live-table-wrap">
          <table class="leads-table">
            <thead>
              <tr><th>Name</th><th>Phone</th><th>Email</th><th>Question</th><th>Stage</th><th>Assigned</th><th>Created</th></tr>
            </thead>
            <tbody id="leadsBody"><tr><td colspan="7" class="live-loading-cell">Loading leads…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<script>
(function () {
  const API = <?= json_encode($apiUrl, JSON_UNESCAPED_SLASHES) ?>;
  const CSRF = <?= json_encode($csrfToken) ?>;
  let selectedSessionId = null;
  const STAGES = ['New','Contacted','Interested','Inspection Scheduled','Negotiating','Closed Sale'];

  async function adminApi(action, body = {}) {
    const res = await fetch(`${API}?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Admin-CSRF': CSRF, 'X-Chatbot-CSRF': CSRF },
      credentials: 'same-origin',
      body: JSON.stringify({ ...body, csrf_token: CSRF }),
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Request failed');
    return data;
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  async function loadStats() {
    const { stats } = await adminApi('admin_stats');
    document.getElementById('sLeads').textContent = stats.total_leads ?? '—';
    document.getElementById('sToday').textContent = stats.today_leads ?? '—';
    document.getElementById('sOpen').textContent = stats.open_chats ?? '—';
    document.getElementById('sPending').textContent = stats.pending_chats ?? '—';
    document.getElementById('sClosed').textContent = stats.closed_chats ?? '—';
    document.getElementById('sWaiting').textContent = stats.waitingCustomers ?? '—';
  }

  async function loadAgents() {
    const data = await adminApi('admin_agents');
    const sel = document.getElementById('agentSelect');
    sel.innerHTML = '<option value="">Assign agent</option>';
    (data.agents || []).forEach(a => {
      const o = document.createElement('option');
      o.value = a.id;
      o.textContent = `${a.name} (${a.status})`;
      sel.appendChild(o);
    });
    sel.onchange = async () => {
      const id = parseInt(sel.value, 10);
      if (!selectedSessionId || !id) return;
      await adminApi('admin_assign', { session_id: selectedSessionId, agent_id: id });
      loadConversations();
    };
  }

  async function loadConversations(search = '') {
    const data = await adminApi('admin_conversations', { search, limit: 50 });
    const list = document.getElementById('convList');
    if (!data.conversations.length) {
      list.innerHTML = '<p class="live-placeholder">No conversations</p>';
      return;
    }
    list.innerHTML = data.conversations.map(c => `
      <div class="conv-item${selectedSessionId == c.id ? ' active' : ''}" data-id="${c.id}">
        <strong>${esc(c.visitor_name || c.visitor_phone || 'Visitor')}</strong><br>
        <small class="live-email-hint">${esc(c.visitor_email || '')}</small><br>
        <small>${esc((c.last_message || '').slice(0, 60))}</small>
        <span class="badge badge-${c.status === 'waiting' ? 'pending' : 'open'}">${esc(c.status)}</span>
      </div>`).join('');
    list.querySelectorAll('.conv-item').forEach(el => {
      el.addEventListener('click', () => openConversation(parseInt(el.dataset.id, 10)));
    });
  }

  async function openConversation(sessionId) {
    selectedSessionId = sessionId;
    document.getElementById('chatCompose').classList.remove('u-hidden');
    const data = await adminApi('admin_messages', { session_id: sessionId });
    const box = document.getElementById('chatMsgs');
    box.innerHTML = data.messages.map(m => `
      <div class="chat-msg ${esc(m.senderType)}">${esc(m.message)}
        <div class="live-msg-time">${esc(m.createdAt)}</div>
      </div>`).join('');
    box.scrollTop = box.scrollHeight;
    loadConversations(document.getElementById('convSearch').value);
  }

  async function loadLeads() {
    try {
      const data = await adminApi('admin_leads', { limit: 80 });
      const tbody = document.getElementById('leadsBody');
      if (!data.leads.length) {
        tbody.innerHTML = '<tr><td colspan="7">No leads yet. Run sql/install_support_platform.php</td></tr>';
        return;
      }
      tbody.innerHTML = data.leads.map(l => `
        <tr>
          <td>${esc(l.visitor_name)}</td>
          <td>${esc(l.visitor_phone)}</td>
          <td>${esc(l.visitor_email || '—')}</td>
          <td>${esc((l.question || '').slice(0, 80))}</td>
          <td><select data-lead="${l.id}" class="assign-input stage-select">${STAGES.map(s =>
            `<option value="${s}"${l.stage === s ? ' selected' : ''}>${s}</option>`).join('')}</select></td>
          <td>${esc(l.assigned_to || '—')}</td>
          <td>${esc(l.created_at)}</td>
        </tr>`).join('');
      tbody.querySelectorAll('.stage-select').forEach(sel => {
        sel.addEventListener('change', async () => {
          await adminApi('admin_update_lead', { lead_id: parseInt(sel.dataset.lead, 10), stage: sel.value });
        });
      });
    } catch (e) {
      document.getElementById('leadsBody').innerHTML = `<tr><td colspan="7">${esc(e.message)}</td></tr>`;
    }
  }

  async function refreshAll() {
    await Promise.all([loadStats(), loadAgents(), loadConversations(document.getElementById('convSearch').value), loadLeads()]);
    if (selectedSessionId) openConversation(selectedSessionId);
  }

  document.getElementById('refreshBtn').addEventListener('click', refreshAll);
  document.getElementById('sendReplyBtn').addEventListener('click', async () => {
    const msg = document.getElementById('replyInput').value.trim();
    if (!selectedSessionId || !msg) return;
    await adminApi('admin_reply', { session_id: selectedSessionId, message: msg });
    document.getElementById('replyInput').value = '';
    openConversation(selectedSessionId);
  });
  document.getElementById('closeChatBtn').addEventListener('click', async () => {
    if (!selectedSessionId || !confirm('Close this chat?')) return;
    await adminApi('admin_close', { session_id: selectedSessionId });
    selectedSessionId = null;
    document.getElementById('chatCompose').style.display = 'none';
    refreshAll();
  });
  document.getElementById('assignLabelBtn').addEventListener('click', async () => {
    const label = document.getElementById('assignLabel').value.trim();
    if (!selectedSessionId || !label) return;
    await adminApi('admin_assign_label', { session_id: selectedSessionId, assigned_to: label });
    loadLeads();
  });
  document.getElementById('convSearch').addEventListener('input', e => {
    clearTimeout(e.target._t);
    e.target._t = setTimeout(() => loadConversations(e.target.value), 400);
  });
  document.getElementById('replyInput').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.getElementById('sendReplyBtn').click(); }
  });

  refreshAll();
  setInterval(refreshAll, 5000);
})();
</script>
</body>
</html>
