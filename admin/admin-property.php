<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Royal Estates | Property Manager</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <link rel="stylesheet" href="../assets/css/admin-property.css">

  </head>
<body>
<div class="bg-aura"></div>
<?php $activeNav = 'properties'; ?>
<div class="app dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main">
    <div class="top-bar">
      <div class="admin-header-actions--lg">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <h1 class="page-title"><i class="fas fa-crown admin-page-title-icon"></i> Royal Property Estates</h1>
      </div>
      <div class="action-group">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search by title or location...">
        </div>
        <button class="btn-primary" id="addPropertyBtn"><i class="fas fa-plus-circle"></i> Add New Estate</button>
      </div>
    </div>

    <div id="propertiesContainer" class="properties-grid">
      <div class="loader"><i class="fas fa-spinner fa-pulse"></i> Loading majestic properties...</div>
    </div>
  </div>
</div>

<!-- Modal -->
<div id="propertyModal" class="modal-overlay">
  <div class="modal-container">
    <h3 id="modalTitle">✨ Add / Edit Property</h3>
    <form id="propertyForm">
      <input type="hidden" id="propertyId">
      <input type="text" id="title" placeholder="Property Title" required>
      <input type="number" id="price" placeholder="Price (₦)" required>
      <select id="type">
        <option value="sale">For Sale</option>
        <option value="rent">For Rent</option>
      </select>
      <input type="text" id="location" placeholder="Location (e.g., Owerri, Imo State)" required>
      <div class="admin-property-grid-form">
        <input type="number" id="bedrooms" placeholder="Bedrooms" min="0" value="2">
        <input type="number" id="bathrooms" placeholder="Bathrooms" min="0" value="2">
        <input type="number" id="area" placeholder="Area (sq ft)" min="0" value="0">
      </div>
      <input type="text" id="imageUrl" placeholder="Image URL">
      <textarea id="description" placeholder="Property description..."></textarea>
      <select id="approvalStatus">
        <option value="approved">Approved (visible on website)</option>
        <option value="pending">Pending (hidden from website)</option>
        <option value="rejected">Rejected (hidden from website)</option>
      </select>
      <div class="modal-buttons">
        <button type="button" class="btn-outline" id="closeModalBtn">Cancel</button>
        <button type="submit" class="btn-primary">Save Estate</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API_URL = 'api/properties.php';
  let properties = [];

  function buildApiUrl(path, method = 'GET') {
    const match = path.match(/^\/properties(?:\/([^/?]+))?(?:\?(.*))?$/);
    if (!match) return API_URL;

    const id = match[1];
    const query = match[2];
    if (id) return `${API_URL}?id=${encodeURIComponent(id)}`;
    if (query) return `${API_URL}?${query}`;
    return method === 'GET' ? `${API_URL}?limit=100` : API_URL;
  }

  async function apiFetch(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const res = await fetch(buildApiUrl(path, method), {
      method,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', ...options.headers },
      body: options.body
    });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) throw new Error('Unauthorized');
    if (!res.ok || data.success === false) {
      throw new Error(data.message || `HTTP ${res.status}`);
    }
    return data;
  }

  // Load properties
  async function loadProperties() {
    try {
      const data = await apiFetch('/properties?limit=100');
      properties = data.properties || [];
      renderProperties();
    } catch (err) {
      console.error(err);
      showToast(err.message || 'Could not load properties', true);
    }
  }

  // Render cards with search filter
  function renderProperties() {
    const container = document.getElementById('propertiesContainer');
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    let filtered = properties.filter(p => 
      p.title?.toLowerCase().includes(searchTerm) || 
      p.location?.toLowerCase().includes(searchTerm)
    );
    if (filtered.length === 0) {
      container.innerHTML = `<div class="admin-empty-wide">🏰 No royal estates found. Add one now.</div>`;
      return;
    }
    container.innerHTML = filtered.map(prop => {
      const status = prop.approvalStatus || 'pending';
      const statusClass = status === 'approved' ? 'approved' : (status === 'rejected' ? 'rejected' : 'pending');
      return `
      <div class="property-card" data-id="${prop._id}">
        <div class="card-img admin-card-img" style="--card-img:url('${escapeHtml(prop.imageUrl || 'https://placehold.co/600x400?text=Luxury+Estate')}')">
          <div class="price-tag">₦${prop.price?.toLocaleString()} ${prop.type === 'rent' ? '/ month' : ''}</div>
          <span class="status-badge status-${statusClass}">${status}</span>
        </div>
        <div class="card-content">
          <div class="card-title">${escapeHtml(prop.title)}</div>
          <div class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(prop.location)}</div>
          <div class="meta-row">
            <span>${prop.bedrooms ?? 2} beds</span>
            <span>${prop.bathrooms ?? 2} baths</span>
            ${prop.area ? `<span>${prop.area} sq ft</span>` : ''}
          </div>
          <div class="admin-property-meta">${prop.type === 'sale' ? '🏷️ For Sale' : '📄 For Rent'}</div>
          <div class="card-actions">
            <button class="icon-btn edit" data-id="${prop._id}"><i class="fas fa-edit"></i> Edit</button>
            <button class="icon-btn delete" data-id="${prop._id}"><i class="fas fa-trash-alt"></i> Delete</button>
          </div>
        </div>
      </div>`;
    }).join('');
    // attach events
    document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = btn.getAttribute('data-id');
      openEditModal(id);
    }));
    document.querySelectorAll('.delete').forEach(btn => btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const id = btn.getAttribute('data-id');
      if (confirm('Permanently delete this majestic property?')) {
        await deleteProperty(id);
      }
    }));
  }

  function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

  // Delete
  async function deleteProperty(id) {
    try {
      await apiFetch(`/properties/${id}`, { method: 'DELETE' });
      showToast('Property deleted successfully');
      loadProperties();
    } catch (err) {
      showToast('Deletion failed', true);
    }
  }

  // Open Add / Edit modal
  function openEditModal(id = null) {
    const modal = document.getElementById('propertyModal');
    const form = document.getElementById('propertyForm');
    form.reset();
    document.getElementById('propertyId').value = '';
    document.getElementById('bedrooms').value = 2;
    document.getElementById('bathrooms').value = 2;
    document.getElementById('area').value = 0;
    document.getElementById('approvalStatus').value = 'approved';
    document.getElementById('modalTitle').innerText = id ? '✍️ Refine Estate' : '🏆 Add New Property';
    if (id) {
      const prop = properties.find(p => p._id === id);
      if (prop) {
        document.getElementById('propertyId').value = prop._id;
        document.getElementById('title').value = prop.title || '';
        document.getElementById('price').value = prop.price || '';
        document.getElementById('type').value = prop.type || 'sale';
        document.getElementById('location').value = prop.location || '';
        document.getElementById('bedrooms').value = prop.bedrooms ?? 2;
        document.getElementById('bathrooms').value = prop.bathrooms ?? 2;
        document.getElementById('area').value = prop.area ?? 0;
        document.getElementById('imageUrl').value = prop.imageUrl || '';
        document.getElementById('description').value = prop.description || '';
        document.getElementById('approvalStatus').value = prop.approvalStatus || 'approved';
      }
    }
    modal.style.display = 'flex';
  }

  // Save property
  async function saveProperty(event) {
    event.preventDefault();
    const id = document.getElementById('propertyId').value;
    const payload = {
      title: document.getElementById('title').value,
      price: Number(document.getElementById('price').value),
      type: document.getElementById('type').value,
      location: document.getElementById('location').value,
      bedrooms: Number(document.getElementById('bedrooms').value),
      bathrooms: Number(document.getElementById('bathrooms').value),
      area: Number(document.getElementById('area').value),
      imageUrl: document.getElementById('imageUrl').value,
      description: document.getElementById('description').value,
      approvalStatus: document.getElementById('approvalStatus').value
    };
    try {
      if (id) {
        await apiFetch(`/properties/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
        showToast('Estate updated ✨');
      } else {
        await apiFetch('/properties', { method: 'POST', body: JSON.stringify(payload) });
        showToast('New estate listed!');
      }
      closeModal();
      loadProperties();
    } catch (err) {
      showToast('Error saving property', true);
    }
  }

  function closeModal() {
    document.getElementById('propertyModal').style.display = 'none';
  }

  function showToast(msg, isError = false) {
    let toast = document.querySelector('.toast');
    if(toast) toast.remove();
    toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${msg}`;
    toast.style.borderLeftColor = isError ? '#e74c3c' : '#D4AF37';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  // logout
  document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    window.location.href = 'logout.php';
  });

  // search event
  document.getElementById('searchInput')?.addEventListener('input', () => renderProperties());

  // Add property button
  document.getElementById('addPropertyBtn')?.addEventListener('click', () => openEditModal());

  // modal close events
  document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
  document.getElementById('propertyModal')?.addEventListener('click', (e) => {
    if(e.target === document.getElementById('propertyModal')) closeModal();
  });

  // form submission
  document.getElementById('propertyForm')?.addEventListener('submit', saveProperty);

  // PHP session (admin_guard.php) is the authority; do not redirect via external API.
  loadProperties();
</script>
</body>
</html>