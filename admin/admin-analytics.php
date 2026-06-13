<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Royal Analytics | Intelligence Hub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <link rel="stylesheet" href="../assets/css/admin-analytics.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  </head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<?php $activeNav = 'analytics'; ?>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main">
    <div class="top-bar">
      <div class="admin-header-actions">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-grip-lines"></i></button>
        <h1 class="page-title"><i class="fas fa-chart-pie"></i> Royal Analytics</h1>
      </div>
      <button class="refresh-btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh Data</button>
    </div>

    <!-- KPI Cards -->
    <div class="stats-grid" id="statsGrid">
      <div class="stat-card"><div class="stat-title">Total Properties</div><div class="stat-number" id="totalProps">—</div><div class="stat-change" id="propsTrend"></div></div>
      <div class="stat-card"><div class="stat-title">Public Submissions</div><div class="stat-number" id="totalSubmissions">—</div><div class="stat-change" id="submissionsTrend"></div></div>
      <div class="stat-card"><div class="stat-title">Testimonials</div><div class="stat-number" id="totalTest">—</div><div class="stat-change" id="testTrend"></div></div>
      <div class="stat-card"><div class="stat-title">Inquiries</div><div class="stat-number" id="totalInq">—</div><div class="stat-change" id="inqTrend"></div></div>
    </div>

    <!-- Charts -->
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-header"><i class="fas fa-chart-line"></i> Monthly Listings (Last 6 months)</div>
        <canvas id="monthlyChart" width="400" height="250"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-header"><i class="fas fa-chart-pie"></i> Property Type Distribution</div>
        <canvas id="typeChart" width="400" height="250"></canvas>
      </div>
    </div>
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-header"><i class="fas fa-envelope-open-text"></i> Inquiries Trend (Last 6 months)</div>
        <canvas id="inquiriesChart" width="400" height="250"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-header"><i class="fas fa-star"></i> Testimonials Rating Distribution</div>
        <canvas id="ratingChart" width="400" height="250"></canvas>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-table">
      <h3><i class="fas fa-history"></i> Recent Properties Added</h3>
      <table id="recentPropsTable">
        <thead><tr><th>Title</th><th>Price (₦)</th><th>Type</th><th>Date</th></tr></thead>
        <tbody><tr><td colspan="4">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const API = 'api/analytics.php';
  let monthlyChart, typeChart, inquiriesChart, ratingChart;

  function trendHtml(trend) {
    if (!trend) return '';
    const cls = trend.direction === 'down' ? 'negative' : 'positive';
    const icon = trend.direction === 'down' ? 'fa-arrow-down' : 'fa-arrow-up';
    const value = trend.value ?? 0;
    if (trend.direction === 'neutral') {
      return `<span class="positive"><i class="fas fa-minus"></i> 0%</span> ${trend.label || 'vs last month'}`;
    }
    return `<span class="${cls}"><i class="fas ${icon}"></i> ${value}%</span> ${trend.label || 'vs last month'}`;
  }

  async function loadAllData() {
    try {
      const res = await fetch(API, { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load analytics');
      const report = data.report || {};
      const kpis = report.kpis || {};
      const trends = report.trends || {};

      document.getElementById('totalProps').innerText = kpis.properties ?? 0;
      document.getElementById('totalSubmissions').innerText = kpis.submissions ?? 0;
      document.getElementById('totalTest').innerText = kpis.testimonials ?? 0;
      document.getElementById('totalInq').innerText = kpis.inquiries ?? 0;

      document.getElementById('propsTrend').innerHTML = trendHtml(trends.properties);
      document.getElementById('submissionsTrend').innerHTML = trendHtml(trends.submissions);
      document.getElementById('testTrend').innerHTML = trendHtml(trends.testimonials);
      document.getElementById('inqTrend').innerHTML = trendHtml(trends.inquiries);

      updateCharts(report);
      renderRecentProperties(report.recentProperties || []);
    } catch (err) {
      showToast('Failed to load analytics data', true);
      console.error(err);
    }
  }

  function updateCharts(report) {
    const monthlyListings = report.monthlyListings || [];
    const monthlyInquiries = report.monthlyInquiries || [];
    const months = monthlyListings.map((row) => row.label);
    const listingCounts = monthlyListings.map((row) => row.count);
    const inquiryCounts = monthlyInquiries.map((row) => row.count);
    const types = report.propertyTypes || { sale: 0, rent: 0 };
    const ratings = report.ratingDistribution || [0, 0, 0, 0, 0];

    if (monthlyChart) monthlyChart.destroy();
    if (typeChart) typeChart.destroy();
    if (inquiriesChart) inquiriesChart.destroy();
    if (ratingChart) ratingChart.destroy();

    monthlyChart = new Chart(document.getElementById('monthlyChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: months,
        datasets: [{
          label: 'Properties Added',
          data: listingCounts,
          borderColor: '#D4AF37',
          backgroundColor: 'rgba(212,175,55,0.1)',
          tension: 0.3,
          fill: true,
          pointBackgroundColor: '#D4AF37'
        }]
      },
      options: { responsive: true, maintainAspectRatio: true }
    });

    typeChart = new Chart(document.getElementById('typeChart').getContext('2d'), {
      type: 'pie',
      data: {
        labels: ['For Sale', 'For Rent'],
        datasets: [{
          data: [types.sale || 0, types.rent || 0],
          backgroundColor: ['#D4AF37', '#9e7a2c'],
          borderWidth: 0
        }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#fef7e8' } } } }
    });

    inquiriesChart = new Chart(document.getElementById('inquiriesChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: months,
        datasets: [{
          label: 'Contact Inquiries',
          data: inquiryCounts,
          backgroundColor: 'rgba(212,175,55,0.6)',
          borderRadius: 8
        }]
      },
      options: { responsive: true, maintainAspectRatio: true }
    });

    ratingChart = new Chart(document.getElementById('ratingChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['⭐ 1', '⭐ 2', '⭐ 3', '⭐ 4', '⭐ 5'],
        datasets: [{
          label: 'Number of Testimonials',
          data: ratings,
          backgroundColor: '#D4AF37',
          borderRadius: 8
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });
  }

  function renderRecentProperties(items) {
    const tbody = document.querySelector('#recentPropsTable tbody');
    if (!tbody) return;
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="4">No properties found</td></tr>';
      return;
    }
    tbody.innerHTML = items.map((p) => `
      <tr>
        <td>${escapeHtml(p.title)}</td>
        <td>₦${Number(p.price || 0).toLocaleString()}</td>
        <td>${p.type === 'sale' ? '🏷️ Sale' : '📄 Rent'}</td>
        <td>${p.createdAt ? new Date(p.createdAt).toLocaleDateString() : 'N/A'}</td>
      </tr>
    `).join('');
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
  }

  function showToast(msg, isError = false) {
    let toast = document.querySelector('.toast-msg');
    if (toast) toast.remove();
    toast = document.createElement('div');
    toast.className = 'toast-msg';
    toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${msg}`;
    toast.style.borderLeftColor = isError ? '#e74c3c' : '#D4AF37';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  document.getElementById('refreshBtn')?.addEventListener('click', () => {
    loadAllData();
    showToast('Analytics refreshed');
  });


  loadAllData();
</script>
</body>
</html>