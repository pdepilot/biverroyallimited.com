<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
AdminPermissions::require(AdminPermissions::PERM_CUSTOMERS);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customers | Biver Royalty Homes Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <style>
    .cm-content { padding: 28px; }

    .cm-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 26px; }
    .cm-stat {
      background: #fff;
      border: 1px solid var(--border-light);
      border-radius: 20px;
      padding: 20px 22px;
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .cm-stat-icon {
      width: 48px; height: 48px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px;
      background: rgba(212,175,55,0.14);
      color: var(--gold-dark);
      flex-shrink: 0;
    }
    .cm-stat-num { font-family: var(--ff-display); font-size: 1.9rem; font-weight: 700; color: var(--text-dark); line-height: 1; }
    .cm-stat-label { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }

    .cm-toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
    .cm-search {
      display: flex; align-items: center; gap: 10px;
      background: #fff; border: 1px solid var(--border-light);
      border-radius: 40px; padding: 10px 18px;
      flex: 1 1 260px; max-width: 380px; box-shadow: var(--shadow-sm);
    }
    .cm-search ion-icon { color: var(--gold-dark); font-size: 20px; }
    .cm-search input { border: none; outline: none; width: 100%; font-family: var(--ff-body); font-size: 0.95rem; background: transparent; color: var(--text-dark); }
    .cm-filter {
      border: 1px solid var(--border-light); background: #fff; color: var(--text-dark);
      border-radius: 40px; padding: 10px 16px; font-family: var(--ff-body); font-size: 0.9rem; cursor: pointer;
    }

    .cm-table-wrap { background: #fff; border: 1px solid var(--border-light); border-radius: 20px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .cm-table { width: 100%; border-collapse: collapse; }
    .cm-table thead th {
      text-align: left; padding: 15px 18px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;
      color: var(--text-muted); background: #faf8f3; border-bottom: 1px solid var(--border-light); white-space: nowrap;
    }
    .cm-table tbody td { padding: 15px 18px; border-bottom: 1px solid var(--border-light); font-size: 0.92rem; color: var(--text-dark); vertical-align: middle; }
    .cm-table tbody tr:last-child td { border-bottom: none; }
    .cm-table tbody tr:hover { background: #fdfbf6; }

    .cm-name { font-weight: 600; }
    .cm-contact { color: var(--text-muted); font-size: 0.85rem; display: flex; flex-direction: column; gap: 2px; }
    .cm-contact a { color: var(--gold-dark); text-decoration: none; }
    .cm-contact a:hover { text-decoration: underline; }

    .cm-badge { display: inline-block; padding: 4px 12px; border-radius: 30px; font-size: 0.72rem; font-weight: 700; text-transform: capitalize; }
    .cm-badge.type { background: rgba(55,24,1,0.08); color: var(--prussian-blue); }
    .cm-badge.status-active { background: rgba(25,135,84,0.14); color: var(--success); }
    .cm-badge.status-lead   { background: rgba(255,193,7,0.2); color: #997404; }
    .cm-badge.status-vip    { background: var(--gold-gradient); color: var(--prussian-blue); }
    .cm-badge.status-inactive { background: rgba(108,94,78,0.14); color: var(--text-muted); }

    .cm-row-actions { display: flex; gap: 8px; }
    .cm-icon-btn {
      border: none; background: #f3efe6; color: var(--text-muted);
      width: 34px; height: 34px; border-radius: 10px; cursor: pointer;
      display: inline-flex; align-items: center; justify-content: center; font-size: 17px; transition: 0.2s;
    }
    .cm-icon-btn.edit:hover { background: var(--gold-gradient); color: var(--prussian-blue); }
    .cm-icon-btn.del:hover { background: var(--danger); color: #fff; }

    .cm-empty, .cm-loading { padding: 50px 20px; text-align: center; color: var(--text-muted); }

    /* Modal */
    .cm-modal { position: fixed; inset: 0; background: rgba(20,14,4,0.72); backdrop-filter: blur(6px); display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 4vh 16px; z-index: 1000; }
    .cm-modal.open { display: flex; }
    .cm-modal-card { background: #fff; border-radius: 24px; width: 100%; max-width: 680px; margin: auto; box-shadow: 0 30px 60px rgba(0,0,0,0.35); animation: cmPop .25s ease; }
    @keyframes cmPop { from { opacity:0; transform: translateY(14px); } to { opacity:1; transform: translateY(0); } }
    .cm-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 22px 26px; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; background: #fff; border-radius: 24px 24px 0 0; z-index: 2; }
    .cm-modal-head h3 { font-family: var(--ff-display); font-size: 1.6rem; font-weight: 600; color: var(--text-dark); }
    .cm-close { background: #f3efe6; border: none; width: 38px; height: 38px; border-radius: 50%; cursor: pointer; font-size: 22px; color: var(--text-muted); display: flex; align-items: center; justify-content: center; transition: .2s; }
    .cm-close:hover { background: var(--danger); color: #fff; }

    .cm-form { padding: 24px 26px 28px; display: flex; flex-direction: column; gap: 16px; }
    .cm-field { display: flex; flex-direction: column; gap: 6px; }
    .cm-field label { font-size: 0.85rem; font-weight: 600; color: var(--text-dark); }
    .cm-field .req { color: var(--danger); }
    .cm-input, .cm-select, .cm-textarea {
      width: 100%; border: 1px solid var(--border-light); background: #faf8f3; border-radius: 14px;
      padding: 12px 15px; font-family: var(--ff-body); font-size: 0.95rem; color: var(--text-dark); outline: none; transition: .2s;
    }
    .cm-input:focus, .cm-select:focus, .cm-textarea:focus { border-color: var(--gold); background: #fff; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
    .cm-textarea { min-height: 90px; resize: vertical; }
    .cm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .cm-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 6px; }
    .cm-save, .cm-cancel { border-radius: 40px; padding: 11px 26px; font-weight: 600; font-family: var(--ff-body); cursor: pointer; font-size: 0.9rem; }
    .cm-save { background: var(--gold-gradient); color: var(--prussian-blue); border: none; }
    .cm-save:hover { box-shadow: 0 6px 16px rgba(212,175,55,0.4); }
    .cm-save:disabled { opacity: .6; cursor: not-allowed; }
    .cm-cancel { background: transparent; border: 1px solid var(--border-light); color: var(--text-muted); }
    .cm-cancel:hover { border-color: var(--danger); color: var(--danger); }

    @media (max-width: 720px) {
      .cm-grid-2 { grid-template-columns: 1fr; }
      .cm-hide-sm { display: none; }
    }
  </style>
</head>
<body class="admin-app">
<?php $activeNav = 'customers'; ?>
<div class="dashboard admin-dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="main-content">
    <header class="admin-topbar">
      <div class="admin-header-actions--lg">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><ion-icon name="menu-outline"></ion-icon></button>
        <h1 class="admin-page-title">Customers</h1>
      </div>
      <div class="admin-header-actions">
        <button class="admin-btn-outline" id="exportBtn"><ion-icon name="download-outline"></ion-icon> Export CSV</button>
        <button class="admin-btn-primary" id="addCustomerBtn"><ion-icon name="person-add-outline"></ion-icon> Add Customer</button>
      </div>
    </header>

    <div class="cm-content">
      <div class="cm-stats" id="statsRow">
        <div class="cm-stat"><div class="cm-stat-icon"><ion-icon name="people-outline"></ion-icon></div><div><div class="cm-stat-num" id="statTotal">0</div><div class="cm-stat-label">Total Customers</div></div></div>
        <div class="cm-stat"><div class="cm-stat-icon"><ion-icon name="checkmark-circle-outline"></ion-icon></div><div><div class="cm-stat-num" id="statActive">0</div><div class="cm-stat-label">Active</div></div></div>
        <div class="cm-stat"><div class="cm-stat-icon"><ion-icon name="flash-outline"></ion-icon></div><div><div class="cm-stat-num" id="statLeads">0</div><div class="cm-stat-label">Leads</div></div></div>
        <div class="cm-stat"><div class="cm-stat-icon"><ion-icon name="star-outline"></ion-icon></div><div><div class="cm-stat-num" id="statVip">0</div><div class="cm-stat-label">VIP</div></div></div>
      </div>

      <div class="cm-toolbar">
        <div class="cm-search">
          <ion-icon name="search-outline"></ion-icon>
          <input type="text" id="searchInput" placeholder="Search name, email, phone, location...">
        </div>
        <select class="cm-filter" id="statusFilter">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="lead">Lead</option>
          <option value="vip">VIP</option>
          <option value="inactive">Inactive</option>
        </select>
        <select class="cm-filter" id="typeFilter">
          <option value="">All types</option>
          <option value="buyer">Buyer</option>
          <option value="seller">Seller</option>
          <option value="renter">Renter</option>
          <option value="tenant">Tenant</option>
          <option value="landlord">Landlord</option>
          <option value="investor">Investor</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div class="cm-table-wrap">
        <table class="cm-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contact</th>
              <th>Type</th>
              <th>Status</th>
              <th class="cm-hide-sm">Location</th>
              <th class="cm-hide-sm">Added</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="customerRows">
            <tr><td colspan="7" class="cm-loading">Loading customers...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Modal -->
<div id="customerModal" class="cm-modal">
  <div class="cm-modal-card">
    <div class="cm-modal-head">
      <h3 id="modalTitle">Add Customer</h3>
      <button type="button" class="cm-close" id="closeModalBtn" aria-label="Close">&times;</button>
    </div>
    <form id="customerForm" class="cm-form">
      <input type="hidden" id="customerId">

      <div class="cm-field">
        <label for="name">Full Name <span class="req">*</span></label>
        <input type="text" id="name" class="cm-input" placeholder="e.g. Chidi Okafor" required>
      </div>

      <div class="cm-grid-2">
        <div class="cm-field">
          <label for="email">Email</label>
          <input type="email" id="email" class="cm-input" placeholder="name@example.com">
        </div>
        <div class="cm-field">
          <label for="phone">Phone</label>
          <input type="text" id="phone" class="cm-input" placeholder="+234 ...">
        </div>
      </div>

      <div class="cm-field">
        <label for="address">Address</label>
        <input type="text" id="address" class="cm-input" placeholder="Street address">
      </div>

      <div class="cm-grid-2">
        <div class="cm-field">
          <label for="city">City</label>
          <input type="text" id="city" class="cm-input" placeholder="e.g. Owerri">
        </div>
        <div class="cm-field">
          <label for="state">State</label>
          <input type="text" id="state" class="cm-input" placeholder="e.g. Imo State">
        </div>
      </div>

      <div class="cm-grid-2">
        <div class="cm-field">
          <label for="customerType">Customer Type</label>
          <select id="customerType" class="cm-select">
            <option value="buyer">Buyer</option>
            <option value="seller">Seller</option>
            <option value="renter">Renter</option>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="investor">Investor</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="cm-field">
          <label for="status">Status</label>
          <select id="status" class="cm-select">
            <option value="active">Active</option>
            <option value="lead">Lead</option>
            <option value="vip">VIP</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="cm-field">
        <label for="source">Source</label>
        <input type="text" id="source" class="cm-input" placeholder="e.g. Referral, Website, Walk-in" value="manual">
      </div>

      <div class="cm-field">
        <label for="notes">Notes</label>
        <textarea id="notes" class="cm-textarea" placeholder="Preferences, budget, follow-up reminders..."></textarea>
      </div>

      <div class="cm-form-actions">
        <button type="button" class="cm-cancel" id="closeModalBtn2">Cancel</button>
        <button type="submit" class="cm-save" id="saveBtn">Save Customer</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API = 'api/customers.php';
  let customers = [];

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
  }

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
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) throw new Error('Session expired. Please log in again.');
    if (!res.ok || data.success === false) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
  }

  function buildQuery() {
    const params = new URLSearchParams();
    const s = document.getElementById('searchInput').value.trim();
    const st = document.getElementById('statusFilter').value;
    const tp = document.getElementById('typeFilter').value;
    if (s) params.set('search', s);
    if (st) params.set('status', st);
    if (tp) params.set('type', tp);
    const q = params.toString();
    return q ? '?' + q : '';
  }

  async function loadCustomers() {
    const tbody = document.getElementById('customerRows');
    try {
      const data = await apiGet(buildQuery());
      customers = data.customers || [];
      const stats = data.stats || {};
      document.getElementById('statTotal').textContent = stats.total ?? 0;
      document.getElementById('statActive').textContent = stats.active ?? 0;
      document.getElementById('statLeads').textContent = stats.leads ?? 0;
      document.getElementById('statVip').textContent = stats.vip ?? 0;
      renderRows();
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="7" class="cm-empty">${esc(err.message)}</td></tr>`;
    }
  }

  function fmtDate(s) {
    if (!s) return '—';
    const d = new Date(s.replace(' ', 'T'));
    return isNaN(d) ? '—' : d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function renderRows() {
    const tbody = document.getElementById('customerRows');
    if (!customers.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="cm-empty">No customers found. Click “Add Customer” to create one.</td></tr>`;
      return;
    }
    tbody.innerHTML = customers.map(c => {
      const loc = [c.city, c.state].filter(Boolean).join(', ') || '—';
      const contact = [];
      if (c.email) contact.push(`<a href="mailto:${esc(c.email)}">${esc(c.email)}</a>`);
      if (c.phone) contact.push(`<a href="tel:${esc(c.phone)}">${esc(c.phone)}</a>`);
      return `
      <tr>
        <td class="cm-name">${esc(c.name)}</td>
        <td><div class="cm-contact">${contact.join('') || '—'}</div></td>
        <td><span class="cm-badge type">${esc(c.customerType)}</span></td>
        <td><span class="cm-badge status-${esc(c.status)}">${esc(c.status)}</span></td>
        <td class="cm-hide-sm">${esc(loc)}</td>
        <td class="cm-hide-sm">${fmtDate(c.createdAt)}</td>
        <td>
          <div class="cm-row-actions">
            <button class="cm-icon-btn edit" data-edit="${c.id}" title="Edit"><ion-icon name="create-outline"></ion-icon></button>
            <button class="cm-icon-btn del" data-del="${c.id}" title="Delete"><ion-icon name="trash-outline"></ion-icon></button>
          </div>
        </td>
      </tr>`;
    }).join('');

    tbody.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openModal(b.getAttribute('data-edit'))));
    tbody.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => removeCustomer(b.getAttribute('data-del'))));
  }

  function openModal(id = null) {
    const form = document.getElementById('customerForm');
    form.reset();
    document.getElementById('customerId').value = '';
    document.getElementById('customerType').value = 'buyer';
    document.getElementById('status').value = 'active';
    document.getElementById('source').value = 'manual';
    document.getElementById('modalTitle').textContent = id ? 'Edit Customer' : 'Add Customer';

    if (id) {
      const c = customers.find(x => String(x.id) === String(id));
      if (c) {
        document.getElementById('customerId').value = c.id;
        document.getElementById('name').value = c.name || '';
        document.getElementById('email').value = c.email || '';
        document.getElementById('phone').value = c.phone || '';
        document.getElementById('address').value = c.address || '';
        document.getElementById('city').value = c.city || '';
        document.getElementById('state').value = c.state || '';
        document.getElementById('customerType').value = c.customerType || 'buyer';
        document.getElementById('status').value = c.status || 'active';
        document.getElementById('source').value = c.source || 'manual';
        document.getElementById('notes').value = c.notes || '';
      }
    }
    document.getElementById('customerModal').classList.add('open');
  }

  function closeModal() { document.getElementById('customerModal').classList.remove('open'); }

  async function removeCustomer(id) {
    const c = customers.find(x => String(x.id) === String(id));
    if (!confirm(`Delete customer "${c ? c.name : ''}"? This cannot be undone.`)) return;
    try {
      await apiPost({ action: 'delete', id: Number(id) });
      showToast('Customer deleted');
      loadCustomers();
    } catch (err) { showToast(err.message, true); }
  }

  async function saveCustomer(e) {
    e.preventDefault();
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true; saveBtn.textContent = 'Saving...';
    try {
      await apiPost({
        id: Number(document.getElementById('customerId').value) || 0,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        city: document.getElementById('city').value,
        state: document.getElementById('state').value,
        customerType: document.getElementById('customerType').value,
        status: document.getElementById('status').value,
        source: document.getElementById('source').value,
        notes: document.getElementById('notes').value
      });
      showToast('Customer saved');
      closeModal();
      loadCustomers();
    } catch (err) {
      showToast(err.message, true);
    } finally {
      saveBtn.disabled = false; saveBtn.textContent = 'Save Customer';
    }
  }

  function exportCsv() {
    if (!customers.length) { showToast('No customers to export', true); return; }
    const headers = ['Name', 'Email', 'Phone', 'Address', 'City', 'State', 'Type', 'Status', 'Source', 'Notes', 'Added'];
    const rows = customers.map(c => [c.name, c.email, c.phone, c.address, c.city, c.state, c.customerType, c.status, c.source, (c.notes || '').replace(/\s+/g, ' '), c.createdAt]);
    const csv = [headers, ...rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `customers-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  let searchTimer;
  document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadCustomers, 300);
  });
  document.getElementById('statusFilter').addEventListener('change', loadCustomers);
  document.getElementById('typeFilter').addEventListener('change', loadCustomers);
  document.getElementById('addCustomerBtn').addEventListener('click', () => openModal());
  document.getElementById('exportBtn').addEventListener('click', exportCsv);
  document.getElementById('closeModalBtn').addEventListener('click', closeModal);
  document.getElementById('closeModalBtn2').addEventListener('click', closeModal);
  document.getElementById('customerModal').addEventListener('click', e => {
    if (e.target === document.getElementById('customerModal')) closeModal();
  });
  document.getElementById('customerForm').addEventListener('submit', saveCustomer);

  loadCustomers();
</script>
</body>
</html>
