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
      <h1 class="page-title">Newsletter</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="stats-row">
        <div class="stat-pill"><strong id="statTotal">—</strong><span>Total</span></div>
        <div class="stat-pill"><strong id="statActive">—</strong><span>Active</span></div>
        <div class="stat-pill"><strong id="statUnsub">—</strong><span>Unsubscribed</span></div>
        <div class="stat-pill"><strong id="statCountries">—</strong><span>Countries</span></div>
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

      <div class="admin-panel country-panel">
        <h2 class="admin-section-title">Subscribers by country</h2>
        <p class="country-hint">Detected automatically from the visitor’s IP when they subscribe on the website.</p>
        <div class="country-stats" id="countryStats">
          <p class="muted">Loading country breakdown…</p>
        </div>
      </div>

      <div class="subscribers-toolbar">
        <input type="search" id="searchInput" placeholder="Search email, name, or country…">
        <select id="statusFilter">
          <option value="">All statuses</option>
          <option value="active" selected>Active</option>
          <option value="unsubscribed">Unsubscribed</option>
        </select>
        <select id="countryFilter">
          <option value="">All countries</option>
        </select>
        <button type="button" class="admin-btn-outline" id="refreshBtn"><ion-icon name="refresh-outline"></ion-icon> Refresh</button>
        <button type="button" class="admin-btn-outline" id="exportBtn"><ion-icon name="download-outline"></ion-icon> Export CSV</button>
        <a href="admin-email-center.php" class="admin-btn-primary"><ion-icon name="paper-plane-outline"></ion-icon> Email Center</a>
      </div>

      <div class="subscribers-table-wrap">
        <table class="subscribers-table">
          <thead>
            <tr>
              <th>Email</th>
              <th>Name</th>
              <th>Country</th>
              <th>Status</th>
              <th>Date &amp; time</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="subscribersBody">
            <tr><td colspan="6">Loading…</td></tr>
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

  function formatDateTime(d) {
    if (!d) return '—';
    const parsed = new Date(String(d).replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) return String(d);
    return parsed.toLocaleString('en-GB', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    });
  }

  function countryLabel(row) {
    if (row.country_name) {
      return row.country_code && row.country_code !== 'UNKNOWN'
        ? `${row.country_name} (${row.country_code})`
        : row.country_name;
    }
    if (row.country_code) return row.country_code;
    return 'Unknown';
  }

  function queryString() {
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;
    const country = document.getElementById('countryFilter').value;
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (search) params.set('search', search);
    if (country) params.set('country', country);
    const qs = params.toString();
    return qs ? ('?' + qs) : '';
  }

  function renderCountries(countries) {
    const wrap = document.getElementById('countryStats');
    const select = document.getElementById('countryFilter');
    const current = select.value;
    const rows = Array.isArray(countries) ? countries : [];

    select.innerHTML = '<option value="">All countries</option>' + rows.map(c => {
      const value = c.country_code || c.country_name || '';
      return `<option value="${esc(value)}">${esc(c.country_name || 'Unknown')} (${Number(c.total) || 0})</option>`;
    }).join('');
    if ([...select.options].some(o => o.value === current)) select.value = current;

    if (!rows.length) {
      wrap.innerHTML = '<p class="muted">No country data yet. New website signups will record the visitor country automatically.</p>';
      return;
    }

    wrap.innerHTML = rows.map(c => `
      <button type="button" class="country-chip" data-country="${esc(c.country_code || '')}">
        <strong>${esc(c.country_name || 'Unknown')}</strong>
        <span>${Number(c.active) || 0} active · ${Number(c.total) || 0} total</span>
      </button>
    `).join('');

    wrap.querySelectorAll('.country-chip').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('countryFilter').value = btn.dataset.country || '';
        loadSubscribers();
      });
    });
  }

  async function loadSubscribers() {
    const tbody = document.getElementById('subscribersBody');
    try {
      const data = await api('GET', API + queryString());
      const rows = data.subscribers || [];
      if (data.stats) {
        document.getElementById('statTotal').textContent = data.stats.total ?? 0;
        document.getElementById('statActive').textContent = data.stats.active ?? 0;
        document.getElementById('statUnsub').textContent = data.stats.unsubscribed ?? 0;
        document.getElementById('statCountries').textContent = data.stats.countries ?? 0;
      }
      renderCountries(data.countries || []);
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6">No subscribers found.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(r => `
        <tr>
          <td>${esc(r.email)}</td>
          <td>${esc(r.name || '—')}</td>
          <td>${esc(countryLabel(r))}</td>
          <td><span class="subscriber-status ${esc(r.status)}">${esc(r.status)}</span></td>
          <td>
            <div>${esc(r.subscribed_at_label || formatDateTime(r.subscribed_at))}</div>
            ${r.subscribed_local_label ? `<small class="sub-local-time">${esc(r.subscribed_local_label)}</small>` : ''}
          </td>
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
      tbody.innerHTML = '<tr><td colspan="6">' + esc(e.message) + '</td></tr>';
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

  document.getElementById('exportBtn').addEventListener('click', () => {
    window.location.href = API + queryString() + (queryString() ? '&' : '?') + 'export=csv';
  });

  let debounceTimer;
  document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadSubscribers, 350);
  });
  document.getElementById('statusFilter').addEventListener('change', loadSubscribers);
  document.getElementById('countryFilter').addEventListener('change', loadSubscribers);
  document.getElementById('refreshBtn').addEventListener('click', loadSubscribers);

  loadSubscribers();
})();
</script>
</body>
</html>
