<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
AdminPermissions::require(AdminPermissions::PERM_TRANSACTIONS);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipts & Certificates | Biver Royalty Homes Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <style>
    .tx-content { padding: 28px; }
    .tx-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 18px; margin-bottom: 26px; }
    .tx-stat { background: #fff; border: 1px solid var(--border-light); border-radius: 20px; padding: 20px 22px; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 16px; }
    .tx-stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(212,175,55,0.14); color: var(--gold-dark); flex-shrink: 0; }
    .tx-stat-num { font-family: var(--ff-display); font-size: 1.7rem; font-weight: 700; color: var(--text-dark); line-height: 1; }
    .tx-stat-label { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }

    .tx-toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
    .tx-search { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--border-light); border-radius: 40px; padding: 10px 18px; flex: 1 1 240px; max-width: 360px; box-shadow: var(--shadow-sm); }
    .tx-search ion-icon { color: var(--gold-dark); font-size: 20px; }
    .tx-search input { border: none; outline: none; width: 100%; font-family: var(--ff-body); font-size: 0.95rem; background: transparent; color: var(--text-dark); }
    .tx-filter { border: 1px solid var(--border-light); background: #fff; color: var(--text-dark); border-radius: 40px; padding: 10px 16px; font-family: var(--ff-body); font-size: 0.9rem; cursor: pointer; }

    .tx-table-wrap { background: #fff; border: 1px solid var(--border-light); border-radius: 20px; box-shadow: var(--shadow-sm); overflow-x: auto; }
    .tx-table { width: 100%; border-collapse: collapse; min-width: 820px; }
    .tx-table thead th { text-align: left; padding: 15px 18px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); background: #faf8f3; border-bottom: 1px solid var(--border-light); white-space: nowrap; }
    .tx-table tbody td { padding: 14px 18px; border-bottom: 1px solid var(--border-light); font-size: 0.9rem; color: var(--text-dark); vertical-align: middle; }
    .tx-table tbody tr:last-child td { border-bottom: none; }
    .tx-table tbody tr:hover { background: #fdfbf6; }
    .tx-ref { font-weight: 700; color: var(--gold-dark); white-space: nowrap; }
    .tx-amount { font-weight: 700; white-space: nowrap; }

    .tx-badge { display: inline-block; padding: 4px 12px; border-radius: 30px; font-size: 0.72rem; font-weight: 700; text-transform: capitalize; }
    .tx-badge.type { background: rgba(55,24,1,0.08); color: var(--prussian-blue); }
    .tx-badge.pay-paid { background: rgba(25,135,84,0.14); color: var(--success); }
    .tx-badge.pay-part { background: rgba(255,193,7,0.2); color: #997404; }
    .tx-badge.pay-pending { background: rgba(220,53,69,0.12); color: var(--danger); }

    .tx-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .tx-abtn { border: none; border-radius: 9px; padding: 7px 11px; cursor: pointer; font-size: 0.78rem; font-weight: 600; font-family: var(--ff-body); display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; text-decoration: none; }
    .tx-abtn.receipt { background: rgba(212,175,55,0.16); color: var(--gold-dark); }
    .tx-abtn.receipt:hover { background: var(--gold-gradient); color: var(--prussian-blue); }
    .tx-abtn.cert { background: rgba(55,24,1,0.1); color: var(--prussian-blue); }
    .tx-abtn.cert:hover { background: var(--prussian-blue); color: #fff; }
    .tx-abtn.email { background: rgba(31,138,76,0.12); color: #1f8a4c; }
    .tx-abtn.email:hover { background: #1f8a4c; color: #fff; }
    .tx-abtn.edit { background: #eef2f6; color: #33506b; }
    .tx-abtn.edit:hover { background: #33506b; color: #fff; }
    .tx-abtn.del { background: rgba(220,53,69,0.1); color: var(--danger); }
    .tx-abtn.del:hover { background: var(--danger); color: #fff; }

    .tx-empty, .tx-loading { padding: 50px 20px; text-align: center; color: var(--text-muted); }

    /* Modal */
    .tx-modal { position: fixed; inset: 0; background: rgba(20,14,4,0.72); backdrop-filter: blur(6px); display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 4vh 16px; z-index: 1000; }
    .tx-modal.open { display: flex; }
    .tx-modal-card { background: #fff; border-radius: 24px; width: 100%; max-width: 720px; margin: auto; box-shadow: 0 30px 60px rgba(0,0,0,0.35); animation: txPop .25s ease; }
    @keyframes txPop { from { opacity:0; transform: translateY(14px);} to { opacity:1; transform: translateY(0);} }
    .tx-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 22px 26px; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; background: #fff; border-radius: 24px 24px 0 0; z-index: 2; }
    .tx-modal-head h3 { font-family: var(--ff-display); font-size: 1.6rem; font-weight: 600; color: var(--text-dark); }
    .tx-close { background: #f3efe6; border: none; width: 38px; height: 38px; border-radius: 50%; cursor: pointer; font-size: 22px; color: var(--text-muted); display: flex; align-items: center; justify-content: center; transition: .2s; }
    .tx-close:hover { background: var(--danger); color: #fff; }

    .tx-form { padding: 24px 26px 28px; display: flex; flex-direction: column; gap: 16px; }
    .tx-section-title { font-weight: 700; color: var(--gold-dark); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .tx-field { display: flex; flex-direction: column; gap: 6px; }
    .tx-field label { font-size: 0.85rem; font-weight: 600; color: var(--text-dark); }
    .tx-field .req { color: var(--danger); }
    .tx-input, .tx-select, .tx-textarea { width: 100%; border: 1px solid var(--border-light); background: #faf8f3; border-radius: 14px; padding: 12px 15px; font-family: var(--ff-body); font-size: 0.95rem; color: var(--text-dark); outline: none; transition: .2s; }
    .tx-input:focus, .tx-select:focus, .tx-textarea:focus { border-color: var(--gold); background: #fff; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
    .tx-textarea { min-height: 80px; resize: vertical; }
    .tx-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .tx-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .tx-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 6px; }
    .tx-save, .tx-cancel { border-radius: 40px; padding: 11px 26px; font-weight: 600; font-family: var(--ff-body); cursor: pointer; font-size: 0.9rem; }
    .tx-save { background: var(--gold-gradient); color: var(--prussian-blue); border: none; }
    .tx-save:hover { box-shadow: 0 6px 16px rgba(212,175,55,0.4); }
    .tx-save:disabled { opacity: .6; cursor: not-allowed; }
    .tx-cancel { background: transparent; border: 1px solid var(--border-light); color: var(--text-muted); }
    .tx-cancel:hover { border-color: var(--danger); color: var(--danger); }

    @media (max-width: 720px) { .tx-grid-2, .tx-grid-3 { grid-template-columns: 1fr; } }
  </style>
</head>
<body class="admin-app">
<?php $activeNav = 'transactions'; ?>
<div class="dashboard admin-dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="main-content">
    <header class="admin-topbar">
      <div class="admin-header-actions--lg">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><ion-icon name="menu-outline"></ion-icon></button>
        <h1 class="admin-page-title">Receipts &amp; Certificates</h1>
      </div>
      <button class="admin-btn-primary" id="addBtn"><ion-icon name="add-circle-outline"></ion-icon> New Transaction</button>
    </header>

    <div class="tx-content">
      <div class="tx-stats">
        <div class="tx-stat"><div class="tx-stat-icon"><ion-icon name="documents-outline"></ion-icon></div><div><div class="tx-stat-num" id="statTotal">0</div><div class="tx-stat-label">Transactions</div></div></div>
        <div class="tx-stat"><div class="tx-stat-icon"><ion-icon name="cash-outline"></ion-icon></div><div><div class="tx-stat-num" id="statRevenue">₦0</div><div class="tx-stat-label">Total Received</div></div></div>
        <div class="tx-stat"><div class="tx-stat-icon"><ion-icon name="home-outline"></ion-icon></div><div><div class="tx-stat-num" id="statPurchases">0</div><div class="tx-stat-label">Purchases</div></div></div>
        <div class="tx-stat"><div class="tx-stat-icon"><ion-icon name="key-outline"></ion-icon></div><div><div class="tx-stat-num" id="statRentals">0</div><div class="tx-stat-label">Rentals</div></div></div>
      </div>

      <div class="tx-toolbar">
        <div class="tx-search">
          <ion-icon name="search-outline"></ion-icon>
          <input type="text" id="searchInput" placeholder="Search reference, customer, property...">
        </div>
        <select class="tx-filter" id="typeFilter">
          <option value="">All types</option>
          <option value="purchase">Purchase</option>
          <option value="rent">Rent</option>
          <option value="sale">Sale</option>
        </select>
        <select class="tx-filter" id="statusFilter">
          <option value="">All payments</option>
          <option value="paid">Paid</option>
          <option value="part">Part payment</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div class="tx-table-wrap">
        <table class="tx-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Customer</th>
              <th>Type</th>
              <th>Property</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="txRows">
            <tr><td colspan="8" class="tx-loading">Loading transactions...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Modal -->
<div id="txModal" class="tx-modal">
  <div class="tx-modal-card">
    <div class="tx-modal-head">
      <h3 id="modalTitle">New Transaction</h3>
      <button type="button" class="tx-close" id="closeBtn" aria-label="Close">&times;</button>
    </div>
    <form id="txForm" class="tx-form">
      <input type="hidden" id="txId">
      <input type="hidden" id="customerId">
      <input type="hidden" id="propertyId">

      <div class="tx-section-title">Customer</div>
      <div class="tx-field">
        <label for="customerName">Customer Name <span class="req">*</span></label>
        <input type="text" id="customerName" class="tx-input" list="customerList" placeholder="Type or pick a customer" autocomplete="off" required>
        <datalist id="customerList"></datalist>
      </div>
      <div class="tx-grid-2">
        <div class="tx-field"><label for="customerEmail">Email</label><input type="email" id="customerEmail" class="tx-input" placeholder="name@example.com"></div>
        <div class="tx-field"><label for="customerPhone">Phone</label><input type="text" id="customerPhone" class="tx-input" placeholder="+234 ..."></div>
      </div>
      <div class="tx-field"><label for="customerAddress">Address</label><input type="text" id="customerAddress" class="tx-input" placeholder="Customer address"></div>

      <div class="tx-section-title">Property</div>
      <div class="tx-field">
        <label for="propertyTitle">Property</label>
        <input type="text" id="propertyTitle" class="tx-input" list="propertyList" placeholder="Type or pick a property" autocomplete="off">
        <datalist id="propertyList"></datalist>
      </div>
      <div class="tx-field"><label for="propertyLocation">Property Location</label><input type="text" id="propertyLocation" class="tx-input" placeholder="e.g. Owerri, Imo State"></div>

      <div class="tx-section-title">Transaction</div>
      <div class="tx-grid-3">
        <div class="tx-field">
          <label for="transactionType">Type</label>
          <select id="transactionType" class="tx-select">
            <option value="purchase">Purchase (client buys)</option>
            <option value="rent">Rent (client rents)</option>
            <option value="sale">Sale (client sells to us)</option>
          </select>
        </div>
        <div class="tx-field">
          <label for="paymentStatus">Payment Status</label>
          <select id="paymentStatus" class="tx-select">
            <option value="paid">Paid in full</option>
            <option value="part">Part payment</option>
            <option value="pending">Pending</option>
          </select>
        </div>
        <div class="tx-field">
          <label for="transactionDate">Date</label>
          <input type="date" id="transactionDate" class="tx-input">
        </div>
      </div>
      <div class="tx-grid-3">
        <div class="tx-field"><label for="amount">Total Amount (₦) <span class="req">*</span></label><input type="number" id="amount" class="tx-input" min="0" placeholder="0"></div>
        <div class="tx-field"><label for="amountPaid">Amount Paid (₦)</label><input type="number" id="amountPaid" class="tx-input" min="0" placeholder="0"></div>
        <div class="tx-field"><label for="paymentMethod">Payment Method</label><input type="text" id="paymentMethod" class="tx-input" placeholder="Bank transfer, Cash..."></div>
      </div>
      <div class="tx-field"><label for="description">Description / Notes</label><textarea id="description" class="tx-textarea" placeholder="Details shown on the receipt..."></textarea></div>

      <div class="tx-form-actions">
        <button type="button" class="tx-cancel" id="cancelBtn">Cancel</button>
        <button type="submit" class="tx-save" id="saveBtn">Save Transaction</button>
      </div>
    </form>
  </div>
</div>

<!-- Email Modal -->
<div id="emailModal" class="tx-modal">
  <div class="tx-modal-card" style="max-width:560px;">
    <div class="tx-modal-head">
      <h3>Share via Email</h3>
      <button type="button" class="tx-close" id="emailCloseBtn" aria-label="Close">&times;</button>
    </div>
    <form id="emailForm" class="tx-form">
      <input type="hidden" id="emailTxId">
      <div class="tx-field">
        <label for="emailDocType">Document</label>
        <select id="emailDocType" class="tx-select">
          <option value="receipt">Receipt</option>
          <option value="certificate">Certificate</option>
        </select>
      </div>
      <div class="tx-field">
        <label for="emailTo">Recipient Email <span class="req">*</span></label>
        <input type="email" id="emailTo" class="tx-input" placeholder="customer@example.com" required>
      </div>
      <div class="tx-field">
        <label for="emailSubject">Subject</label>
        <input type="text" id="emailSubject" class="tx-input" placeholder="Auto-generated if left blank">
      </div>
      <div class="tx-field">
        <label for="emailMessage">Message (optional)</label>
        <textarea id="emailMessage" class="tx-textarea" placeholder="A short note to the customer..."></textarea>
      </div>
      <div class="tx-form-actions">
        <button type="button" class="tx-cancel" id="emailCancelBtn">Cancel</button>
        <button type="submit" class="tx-save" id="emailSendBtn"><ion-icon name="paper-plane-outline"></ion-icon> Send Email</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API = 'api/transactions.php';
  let transactions = [];
  let customerLookup = [];
  let propertyLookup = [];

  function esc(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
  function naira(n) { return '₦' + Number(n || 0).toLocaleString(); }

  function showToast(msg, err = false) {
    document.querySelector('.admin-toast')?.remove();
    const t = document.createElement('div');
    t.className = 'admin-toast' + (err ? ' error' : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3200);
  }

  async function apiGet(qs = '') {
    const res = await fetch(API + qs, { credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) throw new Error('Session expired. Please log in again.');
    if (!res.ok || data.success === false) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
  }
  async function apiPost(payload) {
    const res = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(payload) });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) throw new Error('Session expired. Please log in again.');
    if (!res.ok || data.success === false) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
  }

  function buildQuery() {
    const p = new URLSearchParams();
    const s = document.getElementById('searchInput').value.trim();
    const t = document.getElementById('typeFilter').value;
    const st = document.getElementById('statusFilter').value;
    if (s) p.set('search', s);
    if (t) p.set('type', t);
    if (st) p.set('status', st);
    const q = p.toString();
    return q ? '?' + q : '';
  }

  const typeLabels = { purchase: 'Purchase', rent: 'Rent', sale: 'Sale' };
  const payLabels = { paid: 'Paid', part: 'Part', pending: 'Pending' };

  function fmtDate(s) {
    if (!s) return '—';
    const d = new Date(s.replace(' ', 'T'));
    return isNaN(d) ? esc(s) : d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
  }

  async function loadTransactions() {
    const tbody = document.getElementById('txRows');
    try {
      const data = await apiGet(buildQuery());
      transactions = data.transactions || [];
      const stats = data.stats || {};
      document.getElementById('statTotal').textContent = stats.total ?? 0;
      document.getElementById('statRevenue').textContent = naira(stats.revenue);
      document.getElementById('statPurchases').textContent = stats.purchases ?? 0;
      document.getElementById('statRentals').textContent = stats.rentals ?? 0;
      renderRows();
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="8" class="tx-empty">${esc(err.message)}</td></tr>`;
    }
  }

  function renderRows() {
    const tbody = document.getElementById('txRows');
    if (!transactions.length) {
      tbody.innerHTML = `<tr><td colspan="8" class="tx-empty">No transactions yet. Click “New Transaction” to record one.</td></tr>`;
      return;
    }
    tbody.innerHTML = transactions.map(t => `
      <tr>
        <td class="tx-ref">${esc(t.reference)}</td>
        <td>${esc(t.customerName)}</td>
        <td><span class="tx-badge type">${esc(typeLabels[t.transactionType] || t.transactionType)}</span></td>
        <td>${esc(t.propertyTitle || '—')}</td>
        <td class="tx-amount">${naira(t.amount)}</td>
        <td><span class="tx-badge pay-${esc(t.paymentStatus)}">${esc(payLabels[t.paymentStatus] || t.paymentStatus)}</span></td>
        <td>${fmtDate(t.transactionDate)}</td>
        <td>
          <div class="tx-actions">
            <a class="tx-abtn receipt" href="receipt.php?id=${t.id}" target="_blank" rel="noopener"><ion-icon name="receipt-outline"></ion-icon> Receipt</a>
            <a class="tx-abtn cert" href="certificate.php?id=${t.id}" target="_blank" rel="noopener"><ion-icon name="ribbon-outline"></ion-icon> Certificate</a>
            <button class="tx-abtn email" data-email="${t.id}"><ion-icon name="mail-outline"></ion-icon> Email</button>
            <button class="tx-abtn edit" data-edit="${t.id}"><ion-icon name="create-outline"></ion-icon></button>
            <button class="tx-abtn del" data-del="${t.id}"><ion-icon name="trash-outline"></ion-icon></button>
          </div>
        </td>
      </tr>`).join('');

    tbody.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openModal(b.getAttribute('data-edit'))));
    tbody.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => removeTx(b.getAttribute('data-del'))));
    tbody.querySelectorAll('[data-email]').forEach(b => b.addEventListener('click', () => openEmailModal(b.getAttribute('data-email'))));
  }

  async function loadLookups() {
    try {
      const [c, p] = await Promise.all([apiGet('?lookup=customers'), apiGet('?lookup=properties')]);
      customerLookup = c.customers || [];
      propertyLookup = p.properties || [];
      document.getElementById('customerList').innerHTML = customerLookup.map(x => `<option value="${esc(x.name)}"></option>`).join('');
      document.getElementById('propertyList').innerHTML = propertyLookup.map(x => `<option value="${esc(x.title)}"></option>`).join('');
    } catch (_) { /* lookups are optional */ }
  }

  function openModal(id = null) {
    const form = document.getElementById('txForm');
    form.reset();
    document.getElementById('txId').value = '';
    document.getElementById('customerId').value = '';
    document.getElementById('propertyId').value = '';
    document.getElementById('transactionType').value = 'purchase';
    document.getElementById('paymentStatus').value = 'paid';
    document.getElementById('transactionDate').value = new Date().toISOString().slice(0, 10);
    document.getElementById('modalTitle').textContent = id ? 'Edit Transaction' : 'New Transaction';

    if (id) {
      const t = transactions.find(x => String(x.id) === String(id));
      if (t) {
        document.getElementById('txId').value = t.id;
        document.getElementById('customerId').value = t.customerId || '';
        document.getElementById('propertyId').value = t.propertyId || '';
        document.getElementById('customerName').value = t.customerName || '';
        document.getElementById('customerEmail').value = t.customerEmail || '';
        document.getElementById('customerPhone').value = t.customerPhone || '';
        document.getElementById('customerAddress').value = t.customerAddress || '';
        document.getElementById('propertyTitle').value = t.propertyTitle || '';
        document.getElementById('propertyLocation').value = t.propertyLocation || '';
        document.getElementById('transactionType').value = t.transactionType || 'purchase';
        document.getElementById('paymentStatus').value = t.paymentStatus || 'paid';
        document.getElementById('transactionDate').value = (t.transactionDate || '').slice(0, 10);
        document.getElementById('amount').value = t.amount || '';
        document.getElementById('amountPaid').value = t.amountPaid || '';
        document.getElementById('paymentMethod').value = t.paymentMethod || '';
        document.getElementById('description').value = t.description || '';
      }
    }
    document.getElementById('txModal').classList.add('open');
  }
  function closeModal() { document.getElementById('txModal').classList.remove('open'); }

  // Auto-fill from lookups
  document.getElementById('customerName').addEventListener('change', (e) => {
    const c = customerLookup.find(x => x.name === e.target.value);
    if (c) {
      document.getElementById('customerId').value = c.id;
      if (c.email) document.getElementById('customerEmail').value = c.email;
      if (c.phone) document.getElementById('customerPhone').value = c.phone;
      if (c.address) document.getElementById('customerAddress').value = c.address;
    } else {
      document.getElementById('customerId').value = '';
    }
  });
  document.getElementById('propertyTitle').addEventListener('change', (e) => {
    const p = propertyLookup.find(x => x.title === e.target.value);
    if (p) {
      document.getElementById('propertyId').value = p.id;
      if (p.location) document.getElementById('propertyLocation').value = p.location;
      if (p.price && !document.getElementById('amount').value) document.getElementById('amount').value = p.price;
    } else {
      document.getElementById('propertyId').value = '';
    }
  });

  async function removeTx(id) {
    const t = transactions.find(x => String(x.id) === String(id));
    if (!confirm(`Delete transaction ${t ? t.reference : ''}? This cannot be undone.`)) return;
    try {
      await apiPost({ action: 'delete', id: Number(id) });
      showToast('Transaction deleted');
      loadTransactions();
    } catch (err) { showToast(err.message, true); }
  }

  async function saveTx(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.textContent = 'Saving...';
    try {
      await apiPost({
        id: Number(document.getElementById('txId').value) || 0,
        customerId: Number(document.getElementById('customerId').value) || 0,
        propertyId: Number(document.getElementById('propertyId').value) || 0,
        customerName: document.getElementById('customerName').value,
        customerEmail: document.getElementById('customerEmail').value,
        customerPhone: document.getElementById('customerPhone').value,
        customerAddress: document.getElementById('customerAddress').value,
        propertyTitle: document.getElementById('propertyTitle').value,
        propertyLocation: document.getElementById('propertyLocation').value,
        transactionType: document.getElementById('transactionType').value,
        paymentStatus: document.getElementById('paymentStatus').value,
        transactionDate: document.getElementById('transactionDate').value,
        amount: document.getElementById('amount').value,
        amountPaid: document.getElementById('amountPaid').value,
        paymentMethod: document.getElementById('paymentMethod').value,
        description: document.getElementById('description').value
      });
      showToast('Transaction saved');
      closeModal();
      loadTransactions();
    } catch (err) {
      showToast(err.message, true);
    } finally {
      btn.disabled = false; btn.textContent = 'Save Transaction';
    }
  }

  // ---- Email sharing ----
  function openEmailModal(id) {
    const t = transactions.find(x => String(x.id) === String(id));
    if (!t) return;
    document.getElementById('emailTxId').value = t.id;
    document.getElementById('emailDocType').value = 'receipt';
    document.getElementById('emailTo').value = t.customerEmail || '';
    document.getElementById('emailSubject').value = '';
    document.getElementById('emailMessage').value = '';
    document.getElementById('emailModal').classList.add('open');
  }
  function closeEmailModal() { document.getElementById('emailModal').classList.remove('open'); }

  async function sendEmail(e) {
    e.preventDefault();
    const btn = document.getElementById('emailSendBtn');
    btn.disabled = true; btn.textContent = 'Sending...';
    try {
      const data = await apiPost({
        action: 'email',
        id: Number(document.getElementById('emailTxId').value),
        docType: document.getElementById('emailDocType').value,
        to: document.getElementById('emailTo').value,
        subject: document.getElementById('emailSubject').value,
        message: document.getElementById('emailMessage').value
      });
      showToast(data.message || 'Email sent');
      closeEmailModal();
    } catch (err) {
      showToast(err.message, true);
    } finally {
      btn.disabled = false; btn.innerHTML = '<ion-icon name="paper-plane-outline"></ion-icon> Send Email';
    }
  }

  document.getElementById('emailCloseBtn').addEventListener('click', closeEmailModal);
  document.getElementById('emailCancelBtn').addEventListener('click', closeEmailModal);
  document.getElementById('emailModal').addEventListener('click', e => { if (e.target === document.getElementById('emailModal')) closeEmailModal(); });
  document.getElementById('emailForm').addEventListener('submit', sendEmail);

  let searchTimer;
  document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadTransactions, 300); });
  document.getElementById('typeFilter').addEventListener('change', loadTransactions);
  document.getElementById('statusFilter').addEventListener('change', loadTransactions);
  document.getElementById('addBtn').addEventListener('click', () => openModal());
  document.getElementById('closeBtn').addEventListener('click', closeModal);
  document.getElementById('cancelBtn').addEventListener('click', closeModal);
  document.getElementById('txModal').addEventListener('click', e => { if (e.target === document.getElementById('txModal')) closeModal(); });
  document.getElementById('txForm').addEventListener('submit', saveTx);

  loadTransactions();
  loadLookups();
</script>
</body>
</html>
