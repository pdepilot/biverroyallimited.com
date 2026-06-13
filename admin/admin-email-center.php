<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';

$activeNav = 'email';
$pageTitle = 'Email Center | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-email-center.css';
$csrfToken = AuthSecurity::generateCsrfToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
</head>
<body>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu">
        <ion-icon name="menu-outline"></ion-icon>
      </button>
      <h1 class="page-title">Email Center</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="stats-row" id="logStatsRow">
        <div class="stat-pill"><strong id="statSent">—</strong><span>Sent</span></div>
        <div class="stat-pill"><strong id="statFailed">—</strong><span>Failed</span></div>
        <div class="stat-pill"><strong id="statQueued">—</strong><span>Queued</span></div>
        <div class="stat-pill"><strong id="statTotal">—</strong><span>Total Logs</span></div>
      </div>

      <div class="email-tabs" role="tablist">
        <button type="button" class="email-tab active" data-tab="compose"><ion-icon name="create-outline"></ion-icon> Compose</button>
        <button type="button" class="email-tab" data-tab="templates"><ion-icon name="documents-outline"></ion-icon> Templates</button>
        <button type="button" class="email-tab" data-tab="logs"><ion-icon name="time-outline"></ion-icon> Email Logs</button>
      </div>

      <!-- Compose -->
      <div class="email-tab-panel active" id="panel-compose">
        <div class="email-layout">
          <div class="admin-panel">
            <form id="composeForm" class="email-compose-grid">
              <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

              <div class="form-field">
                <label for="recipientType">Recipient Type</label>
                <select id="recipientType" required>
                  <option value="owners">Property Owners</option>
                  <option value="subscribers">Newsletter Subscribers</option>
                  <option value="single">Single Email Address</option>
                  <option value="multiple">Multiple Email Addresses</option>
                </select>
              </div>

              <div class="form-field u-hidden" id="singleEmailField">
                <label for="singleEmail">Email Address</label>
                <input type="email" id="singleEmail" placeholder="example@gmail.com">
              </div>

              <div class="form-field u-hidden" id="multipleEmailField">
                <label for="multipleEmails">Email Addresses (one per line or comma-separated)</label>
                <textarea id="multipleEmails" rows="4" placeholder="user1@gmail.com&#10;user2@gmail.com"></textarea>
              </div>

              <div class="recipient-panel u-hidden" id="ownersPanel">
                <label><input type="checkbox" id="ownersAll" checked> Send to all property owners</label>
                <div class="recipient-list u-hidden" id="ownersList"></div>
              </div>

              <div class="recipient-panel u-hidden" id="subscribersPanel">
                <label><input type="checkbox" id="subscribersAll" checked> Send to all active subscribers</label>
                <div class="recipient-list u-hidden" id="subscribersList"></div>
              </div>

              <div class="form-field">
                <label for="templateSelect">Use Template (optional)</label>
                <select id="templateSelect">
                  <option value="">— Start from scratch —</option>
                </select>
              </div>

              <div class="form-field">
                <label for="emailSubject">Subject</label>
                <input type="text" id="emailSubject" required placeholder="Email subject">
              </div>

              <div class="form-field">
                <label>Message</label>
                <div id="editorContainer"></div>
              </div>

              <div class="email-actions">
                <button type="button" class="admin-btn-primary" id="sendBtn"><ion-icon name="send-outline"></ion-icon> Send Email</button>
                <button type="button" class="admin-btn-outline" id="saveDraftBtn"><ion-icon name="save-outline"></ion-icon> Save Draft</button>
                <button type="button" class="admin-btn-outline" id="previewBtn"><ion-icon name="eye-outline"></ion-icon> Update Preview</button>
              </div>

              <div class="queue-progress" id="queueProgress">
                <strong>Sending batch…</strong>
                <div class="progress-bar"><div class="progress-bar-fill" id="progressFill"></div></div>
                <p id="queueStatusText">Processing…</p>
              </div>
            </form>
          </div>

          <div class="email-preview" id="emailPreview">
            <h4>Email Preview</h4>
            <div class="preview-subject" id="previewSubject">Subject will appear here</div>
            <div class="preview-body" id="previewBody"><p class="admin-text-muted">Compose a message to see preview.</p></div>
          </div>
        </div>
      </div>

      <!-- Templates -->
      <div class="email-tab-panel" id="panel-templates">
        <div class="toolbar">
          <button type="button" class="admin-btn-primary" id="newTemplateBtn"><ion-icon name="add-outline"></ion-icon> Create Template</button>
          <button type="button" class="admin-btn-outline" id="refreshTemplatesBtn"><ion-icon name="refresh-outline"></ion-icon> Refresh</button>
        </div>
        <div class="templates-grid" id="templatesGrid">
          <div class="loader">Loading templates…</div>
        </div>
      </div>

      <!-- Logs -->
      <div class="email-tab-panel" id="panel-logs">
        <div class="toolbar">
          <input type="search" id="logSearchInput" placeholder="Search recipient, subject, type…">
          <select id="logStatusFilter">
            <option value="">All statuses</option>
            <option value="sent">Sent</option>
            <option value="failed">Failed</option>
            <option value="queued">Queued</option>
          </select>
          <select id="logTypeFilter">
            <option value="">All email types</option>
          </select>
          <button type="button" class="admin-btn-outline" id="refreshLogsBtn"><ion-icon name="refresh-outline"></ion-icon> Refresh</button>
        </div>
        <div class="logs-table-wrap">
          <table class="logs-table">
            <thead>
              <tr><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Date</th><th></th></tr>
            </thead>
            <tbody id="logsBody">
              <tr><td colspan="6">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal template-form-modal" id="templateModal">
  <div class="modal-box">
    <span class="modal-close" id="closeTemplateModal">&times;</span>
    <h3 id="templateModalTitle">Create Template</h3>
    <input type="hidden" id="templateEditId">
    <div class="form-field">
      <label for="tplName">Template Name</label>
      <input type="text" id="tplName" required>
    </div>
    <div class="form-field">
      <label for="tplEvent">Assign to Event (optional)</label>
      <select id="tplEvent">
        <option value="">— Manual / custom template —</option>
      </select>
    </div>
    <div class="form-field">
      <label for="tplSubject">Subject</label>
      <input type="text" id="tplSubject" required>
    </div>
    <div class="form-field">
      <label for="tplBody">Body HTML</label>
      <textarea id="tplBody" rows="8" required></textarea>
    </div>
    <div class="modal-actions">
      <button type="button" class="admin-btn-primary" id="saveTemplateBtn">Save Template</button>
      <button type="button" class="admin-btn-outline" id="previewTemplateBtn">Preview</button>
      <button type="button" class="admin-btn-outline" id="cancelTemplateBtn">Cancel</button>
    </div>
    <div class="email-preview u-hidden" id="tplPreviewBox" style="margin-top:16px;"></div>
  </div>
</div>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function() {
  const API = 'api/email-center.php';
  const csrf = document.getElementById('csrfToken').value;
  let templates = [];
  let emailEvents = {};
  let quill = null;
  let queueTimer = null;

  async function api(method, url, body) {
    const opts = { method, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify({ ...body, csrf_token: csrf });
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed');
    return data;
  }

  function toast(msg, isError) {
    document.querySelector('.admin-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'admin-toast' + (isError ? ' error' : '');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

  function initEditor() {
    quill = new Quill('#editorContainer', {
      theme: 'snow',
      placeholder: 'Write your email message… Use {{name}} for personalization.',
      modules: { toolbar: [['bold','italic','underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] }
    });
    quill.on('text-change', updatePreview);
  }

  function getBodyHtml() {
    return quill ? quill.root.innerHTML : '';
  }

  function setBodyHtml(html) {
    if (quill) quill.root.innerHTML = html || '';
    updatePreview();
  }

  function updatePreview() {
    document.getElementById('previewSubject').textContent = document.getElementById('emailSubject').value || 'Subject will appear here';
    document.getElementById('previewBody').innerHTML = getBodyHtml() || '<p class="admin-text-muted">Compose a message to see preview.</p>';
  }

  function toggleRecipientPanels() {
    const type = document.getElementById('recipientType').value;
    document.getElementById('singleEmailField').classList.toggle('u-hidden', type !== 'single');
    document.getElementById('multipleEmailField').classList.toggle('u-hidden', type !== 'multiple');
    document.getElementById('ownersPanel').classList.toggle('u-hidden', type !== 'owners');
    document.getElementById('subscribersPanel').classList.toggle('u-hidden', type !== 'subscribers');
  }

  function buildSendPayload() {
    const type = document.getElementById('recipientType').value;
    const payload = {
      action: 'send',
      recipient_type: type,
      subject: document.getElementById('emailSubject').value.trim(),
      body_html: getBodyHtml()
    };

    if (type === 'single') {
      payload.email = document.getElementById('singleEmail').value.trim();
    } else if (type === 'multiple') {
      payload.emails = document.getElementById('multipleEmails').value.trim();
    } else if (type === 'owners') {
      payload.send_all = document.getElementById('ownersAll').checked;
      if (!payload.send_all) {
        payload.owner_ids = [...document.querySelectorAll('#ownersList input:checked')].map(cb => parseInt(cb.value, 10));
      }
    } else if (type === 'subscribers') {
      payload.send_all = document.getElementById('subscribersAll').checked;
      if (!payload.send_all) {
        payload.subscriber_ids = [...document.querySelectorAll('#subscribersList input:checked')].map(cb => parseInt(cb.value, 10));
      }
    }
    return payload;
  }

  async function loadRecipients() {
    try {
      const [owners, subs] = await Promise.all([
        api('GET', API + '?view=recipients&type=owners'),
        api('GET', API + '?view=recipients&type=subscribers')
      ]);
      renderRecipientList('ownersList', owners.owners || [], 'owner');
      renderRecipientList('subscribersList', subs.subscribers || [], 'subscriber');
    } catch (e) { /* non-fatal */ }
  }

  function renderRecipientList(containerId, items, prefix) {
    const el = document.getElementById(containerId);
    if (!items.length) {
      el.innerHTML = '<p class="admin-text-muted">No recipients found.</p>';
      return;
    }
    el.innerHTML = items.map(r => `
      <label class="recipient-item">
        <input type="checkbox" value="${r.id}" checked>
        <span>${esc(r.name || r.email)} <span>&lt;${esc(r.email)}&gt;</span></span>
      </label>`).join('');
  }

  function bindRecipientToggles() {
    document.getElementById('ownersAll').addEventListener('change', e => {
      document.getElementById('ownersList').classList.toggle('u-hidden', e.target.checked);
    });
    document.getElementById('subscribersAll').addEventListener('change', e => {
      document.getElementById('subscribersList').classList.toggle('u-hidden', e.target.checked);
    });
  }

  function renderEventSelects() {
    const opts = Object.entries(emailEvents).map(([k, v]) => `<option value="${k}">${esc(v)}</option>`).join('');
    document.getElementById('tplEvent').innerHTML = '<option value="">— Manual / custom template —</option>' + opts;
    document.getElementById('logTypeFilter').innerHTML = '<option value="">All email types</option>' + opts;
  }

  async function loadMeta() {
    const data = await api('GET', API);
    templates = data.templates || [];
    emailEvents = data.email_events || {};
    renderEventSelects();
    renderTemplateSelect();
    if (data.log_stats) {
      document.getElementById('statSent').textContent = data.log_stats.sent ?? 0;
      document.getElementById('statFailed').textContent = data.log_stats.failed ?? 0;
      document.getElementById('statQueued').textContent = data.log_stats.queued ?? 0;
      document.getElementById('statTotal').textContent = data.log_stats.total ?? 0;
    }
    const draft = await api('GET', API + '?view=draft').catch(() => ({ draft: null }));
    if (draft.draft) {
      document.getElementById('recipientType').value = draft.draft.recipient_type || 'single';
      document.getElementById('emailSubject').value = draft.draft.subject || '';
      setBodyHtml(draft.draft.body_html || '');
      toggleRecipientPanels();
    }
  }

  function renderTemplateSelect() {
    const sel = document.getElementById('templateSelect');
    sel.innerHTML = '<option value="">— Start from scratch —</option>' +
      templates.map(t => `<option value="${t.id}">${esc(t.name)}</option>`).join('');
  }

  async function loadTemplates() {
    const grid = document.getElementById('templatesGrid');
    grid.innerHTML = '<div class="loader">Loading templates…</div>';
    try {
      const data = await api('GET', API + '?view=templates');
      templates = data.templates || [];
      renderTemplateSelect();
      if (!templates.length) {
        grid.innerHTML = '<div class="empty-state admin-empty-wide">No templates yet. Create one to speed up composing.</div>';
        return;
      }
      grid.innerHTML = templates.map(t => `
        <article class="template-card">
          <h4>${esc(t.name)}</h4>
          <div class="tpl-subject">${esc(t.subject)}</div>
          ${t.event_key ? `<div class="tpl-subject"><strong>Event:</strong> ${esc(emailEvents[t.event_key] || t.event_key)}</div>` : ''}
          <div class="tpl-actions">
            <button type="button" class="admin-btn-primary use-tpl" data-id="${t.id}">Use</button>
            <button type="button" class="admin-btn-outline edit-tpl" data-id="${t.id}">Edit</button>
            <button type="button" class="admin-btn-outline preview-tpl" data-id="${t.id}">Preview</button>
            <button type="button" class="admin-btn-outline dup-tpl" data-id="${t.id}">Duplicate</button>
            <button type="button" class="admin-btn-outline danger del-tpl" data-id="${t.id}">Delete</button>
          </div>
        </article>`).join('');

      grid.querySelectorAll('.use-tpl').forEach(btn => btn.addEventListener('click', () => applyTemplate(parseInt(btn.dataset.id, 10))));
      grid.querySelectorAll('.edit-tpl').forEach(btn => btn.addEventListener('click', () => openTemplateModal(parseInt(btn.dataset.id, 10))));
      grid.querySelectorAll('.preview-tpl').forEach(btn => btn.addEventListener('click', () => previewTemplateById(parseInt(btn.dataset.id, 10))));
      grid.querySelectorAll('.dup-tpl').forEach(btn => btn.addEventListener('click', async () => {
        await api('POST', API, { action: 'duplicate_template', id: parseInt(btn.dataset.id, 10) });
        toast('Template duplicated');
        loadTemplates();
      }));
      grid.querySelectorAll('.del-tpl').forEach(btn => btn.addEventListener('click', async () => {
        if (!confirm('Delete this template?')) return;
        await api('POST', API, { action: 'delete_template', id: parseInt(btn.dataset.id, 10) });
        toast('Template deleted');
        loadTemplates();
      }));
    } catch (e) {
      grid.innerHTML = '<div class="empty-state">' + esc(e.message) + '</div>';
    }
  }

  function applyTemplate(id) {
    const t = templates.find(x => parseInt(x.id, 10) === id);
    if (!t) return;
    document.querySelector('.email-tab[data-tab="compose"]').click();
    document.getElementById('templateSelect').value = String(id);
    document.getElementById('emailSubject').value = t.subject;
    setBodyHtml(t.body_html);
    toast('Template applied');
  }

  function openTemplateModal(id) {
    const isEdit = id > 0;
    const t = isEdit ? templates.find(x => parseInt(x.id, 10) === id) : null;
    document.getElementById('templateModalTitle').textContent = isEdit ? 'Edit Template' : 'Create Template';
    document.getElementById('templateEditId').value = isEdit ? String(id) : '';
    document.getElementById('tplName').value = t ? t.name : '';
    document.getElementById('tplSubject').value = t ? t.subject : '';
    document.getElementById('tplBody').value = t ? t.body_html : '';
    document.getElementById('tplEvent').value = t ? (t.event_key || '') : '';
    document.getElementById('tplPreviewBox').classList.add('u-hidden');
    document.getElementById('templateModal').classList.add('open');
  }

  async function previewTemplateById(id) {
    const data = await api('GET', API + '?view=preview_template&id=' + id);
    document.querySelector('.email-tab[data-tab="compose"]').click();
    document.getElementById('previewSubject').textContent = data.subject;
    document.getElementById('previewBody').innerHTML = data.preview_html;
    toast('Template preview loaded');
  }

  async function loadLogs() {
    const status = document.getElementById('logStatusFilter').value;
    const search = document.getElementById('logSearchInput').value.trim();
    const emailType = document.getElementById('logTypeFilter').value;
    let url = API + '?view=logs';
    if (status) url += '&status=' + encodeURIComponent(status);
    if (search) url += '&search=' + encodeURIComponent(search);
    if (emailType) url += '&email_type=' + encodeURIComponent(emailType);
    const tbody = document.getElementById('logsBody');
    try {
      const data = await api('GET', url);
      const logs = data.logs || [];
      if (data.stats) {
        document.getElementById('statSent').textContent = data.stats.sent ?? 0;
        document.getElementById('statFailed').textContent = data.stats.failed ?? 0;
        document.getElementById('statQueued').textContent = data.stats.queued ?? 0;
        document.getElementById('statTotal').textContent = data.stats.total ?? 0;
      }
      if (!logs.length) {
        tbody.innerHTML = '<tr><td colspan="6">No email logs yet.</td></tr>';
        return;
      }
      tbody.innerHTML = logs.map(l => `
        <tr>
          <td>${esc(l.recipient_name || '')} ${esc(l.recipient)}</td>
          <td>${esc(l.subject)}</td>
          <td>${esc(l.email_type || '—')}</td>
          <td><span class="status-badge status-${esc(l.status)}">${esc(l.status)}</span></td>
          <td>${formatDate(l.sent_at || l.created_at)}</td>
          <td>${l.status === 'failed' ? `<button type="button" class="admin-btn-outline resend-log" data-id="${l.id}">Resend</button>` : ''}</td>
        </tr>`).join('');

      tbody.querySelectorAll('.resend-log').forEach(btn => btn.addEventListener('click', async () => {
        try {
          await api('POST', API, { action: 'resend_log', id: parseInt(btn.dataset.id, 10) });
          toast('Email resent');
          loadLogs();
        } catch (e) { toast(e.message, true); }
      }));
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="6">' + esc(e.message) + '</td></tr>';
    }
  }

  async function processQueue(batchId) {
    const panel = document.getElementById('queueProgress');
    const fill = document.getElementById('progressFill');
    const statusText = document.getElementById('queueStatusText');
    panel.classList.add('visible');

    const tick = async () => {
      const result = await api('POST', API, { action: 'process_queue', batch_id: batchId, batch_size: 8 });
      const q = result.queue || {};
      const done = (q.sent || 0) + (q.failed || 0);
      const total = q.total || 1;
      const pct = Math.round((done / total) * 100);
      fill.style.width = pct + '%';
      statusText.textContent = `Sent ${q.sent || 0}, failed ${q.failed || 0}, remaining ${q.pending || 0}`;

      if (result.complete) {
        clearInterval(queueTimer);
        toast(`Batch complete. Sent ${q.sent || 0}, failed ${q.failed || 0}.`, (q.failed || 0) > 0);
        panel.classList.remove('visible');
        loadLogs();
        return;
      }
    };

    await tick();
    queueTimer = setInterval(tick, 1500);
  }

  document.querySelectorAll('.email-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.email-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.email-tab-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
      if (tab.dataset.tab === 'templates') loadTemplates();
      if (tab.dataset.tab === 'logs') loadLogs();
    });
  });

  document.getElementById('recipientType').addEventListener('change', toggleRecipientPanels);
  document.getElementById('emailSubject').addEventListener('input', updatePreview);
  document.getElementById('previewBtn').addEventListener('click', updatePreview);

  document.getElementById('templateSelect').addEventListener('change', e => {
    const id = parseInt(e.target.value, 10);
    if (id > 0) applyTemplate(id);
  });

  document.getElementById('sendBtn').addEventListener('click', async () => {
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    try {
      const result = await api('POST', API, buildSendPayload());
      if (result.mode === 'queued' && result.batch_id) {
        toast(result.message);
        await processQueue(result.batch_id);
      } else {
        toast(result.message, (result.failed || 0) > 0);
        loadLogs();
      }
    } catch (e) {
      toast(e.message, true);
    } finally {
      btn.disabled = false;
    }
  });

  document.getElementById('saveDraftBtn').addEventListener('click', async () => {
    try {
      const p = buildSendPayload();
      p.action = 'save_draft';
      p.recipients = {};
      await api('POST', API, p);
      toast('Draft saved');
    } catch (e) {
      toast(e.message, true);
    }
  });

  document.getElementById('newTemplateBtn').addEventListener('click', () => openTemplateModal(0));
  document.getElementById('refreshTemplatesBtn').addEventListener('click', loadTemplates);
  document.getElementById('refreshLogsBtn').addEventListener('click', loadLogs);
  document.getElementById('logStatusFilter').addEventListener('change', loadLogs);
  document.getElementById('logTypeFilter').addEventListener('change', loadLogs);
  let logSearchTimer;
  document.getElementById('logSearchInput').addEventListener('input', () => {
    clearTimeout(logSearchTimer);
    logSearchTimer = setTimeout(loadLogs, 350);
  });

  document.getElementById('saveTemplateBtn').addEventListener('click', async () => {
    const id = parseInt(document.getElementById('templateEditId').value, 10);
    const payload = {
      action: id > 0 ? 'update_template' : 'create_template',
      name: document.getElementById('tplName').value.trim(),
      subject: document.getElementById('tplSubject').value.trim(),
      body_html: document.getElementById('tplBody').value.trim(),
      event_key: document.getElementById('tplEvent').value
    };
    if (id > 0) payload.id = id;
    try {
      await api('POST', API, payload);
      document.getElementById('templateModal').classList.remove('open');
      toast('Template saved');
      loadTemplates();
    } catch (e) {
      toast(e.message, true);
    }
  });

  document.getElementById('previewTemplateBtn').addEventListener('click', async () => {
    const inner = document.getElementById('tplBody').value;
    const box = document.getElementById('tplPreviewBox');
    box.innerHTML = '<h4>Preview</h4><div class="preview-body">' + inner.replace(/\{\{customer_name\}\}/g, 'Sample Customer') + '</div>';
    box.classList.remove('u-hidden');
  });

  document.getElementById('closeTemplateModal').addEventListener('click', () => document.getElementById('templateModal').classList.remove('open'));
  document.getElementById('cancelTemplateBtn').addEventListener('click', () => document.getElementById('templateModal').classList.remove('open'));
  document.getElementById('templateModal').addEventListener('click', e => {
    if (e.target.id === 'templateModal') document.getElementById('templateModal').classList.remove('open');
  });

  initEditor();
  toggleRecipientPanels();
  bindRecipientToggles();
  loadMeta();
  loadRecipients();
})();
</script>
</body>
</html>
