<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';

$activeNav = 'subscribers';
$pageTitle = 'Newsletter Subscribers | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-subscribers.css';
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
      <h1 class="page-title">Subscribers</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="stats-row">
        <div class="stat-pill"><strong id="statTotal">—</strong><span>Total</span></div>
        <div class="stat-pill"><strong id="statActive">—</strong><span>Active</span></div>
        <div class="stat-pill"><strong id="statUnsub">—</strong><span>Unsubscribed</span></div>
      </div>

      <div class="admin-panel">
        <h2 class="admin-section-title">Add Subscriber</h2>
        <form class="add-subscriber-form" id="addForm">
          <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <div>
            <label for="addEmail">Email</label>
            <input type="email" id="addEmail" required placeholder="subscriber@example.com">
          </div>
          <div>
            <label for="addName">Name (optional)</label>
            <input type="text" id="addName" placeholder="Full name">
          </div>
          <button type="submit" class="admin-btn-primary"><ion-icon name="person-add-outline"></ion-icon> Add</button>
        </form>
      </div>

      <div class="subscribers-toolbar">
        <input type="search" id="searchInput" placeholder="Search email or name…">
        <select id="statusFilter">
          <option value="">All statuses</option>
          <option value="active" selected>Active</option>
          <option value="unsubscribed">Unsubscribed</option>
        </select>
        <button type="button" class="admin-btn-outline" id="refreshBtn"><ion-icon name="refresh-outline"></ion-icon> Refresh</button>
        <a href="admin-email-center.php" class="admin-btn-primary"><ion-icon name="paper-plane-outline"></ion-icon> Email Center</a>
      </div>

      <div class="subscribers-table-wrap">
        <table class="subscribers-table">
          <thead>
            <tr><th>Email</th><th>Name</th><th>Status</th><th>Subscribed</th><th>Actions</th></tr>
          </thead>
          <tbody id="subscribersBody">
            <tr><td colspan="5">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const API = 'api/subscribers.php';
  const csrf = document.getElementById('csrfToken').value;

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
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  async function loadSubscribers() {
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    let url = API + '?';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    if (search) url += 'search=' + encodeURIComponent(search);

    const tbody = document.getElementById('subscribersBody');
    try {
      const data = await api('GET', url);
      const rows = data.subscribers || [];
      if (data.stats) {
        document.getElementById('statTotal').textContent = data.stats.total ?? 0;
        document.getElementById('statActive').textContent = data.stats.active ?? 0;
        document.getElementById('statUnsub').textContent = data.stats.unsubscribed ?? 0;
      }
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="5">No subscribers found.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(r => `
        <tr>
          <td>${esc(r.email)}</td>
          <td>${esc(r.name || '—')}</td>
          <td><span class="subscriber-status ${esc(r.status)}">${esc(r.status)}</span></td>
          <td>${formatDate(r.subscribed_at)}</td>
          <td class="row-actions">
            ${r.status === 'active'
              ? `<button type="button" class="admin-btn-outline unsub-btn" data-id="${r.id}">Unsubscribe</button>`
              : `<button type="button" class="admin-btn-outline activate-btn" data-id="${r.id}">Reactivate</button>`}
            <button type="button" class="admin-btn-outline danger delete-btn" data-id="${r.id}">Delete</button>
          </td>
        </tr>`).join('');

      tbody.querySelectorAll('.unsub-btn').forEach(btn => btn.addEventListener('click', async () => {
        await api('POST', API, { action: 'update_status', id: parseInt(btn.dataset.id, 10), status: 'unsubscribed' });
        toast('Subscriber unsubscribed');
        loadSubscribers();
      }));
      tbody.querySelectorAll('.activate-btn').forEach(btn => btn.addEventListener('click', async () => {
        await api('POST', API, { action: 'update_status', id: parseInt(btn.dataset.id, 10), status: 'active' });
        toast('Subscriber reactivated');
        loadSubscribers();
      }));
      tbody.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', async () => {
        if (!confirm('Remove this subscriber permanently?')) return;
        await api('DELETE', API + '?id=' + btn.dataset.id, { id: parseInt(btn.dataset.id, 10) });
        toast('Subscriber removed');
        loadSubscribers();
      }));
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="5">' + esc(e.message) + '</td></tr>';
      toast(e.message, true);
    }
  }

  document.getElementById('addForm').addEventListener('submit', async e => {
    e.preventDefault();
    try {
      await api('POST', API, {
        action: 'add',
        email: document.getElementById('addEmail').value.trim(),
        name: document.getElementById('addName').value.trim()
      });
      toast('Subscriber added');
      e.target.reset();
      loadSubscribers();
    } catch (err) {
      toast(err.message, true);
    }
  });

  let debounceTimer;
  document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadSubscribers, 350);
  });
  document.getElementById('statusFilter').addEventListener('change', loadSubscribers);
  document.getElementById('refreshBtn').addEventListener('click', loadSubscribers);

  loadSubscribers();
})();
</script>
</body>
</html>
