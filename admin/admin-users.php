<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';

AdminPermissions::require(AdminPermissions::PERM_ADMINS);

$activeNav = 'admins';
$pageTitle = 'Admin Users | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-users.css';
$csrfToken = AuthSecurity::generateCsrfToken();
$currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);

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
      <h1 class="page-title">Admin Users</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="users-toolbar">
        <button type="button" class="admin-btn-primary" id="addUserBtn"><ion-icon name="person-add-outline"></ion-icon> Add Admin</button>
        <button type="button" class="admin-btn-outline" id="refreshBtn"><ion-icon name="refresh-outline"></ion-icon> Refresh</button>
      </div>

      <div class="users-table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="usersBody">
            <tr><td colspan="6">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="userModal">
  <div class="modal-box" style="max-width:760px;">
    <span class="modal-close" id="closeModal">&times;</span>
    <h3 id="modalTitle">Add Admin User</h3>
    <form id="userForm" class="user-form-grid">
      <input type="hidden" id="userId">
      <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

      <div class="admin-form-grid-2">
        <div class="form-field">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" required>
        </div>
        <div class="form-field">
          <label for="email">Email</label>
          <input type="email" id="email" required>
        </div>
      </div>

      <div class="admin-form-grid-2">
        <div class="form-field">
          <label for="role">Role</label>
          <select id="role" required></select>
        </div>
        <div class="form-field">
          <label for="password">Password <small id="pwdHint">(required for new users)</small></label>
          <input type="password" id="password" minlength="8" autocomplete="new-password">
        </div>
      </div>

      <div class="custom-perms-toggle">
        <label><input type="checkbox" id="useCustomPerms"> Customize permissions (override role defaults)</label>
      </div>

      <div class="form-field u-hidden" id="permsPanel">
        <label>Permissions</label>
        <div class="perm-grid" id="permGrid"></div>
      </div>

      <div class="modal-actions">
        <button type="submit" class="admin-btn-primary">Save Admin</button>
        <button type="button" class="admin-btn-outline" id="cancelBtn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
(function() {
  const API = 'api/admin-users.php';
  const csrf = document.getElementById('csrfToken').value;
  const currentAdminId = <?= (int) $currentAdminId ?>;
  let roles = {};
  let permissions = {};
  let users = [];

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

  function toast(msg, err) {
    document.querySelector('.admin-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'admin-toast' + (err ? ' error' : '');
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

  function renderRoleSelect(selected) {
    const sel = document.getElementById('role');
    sel.innerHTML = Object.entries(roles).map(([k, v]) =>
      `<option value="${k}"${k === selected ? ' selected' : ''}>${esc(v)}</option>`
    ).join('');
  }

  function renderPermGrid(selected) {
    const grid = document.getElementById('permGrid');
    const set = new Set(selected || []);
    grid.innerHTML = Object.entries(permissions).map(([key, label]) =>
      `<label><input type="checkbox" name="perm" value="${key}"${set.has(key) ? ' checked' : ''}> ${esc(label)}</label>`
    ).join('');
  }

  function getSelectedPerms() {
    return [...document.querySelectorAll('#permGrid input[name="perm"]:checked')].map(cb => cb.value);
  }

  async function loadUsers() {
    const tbody = document.getElementById('usersBody');
    try {
      const data = await api('GET', API);
      users = data.users || [];
      roles = data.roles || {};
      permissions = data.permissions || {};
      renderRoleSelect('viewer');

      if (!users.length) {
        tbody.innerHTML = '<tr><td colspan="6">No admin users found.</td></tr>';
        return;
      }

      tbody.innerHTML = users.map(u => `
        <tr>
          <td><strong>${esc(u.full_name)}</strong></td>
          <td>${esc(u.email)}</td>
          <td><span class="role-badge">${esc(u.role_label)}</span></td>
          <td><span class="user-status ${esc(u.status)}">${esc(u.status)}</span></td>
          <td>${formatDate(u.last_login_at)}</td>
          <td class="row-actions">
            <button type="button" class="admin-btn-outline edit-btn" data-id="${u.id}">Edit</button>
            ${u.is_active
              ? `<button type="button" class="admin-btn-outline suspend-btn" data-id="${u.id}"${u.id === currentAdminId ? ' disabled' : ''}>Suspend</button>`
              : `<button type="button" class="admin-btn-primary reactivate-btn" data-id="${u.id}">Reactivate</button>`}
            <button type="button" class="admin-btn-outline danger delete-btn" data-id="${u.id}"${u.id === currentAdminId ? ' disabled' : ''}>Remove</button>
          </td>
        </tr>`).join('');

      tbody.querySelectorAll('.edit-btn').forEach(btn => btn.addEventListener('click', () => openModal(parseInt(btn.dataset.id, 10))));
      tbody.querySelectorAll('.suspend-btn').forEach(btn => btn.addEventListener('click', () => suspendUser(parseInt(btn.dataset.id, 10))));
      tbody.querySelectorAll('.reactivate-btn').forEach(btn => btn.addEventListener('click', () => reactivateUser(parseInt(btn.dataset.id, 10))));
      tbody.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', () => deleteUser(parseInt(btn.dataset.id, 10))));
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="6">' + esc(e.message) + '</td></tr>';
      toast(e.message, true);
    }
  }

  function openModal(id) {
    const isEdit = id > 0;
    const user = isEdit ? users.find(u => parseInt(u.id, 10) === id) : null;
    document.getElementById('modalTitle').textContent = isEdit ? 'Edit Admin User' : 'Add Admin User';
    document.getElementById('userId').value = isEdit ? String(id) : '';
    document.getElementById('fullName').value = user ? user.full_name : '';
    document.getElementById('email').value = user ? user.email : '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = !isEdit;
    document.getElementById('pwdHint').textContent = isEdit ? '(leave blank to keep current)' : '(required for new users)';
    renderRoleSelect(user ? user.role : 'viewer');
    const useCustom = !!(user && user.has_custom_permissions);
    document.getElementById('useCustomPerms').checked = useCustom;
    document.getElementById('permsPanel').classList.toggle('u-hidden', !useCustom);
    renderPermGrid(user ? user.permissions : []);
    document.getElementById('userModal').classList.add('open');
  }

  function closeModal() {
    document.getElementById('userModal').classList.remove('open');
    document.getElementById('userForm').reset();
  }

  async function suspendUser(id) {
    if (!confirm('Suspend this admin? They will not be able to log in.')) return;
    await api('POST', API, { action: 'suspend', id });
    toast('Admin suspended');
    loadUsers();
  }

  async function reactivateUser(id) {
    await api('POST', API, { action: 'reactivate', id });
    toast('Admin reactivated');
    loadUsers();
  }

  async function deleteUser(id) {
    if (!confirm('Permanently remove this admin user?')) return;
    await api('DELETE', API + '?id=' + id, { id });
    toast('Admin removed');
    loadUsers();
  }

  document.getElementById('addUserBtn').addEventListener('click', () => openModal(0));
  document.getElementById('refreshBtn').addEventListener('click', loadUsers);
  document.getElementById('closeModal').addEventListener('click', closeModal);
  document.getElementById('cancelBtn').addEventListener('click', closeModal);
  document.getElementById('userModal').addEventListener('click', e => { if (e.target.id === 'userModal') closeModal(); });

  document.getElementById('useCustomPerms').addEventListener('change', e => {
    document.getElementById('permsPanel').classList.toggle('u-hidden', !e.target.checked);
    if (e.target.checked && !getSelectedPerms().length) {
      renderPermGrid([]);
    }
  });

  document.getElementById('userForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = parseInt(document.getElementById('userId').value, 10) || 0;
    const payload = {
      action: id > 0 ? 'update' : 'create',
      id,
      full_name: document.getElementById('fullName').value.trim(),
      email: document.getElementById('email').value.trim(),
      role: document.getElementById('role').value,
      password: document.getElementById('password').value,
      use_custom_permissions: document.getElementById('useCustomPerms').checked,
      permissions: getSelectedPerms(),
    };
    try {
      await api('POST', API, payload);
      toast(id > 0 ? 'Admin updated' : 'Admin created');
      closeModal();
      loadUsers();
    } catch (err) {
      toast(err.message, true);
    }
  });

  loadUsers();
})();
</script>
</body>
</html>
