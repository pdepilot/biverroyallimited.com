<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';

$activeNav = 'contacts';
$pageTitle = 'Inquiries | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-contact.css';

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
      <h1 class="page-title">Inquiries</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="stats-row" id="statsRow">
        <div class="stat-pill"><strong id="statTotal">—</strong><span>Total</span></div>
        <div class="stat-pill new"><strong id="statNew">—</strong><span>New</span></div>
        <div class="stat-pill"><strong id="statRead">—</strong><span>Read</span></div>
        <div class="stat-pill"><strong id="statReplied">—</strong><span>Replied</span></div>
      </div>

      <div class="admin-panel compose-panel">
        <details>
          <summary><ion-icon name="mail-outline"></ion-icon> Compose New Email</summary>
          <form class="compose-form" id="composeForm">
            <input type="text" name="name" placeholder="Recipient name" required>
            <input type="email" name="email" placeholder="Recipient email" required>
            <input type="text" name="subject" placeholder="Subject" required>
            <textarea name="message" placeholder="Your message..." required></textarea>
            <button type="submit" class="admin-btn-primary"><ion-icon name="send-outline"></ion-icon> Send Email</button>
          </form>
        </details>
      </div>

      <div class="admin-panel">
        <div class="toolbar">
          <input type="search" id="searchInput" placeholder="Search name, email, message...">
          <select id="statusFilter">
            <option value="">All statuses</option>
            <option value="new">New</option>
            <option value="read">Read</option>
            <option value="replied">Replied</option>
            <option value="archived">Archived</option>
          </select>
          <button type="button" class="admin-btn-outline" id="refreshBtn"><ion-icon name="refresh-outline"></ion-icon> Refresh</button>
          <button type="button" class="admin-btn-outline" id="deleteReadBtn"><ion-icon name="trash-outline"></ion-icon> Delete Read</button>
        </div>
        <div id="contactsContainer" class="contacts-grid">
          <div class="loader"><ion-icon name="hourglass-outline"></ion-icon> Loading inquiries...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="messageModal" role="dialog" aria-modal="true">
  <div class="modal-box">
    <span class="modal-close" id="closeModal">&times;</span>
    <h3>Inquiry Details</h3>
    <div id="modalDetail"></div>
    <div class="reply-form">
      <h4 class="admin-reply-heading">Reply via Email</h4>
      <input type="hidden" id="replyInquiryId">
      <label for="replySubject">Subject</label>
      <input type="text" id="replySubject" required>
      <label for="replyMessage">Message</label>
      <textarea id="replyMessage" required placeholder="Type your reply to the customer..."></textarea>
      <div class="modal-actions">
        <button type="button" class="admin-btn-primary" id="sendReplyBtn"><ion-icon name="send-outline"></ion-icon> Send Reply</button>
        <button type="button" class="admin-btn-outline" id="archiveBtn">Archive</button>
        <button type="button" class="admin-btn-danger" id="deleteInquiryBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const API = 'api/contacts.php';
  let contacts = [];
  let currentInquiry = null;


  async function api(method, url, body) {
    const opts = { method, credentials: 'same-origin', headers: {} };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
      throw new Error(data.message || 'Request failed');
    }
    return data;
  }

  function toast(msg, isError = false) {
    document.querySelector('.admin-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'admin-toast' + (isError ? ' error' : '');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  function escapeHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function statusBadge(status) {
    const labels = { new: 'New', read: 'Read', replied: 'Replied', archived: 'Archived' };
    return `<span class="badge badge-${status}">${labels[status] || status}</span>`;
  }

  function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

  async function loadContacts() {
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    let url = API + '?';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    if (search) url += 'search=' + encodeURIComponent(search);

    const container = document.getElementById('contactsContainer');
    container.innerHTML = '<div class="loader">Loading...</div>';

    try {
      const data = await api('GET', url);
      contacts = data.contacts || [];
      if (data.stats) {
        document.getElementById('statTotal').textContent = data.stats.total ?? 0;
        document.getElementById('statNew').textContent = data.stats.new_count ?? 0;
        document.getElementById('statRead').textContent = data.stats.read_count ?? 0;
        document.getElementById('statReplied').textContent = data.stats.replied_count ?? 0;
      }
      renderContacts();
    } catch (e) {
      container.innerHTML = '<div class="empty-state">Could not load inquiries. ' + escapeHtml(e.message) + '</div>';
      toast(e.message, true);
    }
  }

  function renderContacts() {
    const container = document.getElementById('contactsContainer');
    if (!contacts.length) {
      container.innerHTML = '<div class="empty-state"><ion-icon name="mail-open-outline" class="admin-empty-icon"></ion-icon><p>No inquiries yet.</p></div>';
      return;
    }

    container.innerHTML = contacts.map(c => {
      const unread = c.status === 'new';
      const typeLabel = (c.inquiryType || c.inquiry_type || 'general').replace(/^\w/, m => m.toUpperCase());
      return `
        <article class="contact-card ${unread ? 'unread' : ''}" data-id="${c.id}">
          ${statusBadge(c.status)}
          <h4>${escapeHtml(c.name)}</h4>
          <div class="email">${escapeHtml(c.email)}</div>
          <div class="preview">${escapeHtml((c.message || '').substring(0, 140))}${(c.message || '').length > 140 ? '…' : ''}</div>
          <div class="meta">
            <span>${typeLabel}</span>
            <span>${formatDate(c.createdAt)}</span>
          </div>
          ${c.reply_count > 0 ? `<div class="meta admin-meta-spaced"><span>${c.reply_count} repl${c.reply_count === 1 ? 'y' : 'ies'}</span></div>` : ''}
          <div class="card-actions">
            <button type="button" class="view-btn" data-id="${c.id}">View & Reply</button>
            ${c.status === 'new' ? `<button type="button" class="markread-btn" data-id="${c.id}">Mark Read</button>` : ''}
            <button type="button" class="danger delete-btn" data-id="${c.id}">Delete</button>
          </div>
        </article>`;
    }).join('');

    container.querySelectorAll('.contact-card').forEach(card => {
      card.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        openModal(parseInt(card.dataset.id, 10));
      });
    });
    container.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', (e) => { e.stopPropagation(); openModal(parseInt(btn.dataset.id, 10)); });
    });
    container.querySelectorAll('.markread-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        await api('POST', API, { action: 'mark_read', id: parseInt(btn.dataset.id, 10) });
        toast('Marked as read');
        loadContacts();
      });
    });
    container.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        if (!confirm('Delete this inquiry permanently?')) return;
        await api('DELETE', API + '?id=' + btn.dataset.id);
        toast('Deleted');
        loadContacts();
      });
    });
  }

  async function openModal(id) {
    try {
      const data = await api('GET', API + '?id=' + id);
      currentInquiry = data.inquiry;
      const replies = data.replies || [];

      document.getElementById('replyInquiryId').value = id;
      document.getElementById('replySubject').value = 'Re: Your inquiry — Biver Royalty Homes';
      document.getElementById('replyMessage').value = '';

      let repliesHtml = '';
      if (replies.length) {
        repliesHtml = '<div class="reply-history"><strong>Previous replies</strong>' +
          replies.map(r => `<div class="item"><strong>${escapeHtml(r.subject)}</strong> — ${formatDate(r.sent_at)}<br>${escapeHtml(r.body)}</div>`).join('') +
          '</div>';
      }

      document.getElementById('modalDetail').innerHTML = `
        <div class="detail-row"><strong>Name:</strong> ${escapeHtml(currentInquiry.name)}</div>
        <div class="detail-row"><strong>Email:</strong> <a href="mailto:${escapeHtml(currentInquiry.email)}">${escapeHtml(currentInquiry.email)}</a></div>
        <div class="detail-row"><strong>Phone:</strong> ${escapeHtml(currentInquiry.phone || '—')}</div>
        <div class="detail-row"><strong>Type:</strong> ${escapeHtml(currentInquiry.inquiryType || currentInquiry.inquiry_type)}</div>
        <div class="detail-row"><strong>Received:</strong> ${formatDate(currentInquiry.createdAt)}</div>
        <div class="detail-row"><strong>Status:</strong> ${statusBadge(currentInquiry.status)}</div>
        <div class="message-box">${escapeHtml(currentInquiry.message)}</div>
        ${repliesHtml}`;

      document.getElementById('messageModal').classList.add('open');

      if (currentInquiry.status === 'new') {
        await api('POST', API, { action: 'mark_read', id });
      }
      loadContacts();
    } catch (e) {
      toast(e.message, true);
    }
  }

  function closeModal() {
    document.getElementById('messageModal').classList.remove('open');
    currentInquiry = null;
    loadContacts();
  }

  document.getElementById('closeModal').addEventListener('click', closeModal);
  document.getElementById('messageModal').addEventListener('click', (e) => {
    if (e.target.id === 'messageModal') closeModal();
  });

  document.getElementById('sendReplyBtn').addEventListener('click', async () => {
    const id = parseInt(document.getElementById('replyInquiryId').value, 10);
    const subject = document.getElementById('replySubject').value.trim();
    const message = document.getElementById('replyMessage').value.trim();
    if (!subject || !message) { toast('Subject and message required', true); return; }

    const btn = document.getElementById('sendReplyBtn');
    btn.disabled = true;
    try {
      const res = await api('POST', API, { action: 'reply', inquiry_id: id, subject, message });
      toast(res.message, !res.mail_sent && res.reply_id);
      closeModal();
    } catch (e) {
      toast(e.message, true);
    } finally {
      btn.disabled = false;
    }
  });

  document.getElementById('archiveBtn').addEventListener('click', async () => {
    const id = parseInt(document.getElementById('replyInquiryId').value, 10);
    await api('POST', API, { action: 'update_status', id, status: 'archived' });
    toast('Archived');
    closeModal();
  });

  document.getElementById('deleteInquiryBtn').addEventListener('click', async () => {
    if (!confirm('Delete this inquiry?')) return;
    const id = document.getElementById('replyInquiryId').value;
    await api('DELETE', API + '?id=' + id);
    toast('Deleted');
    closeModal();
  });

  document.getElementById('composeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const res = await api('POST', API, {
        action: 'compose',
        name: fd.get('name'),
        email: fd.get('email'),
        subject: fd.get('subject'),
        message: fd.get('message')
      });
      toast(res.message, !res.mail_sent);
      e.target.reset();
    } catch (err) {
      toast(err.message, true);
    }
  });

  document.getElementById('searchInput').addEventListener('input', debounce(loadContacts, 350));
  document.getElementById('statusFilter').addEventListener('change', loadContacts);
  document.getElementById('refreshBtn').addEventListener('click', loadContacts);
  document.getElementById('deleteReadBtn').addEventListener('click', async () => {
    if (!confirm('Delete all read and archived inquiries?')) return;
    const res = await api('DELETE', API + '?bulk=read');
    toast(res.message);
    loadContacts();
  });

  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }

  loadContacts();
})();
</script>
</body>
</html>
