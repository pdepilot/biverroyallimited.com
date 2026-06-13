<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <meta name="description" content="Biver Royalty Homes Admin Dashboard – Manage properties, users, testimonials, and site content.">
  <title>Admin Dashboard | Biver Royalty Homes</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin-common.css">
  <link rel="stylesheet" href="../assets/css/admin-dashboard.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  </head>
<body>
<?php $activeNav = 'dashboard'; ?>
<div class="dashboard admin-dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main-content admin-main">
    <div class="topbar">
      <button class="menu-toggle" id="menuToggle"><ion-icon name="menu-outline"></ion-icon></button>
      <h1 class="page-title" id="mainTitle">Dashboard</h1>
      <div class="admin-badge">Admin Portal</div>
    </div>

    <div class="admin-content-pad" id="dynamicContent">
      <div class="loading-spinner">Loading dashboard...</div>
    </div>
  </div>
</div>

<script>
  (function () {
    const API = 'api/dashboard.php';
    let chartInstance = null;

    function esc(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function formatMoney(value) {
      const num = Number(value || 0);
      return '₦' + num.toLocaleString();
    }

    function showToast(msg, isError) {
      let toast = document.querySelector('.toast-msg');
      if (toast) toast.remove();
      toast = document.createElement('div');
      toast.className = 'toast-msg';
      if (isError) toast.classList.add('toast-msg--error');
      toast.textContent = msg;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3200);
    }

    function renderOverview(container, data) {
      const stats = data.stats || {};
      const props = stats.properties || {};
      const subs = stats.submissions || {};
      const contacts = stats.contacts || {};
      const promo = stats.promo || {};
      const monthly = data.monthlyListings || [];
      const labels = monthly.map((m) => m.label);
      const counts = monthly.map((m) => m.count);
      const recentProps = data.recentProperties || [];
      const recentSubs = data.recentSubmissions || [];
      const recentContacts = data.recentContacts || [];

      container.innerHTML = `
        <div class="stats-grid">
          <div class="stat-card"><div><h3>Total Properties</h3><div class="stat-number">${props.total ?? 0}</div><small>${props.approved ?? 0} approved</small></div><ion-icon name="home-outline" class="stat-icon"></ion-icon></div>
          <div class="stat-card"><div><h3>Pending Submissions</h3><div class="stat-number">${subs.pending ?? 0}</div><small>${subs.total ?? 0} total submitted</small></div><ion-icon name="clipboard-outline" class="stat-icon"></ion-icon></div>
          <div class="stat-card"><div><h3>New Inquiries</h3><div class="stat-number">${contacts.new ?? 0}</div><small>${contacts.total ?? 0} total contacts</small></div><ion-icon name="mail-outline" class="stat-icon"></ion-icon></div>
          <div class="stat-card"><div><h3>Promo Banner</h3><div class="stat-number admin-stat-number--sm">${promo.enabled ? 'Live' : 'Off'}</div><small>${promo.hasFlier ? 'Designer flier active' : 'Using fallback design'}</small></div><ion-icon name="megaphone-outline" class="stat-icon"></ion-icon></div>
        </div>

        <div class="panel">
          <div class="panel-header"><h2>Listings Added (Last 12 Months)</h2></div>
          <canvas id="listingsChart" height="200"></canvas>
        </div>

        <div class="panel">
          <div class="panel-header"><h2>Recent Properties</h2><a href="admin-property.php" class="btn-outline">Manage Properties</a></div>
          <table><thead><tr><th>Title</th><th>Price</th><th>Type</th><th>Status</th></tr></thead><tbody>
            ${recentProps.length ? recentProps.map((p) => `<tr><td>${esc(p.title)}</td><td>${formatMoney(p.price)}</td><td>${esc(p.type)}</td><td>${esc(p.approvalStatus || 'pending')}</td></tr>`).join('') : '<tr><td colspan="4">No properties yet.</td></tr>'}
          </tbody></table>
        </div>

        <div class="panel">
          <div class="panel-header"><h2>Pending List-Your-Property Submissions</h2><a href="admin-list-your-property.php" class="btn-outline">Review Submissions</a></div>
          <table><thead><tr><th>Title</th><th>Owner</th><th>Location</th><th>Status</th></tr></thead><tbody>
            ${recentSubs.length ? recentSubs.map((s) => `<tr><td>${esc(s.title)}</td><td>${esc(s.ownerName || s.owner?.name || '—')}</td><td>${esc(s.location || '—')}</td><td>${esc(s.approvalStatus || 'pending')}</td></tr>`).join('') : '<tr><td colspan="4">No pending submissions.</td></tr>'}
          </tbody></table>
        </div>

        <div class="panel">
          <div class="panel-header"><h2>Latest Contact Inquiries</h2><a href="admin-contact.php" class="btn-outline">View Inquiries</a></div>
          <table><thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Status</th></tr></thead><tbody>
            ${recentContacts.length ? recentContacts.map((c) => `<tr><td>${esc(c.full_name || c.name)}</td><td>${esc(c.email)}</td><td>${esc((c.message || '').slice(0, 60))}${(c.message || '').length > 60 ? '…' : ''}</td><td>${esc(c.status || 'new')}</td></tr>`).join('') : '<tr><td colspan="4">No inquiries yet.</td></tr>'}
          </tbody></table>
        </div>

        <div class="panel">
          <div class="panel-header"><h2>Quick Actions</h2></div>
          <div class="admin-quick-actions">
            <a href="admin-promo-banner.php" class="btn-primary">Edit Promo Banner</a>
            <a href="admin-setting.php" class="btn-outline">Site Settings</a>
          </div>
        </div>`;

      const ctx = document.getElementById('listingsChart')?.getContext('2d');
      if (ctx && window.Chart) {
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels.length ? labels : ['No data yet'],
            datasets: [{
              label: 'Properties added',
              data: counts.length ? counts : [0],
              borderColor: '#D4AF37',
              backgroundColor: 'rgba(212,175,55,0.12)',
              tension: 0.35,
              fill: true
            }]
          },
          options: { responsive: true, plugins: { legend: { display: false } } }
        });
      }
    }

    async function loadDashboard() {
      const container = document.getElementById('dynamicContent');
      if (!container) return;
      try {
        const res = await fetch(API);
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to load dashboard');
        renderOverview(container, data);
      } catch (err) {
        container.innerHTML = `<div class="panel"><h2>Dashboard unavailable</h2><p>${esc(err.message)}</p><button class="btn-primary" type="button" onclick="location.reload()">Retry</button></div>`;
        showToast(err.message, true);
      }
    }

    loadDashboard();
  })();
</script>
</body>
</html>