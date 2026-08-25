<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biver Royalty Homes | Property Manager</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <style>
    /* Property Manager — page-specific styles (inline to always load fresh) */
    .pm-content { padding: 28px; }

    .pm-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }
    .pm-search {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--border-light);
      border-radius: 40px;
      padding: 10px 18px;
      flex: 1 1 260px;
      max-width: 420px;
      box-shadow: var(--shadow-sm);
    }
    .pm-search ion-icon { color: var(--gold-dark); font-size: 20px; }
    .pm-search input {
      border: none;
      outline: none;
      width: 100%;
      font-family: var(--ff-body);
      font-size: 0.95rem;
      color: var(--text-dark);
      background: transparent;
    }

    .pm-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 22px;
    }

    .pm-card {
      background: #fff;
      border-radius: 22px;
      overflow: hidden;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      display: flex;
      flex-direction: column;
    }
    .pm-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-md);
      border-color: var(--gold);
    }
    .pm-card-media {
      height: 200px;
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .pm-price {
      position: absolute;
      bottom: 12px;
      left: 12px;
      background: rgba(30, 21, 8, 0.85);
      color: var(--gold-light);
      padding: 6px 16px;
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.9rem;
      backdrop-filter: blur(4px);
    }
    .pm-status {
      position: absolute;
      top: 12px;
      right: 12px;
      padding: 5px 14px;
      border-radius: 30px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: #fff;
    }
    .pm-status--approved { background: var(--success); }
    .pm-status--pending  { background: var(--warning); color: #4a3b00; }
    .pm-status--rejected { background: var(--danger); }

    .pm-card-body { padding: 18px 20px 20px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .pm-card-title { font-family: var(--ff-display); font-size: 1.35rem; font-weight: 600; color: var(--text-dark); line-height: 1.2; }
    .pm-card-loc { display: flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 0.9rem; }
    .pm-card-loc ion-icon { color: var(--gold-dark); }
    .pm-meta { display: flex; flex-wrap: wrap; gap: 14px; color: var(--text-muted); font-size: 0.85rem; margin-top: 2px; }
    .pm-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .pm-tag { font-size: 0.8rem; font-weight: 600; color: var(--gold-dark); }
    .pm-card-actions { display: flex; gap: 10px; margin-top: auto; padding-top: 12px; }
    .pm-btn {
      flex: 1;
      border: none;
      border-radius: 30px;
      padding: 9px 14px;
      cursor: pointer;
      font-family: var(--ff-body);
      font-weight: 600;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: 0.2s;
    }
    .pm-btn--edit { background: rgba(212,175,55,0.14); color: var(--gold-dark); }
    .pm-btn--edit:hover { background: var(--gold-gradient); color: var(--prussian-blue); }
    .pm-btn--del { background: rgba(220,53,69,0.1); color: var(--danger); }
    .pm-btn--del:hover { background: var(--danger); color: #fff; }

    .pm-loader, .pm-empty { grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .pm-empty { font-size: 1.05rem; }

    /* ---- Modal ---- */
    .pm-modal {
      position: fixed;
      inset: 0;
      background: rgba(20, 14, 4, 0.72);
      backdrop-filter: blur(6px);
      display: none;
      align-items: flex-start;
      justify-content: center;
      overflow-y: auto;
      padding: 4vh 16px;
      z-index: 1000;
    }
    .pm-modal.open { display: flex; }
    .pm-modal-card {
      background: #fff;
      border-radius: 24px;
      width: 100%;
      max-width: 640px;
      margin: auto;
      box-shadow: 0 30px 60px rgba(0,0,0,0.35);
      animation: pmPop 0.25s ease;
    }
    @keyframes pmPop { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .pm-modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 22px 26px;
      border-bottom: 1px solid var(--border-light);
      position: sticky;
      top: 0;
      background: #fff;
      border-radius: 24px 24px 0 0;
      z-index: 2;
    }
    .pm-modal-head h3 { font-family: var(--ff-display); font-size: 1.6rem; font-weight: 600; color: var(--text-dark); }
    .pm-close {
      background: #f3efe6;
      border: none;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 22px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: 0.2s;
    }
    .pm-close:hover { background: var(--danger); color: #fff; }

    .pm-form { padding: 24px 26px 28px; display: flex; flex-direction: column; gap: 16px; }
    .pm-field { display: flex; flex-direction: column; gap: 6px; }
    .pm-field label { font-size: 0.85rem; font-weight: 600; color: var(--text-dark); }
    .pm-field .req { color: var(--danger); }
    .pm-input, .pm-select, .pm-textarea {
      width: 100%;
      border: 1px solid var(--border-light);
      background: #faf8f3;
      border-radius: 14px;
      padding: 12px 15px;
      font-family: var(--ff-body);
      font-size: 0.95rem;
      color: var(--text-dark);
      outline: none;
      transition: 0.2s;
    }
    .pm-input:focus, .pm-select:focus, .pm-textarea:focus {
      border-color: var(--gold);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
    }
    .pm-textarea { min-height: 110px; resize: vertical; }
    .pm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .pm-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

    /* Upload zone */
    .pm-upload {
      border: 2px dashed var(--gold);
      background: rgba(212,175,55,0.06);
      border-radius: 18px;
      padding: 26px 18px;
      text-align: center;
      cursor: pointer;
      transition: 0.2s;
    }
    .pm-upload:hover, .pm-upload.dragover { background: rgba(212,175,55,0.14); border-color: var(--gold-dark); }
    .pm-upload ion-icon { font-size: 42px; color: var(--gold-dark); }
    .pm-upload-title { font-weight: 700; color: var(--text-dark); margin-top: 6px; }
    .pm-upload-title span { color: var(--gold-dark); text-decoration: underline; }
    .pm-upload-hint { color: var(--text-muted); font-size: 0.8rem; margin-top: 4px; }
    .pm-upload input[type="file"] { display: none; }

    .pm-thumbs { display: flex; flex-wrap: wrap; gap: 10px; }
    .pm-thumb {
      position: relative;
      width: 88px;
      height: 88px;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--border-light);
      box-shadow: var(--shadow-sm);
    }
    .pm-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pm-thumb .badge-cover {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      background: rgba(30,21,8,0.8);
      color: var(--gold-light);
      font-size: 0.62rem;
      font-weight: 700;
      text-align: center;
      padding: 2px 0;
      text-transform: uppercase;
    }
    .pm-thumb-remove {
      position: absolute;
      top: 4px; right: 4px;
      width: 22px; height: 22px;
      border: none;
      border-radius: 50%;
      background: rgba(0,0,0,0.65);
      color: #fff;
      cursor: pointer;
      font-size: 13px;
      display: flex; align-items: center; justify-content: center;
      transition: 0.2s;
    }
    .pm-thumb-remove:hover { background: var(--danger); }

    .pm-form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 6px; }
    .pm-save, .pm-cancel {
      border-radius: 40px;
      padding: 11px 26px;
      font-weight: 600;
      font-family: var(--ff-body);
      cursor: pointer;
      font-size: 0.9rem;
    }
    .pm-save { background: var(--gold-gradient); color: var(--prussian-blue); border: none; }
    .pm-save:hover { box-shadow: 0 6px 16px rgba(212,175,55,0.4); }
    .pm-save:disabled { opacity: 0.6; cursor: not-allowed; }
    .pm-cancel { background: transparent; border: 1px solid var(--border-light); color: var(--text-muted); }
    .pm-cancel:hover { border-color: var(--danger); color: var(--danger); }

    /* Media type toggle */
    .pm-media-toggle {
      display: inline-flex;
      background: #f3efe6;
      border-radius: 30px;
      padding: 4px;
      gap: 4px;
      margin-bottom: 4px;
    }
    .pm-mt-btn {
      border: none;
      background: transparent;
      color: var(--text-muted);
      font-family: var(--ff-body);
      font-weight: 600;
      font-size: 0.85rem;
      padding: 8px 18px;
      border-radius: 30px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: 0.2s;
    }
    .pm-mt-btn ion-icon { font-size: 16px; }
    .pm-mt-btn.active { background: var(--gold-gradient); color: var(--prussian-blue); box-shadow: var(--shadow-sm); }
    .pm-media-pane[hidden] { display: none; }

    .pm-video-preview { margin-bottom: 10px; }
    .pm-video-preview video {
      width: 100%;
      max-height: 240px;
      border-radius: 14px;
      background: #000;
      display: block;
    }
    .pm-video-meta {
      display: flex; align-items: center; justify-content: space-between;
      gap: 10px; margin-top: 8px;
    }
    .pm-video-remove {
      border: 1px solid var(--border-light);
      background: rgba(220,53,69,0.08);
      color: var(--danger);
      border-radius: 30px;
      padding: 7px 16px;
      font-weight: 600;
      font-size: 0.82rem;
      cursor: pointer;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .pm-video-remove:hover { background: var(--danger); color: #fff; }

    @media (max-width: 560px) {
      .pm-grid-2, .pm-grid-3 { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="admin-app">
<?php $activeNav = 'properties'; ?>
<div class="dashboard admin-dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <main class="main-content">
    <header class="admin-topbar">
      <div class="admin-header-actions--lg">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><ion-icon name="menu-outline"></ion-icon></button>
        <h1 class="admin-page-title">Property Estates</h1>
      </div>
      <button class="admin-btn-primary" id="addPropertyBtn"><ion-icon name="add-circle-outline"></ion-icon> Add Property</button>
    </header>

    <div class="pm-content">
      <div class="pm-toolbar">
        <div class="pm-search">
          <ion-icon name="search-outline"></ion-icon>
          <input type="text" id="searchInput" placeholder="Search by title or location...">
        </div>
      </div>

      <div id="propertiesContainer" class="pm-grid">
        <div class="pm-loader"><ion-icon name="hourglass-outline"></ion-icon> Loading properties...</div>
      </div>
    </div>
  </main>
</div>

<!-- Modal -->
<div id="propertyModal" class="pm-modal">
  <div class="pm-modal-card">
    <div class="pm-modal-head">
      <h3 id="modalTitle">Add Property</h3>
      <button type="button" class="pm-close" id="closeModalBtn" aria-label="Close">&times;</button>
    </div>
    <form id="propertyForm" class="pm-form">
      <input type="hidden" id="propertyId">

      <div class="pm-field">
        <label for="title">Property Title <span class="req">*</span></label>
        <input type="text" id="title" class="pm-input" placeholder="e.g. Luxury 4-Bedroom Duplex" required>
      </div>

      <div class="pm-grid-2">
        <div class="pm-field">
          <label for="price">Price (₦) <span class="req">*</span></label>
          <input type="number" id="price" class="pm-input" placeholder="e.g. 75000000" required>
        </div>
        <div class="pm-field">
          <label for="type">Listing Type</label>
          <select id="type" class="pm-select">
            <option value="sale">For Sale</option>
            <option value="rent">For Rent</option>
          </select>
        </div>
      </div>

      <div class="pm-field">
        <label for="location">Location <span class="req">*</span></label>
        <input type="text" id="location" class="pm-input" placeholder="e.g. Owerri, Imo State" required>
      </div>

      <div class="pm-grid-3">
        <div class="pm-field">
          <label for="bedrooms">Bedrooms</label>
          <input type="number" id="bedrooms" class="pm-input" min="0" value="2">
        </div>
        <div class="pm-field">
          <label for="bathrooms">Bathrooms</label>
          <input type="number" id="bathrooms" class="pm-input" min="0" value="2">
        </div>
        <div class="pm-field">
          <label for="area">Area (sq ft)</label>
          <input type="number" id="area" class="pm-input" min="0" value="0">
        </div>
      </div>

      <div class="pm-field">
        <label>Property Media</label>
        <div class="pm-media-toggle" role="tablist" aria-label="Choose media type">
          <button type="button" class="pm-mt-btn active" data-media="images"><ion-icon name="images-outline"></ion-icon> Images</button>
          <button type="button" class="pm-mt-btn" data-media="video"><ion-icon name="videocam-outline"></ion-icon> Video</button>
        </div>

        <div id="imagesPane" class="pm-media-pane">
          <div id="imageThumbs" class="pm-thumbs"></div>
          <div class="pm-upload" id="uploadZone">
            <ion-icon name="cloud-upload-outline"></ion-icon>
            <div class="pm-upload-title">Click to upload or <span>browse</span></div>
            <div class="pm-upload-hint">JPG, PNG, WEBP or GIF · max 5MB each · first image is the cover</div>
            <input type="file" id="propertyImages" accept="image/*" multiple>
          </div>
        </div>

        <div id="videoPane" class="pm-media-pane" hidden>
          <div id="videoPreview" class="pm-video-preview"></div>
          <div class="pm-upload" id="videoZone">
            <ion-icon name="videocam-outline"></ion-icon>
            <div class="pm-upload-title">Click to upload or <span>browse</span></div>
            <div class="pm-upload-hint">MP4, WEBM or MOV · max 50MB · one video per property</div>
            <input type="file" id="propertyVideo" accept="video/*">
          </div>
        </div>
      </div>

      <div class="pm-field">
        <label for="description">Description</label>
        <textarea id="description" class="pm-textarea" placeholder="Describe the property..."></textarea>
      </div>

      <div class="pm-field">
        <label for="approvalStatus">Visibility</label>
        <select id="approvalStatus" class="pm-select">
          <option value="approved">Approved (visible on website)</option>
          <option value="pending">Pending (hidden from website)</option>
          <option value="rejected">Rejected (hidden from website)</option>
        </select>
      </div>

      <div class="pm-form-actions">
        <button type="button" class="pm-cancel" id="closeModalBtn2">Cancel</button>
        <button type="submit" class="pm-save" id="saveBtn">Save Property</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API_URL = 'api/properties.php';
  let properties = [];
  let keptImages = [];   // raw stored paths kept when editing
  let newFiles = [];     // File objects newly selected
  let keptVideoDisplay = null; // public URL of existing video (for preview)
  let newVideo = null;         // newly selected video File
  let removeVideo = false;     // flag to delete existing video

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
  }

  async function apiFetch(url, options = {}) {
    const res = await fetch(url, { credentials: 'same-origin', ...options });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) throw new Error('Session expired. Please log in again.');
    if (!res.ok || data.success === false) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
  }

  async function loadProperties() {
    const container = document.getElementById('propertiesContainer');
    try {
      const data = await apiFetch(`${API_URL}?limit=100`);
      properties = data.properties || [];
      renderProperties();
    } catch (err) {
      container.innerHTML = `<div class="pm-empty">${escapeHtml(err.message || 'Could not load properties')}</div>`;
    }
  }

  function renderProperties() {
    const container = document.getElementById('propertiesContainer');
    const term = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const filtered = properties.filter(p =>
      (p.title || '').toLowerCase().includes(term) ||
      (p.location || '').toLowerCase().includes(term)
    );
    if (filtered.length === 0) {
      container.innerHTML = `<div class="pm-empty">No properties found. Click “Add Property” to create one.</div>`;
      return;
    }
    container.innerHTML = filtered.map(prop => {
      const status = prop.approvalStatus || 'pending';
      const cover = prop.imageUrl || 'https://placehold.co/600x400?text=No+Image';
      const priceText = prop.price ? '₦' + Number(prop.price).toLocaleString() : '₦0';
      return `
      <article class="pm-card">
        <div class="pm-card-media" style="background-image:url('${escapeHtml(cover)}')">
          <span class="pm-price">${priceText}${prop.type === 'rent' ? ' / mo' : ''}</span>
          <span class="pm-status pm-status--${escapeHtml(status)}">${escapeHtml(status)}</span>
        </div>
        <div class="pm-card-body">
          <h3 class="pm-card-title">${escapeHtml(prop.title)}</h3>
          <p class="pm-card-loc"><ion-icon name="location-outline"></ion-icon> ${escapeHtml(prop.location)}</p>
          <div class="pm-meta">
            <span><ion-icon name="bed-outline"></ion-icon> ${prop.bedrooms ?? 0} beds</span>
            <span><ion-icon name="water-outline"></ion-icon> ${prop.bathrooms ?? 0} baths</span>
            ${prop.area ? `<span><ion-icon name="resize-outline"></ion-icon> ${prop.area} sq ft</span>` : ''}
          </div>
          <div class="pm-tag">${prop.type === 'sale' ? 'For Sale' : 'For Rent'}</div>
          <div class="pm-card-actions">
            <button class="pm-btn pm-btn--edit" data-edit="${prop._id}"><ion-icon name="create-outline"></ion-icon> Edit</button>
            <button class="pm-btn pm-btn--del" data-del="${prop._id}"><ion-icon name="trash-outline"></ion-icon> Delete</button>
          </div>
        </div>
      </article>`;
    }).join('');

    container.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => openEditModal(b.getAttribute('data-edit'))));
    container.querySelectorAll('[data-del]').forEach(b => b.addEventListener('click', () => {
      if (confirm('Permanently delete this property?')) deleteProperty(b.getAttribute('data-del'));
    }));
  }

  async function deleteProperty(id) {
    try {
      await apiFetch(`${API_URL}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
      showToast('Property deleted');
      loadProperties();
    } catch (err) {
      showToast(err.message || 'Deletion failed', true);
    }
  }

  function openEditModal(id = null) {
    const form = document.getElementById('propertyForm');
    form.reset();
    keptImages = [];
    newFiles = [];
    keptVideoDisplay = null;
    newVideo = null;
    removeVideo = false;
    setMediaTab('images');
    document.getElementById('propertyId').value = '';
    document.getElementById('bedrooms').value = 2;
    document.getElementById('bathrooms').value = 2;
    document.getElementById('area').value = 0;
    document.getElementById('approvalStatus').value = 'approved';
    document.getElementById('modalTitle').innerText = id ? 'Edit Property' : 'Add Property';

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
        document.getElementById('description').value = prop.description || '';
        document.getElementById('approvalStatus').value = prop.approvalStatus || 'approved';
        keptImages = (prop.storedImages || []).slice();
        window.__editDisplay = (prop.images || []).slice();
        keptVideoDisplay = prop.videoUrl || null;
        // Show the video tab first if the property has a video but no images.
        if (keptVideoDisplay && keptImages.length === 0) setMediaTab('video');
      }
    } else {
      window.__editDisplay = [];
    }
    renderThumbs();
    renderVideo();
    document.getElementById('propertyModal').classList.add('open');
  }

  function renderThumbs() {
    const wrap = document.getElementById('imageThumbs');
    const display = window.__editDisplay || [];
    let html = '';

    keptImages.forEach((path, i) => {
      html += `
        <div class="pm-thumb" data-keep="${escapeHtml(path)}">
          <img src="${escapeHtml(display[i] || '')}" alt="">
          ${i === 0 ? '<span class="badge-cover">Cover</span>' : ''}
          <button type="button" class="pm-thumb-remove" data-remove-keep="${escapeHtml(path)}">&times;</button>
        </div>`;
    });

    newFiles.forEach((file, i) => {
      const url = URL.createObjectURL(file);
      const isCover = keptImages.length === 0 && i === 0;
      html += `
        <div class="pm-thumb" data-new="${i}">
          <img src="${url}" alt="">
          ${isCover ? '<span class="badge-cover">Cover</span>' : ''}
          <button type="button" class="pm-thumb-remove" data-remove-new="${i}">&times;</button>
        </div>`;
    });

    wrap.innerHTML = html;

    wrap.querySelectorAll('[data-remove-keep]').forEach(btn => btn.addEventListener('click', () => {
      const path = btn.getAttribute('data-remove-keep');
      keptImages = keptImages.filter(p => p !== path);
      renderThumbs();
    }));
    wrap.querySelectorAll('[data-remove-new]').forEach(btn => btn.addEventListener('click', () => {
      const idx = Number(btn.getAttribute('data-remove-new'));
      newFiles.splice(idx, 1);
      renderThumbs();
    }));
  }

  function setMediaTab(which) {
    document.querySelectorAll('.pm-mt-btn').forEach(b => b.classList.toggle('active', b.dataset.media === which));
    document.getElementById('imagesPane').hidden = which !== 'images';
    document.getElementById('videoPane').hidden = which !== 'video';
  }

  function renderVideo() {
    const wrap = document.getElementById('videoPreview');
    let src = null;
    if (newVideo) {
      src = URL.createObjectURL(newVideo);
    } else if (keptVideoDisplay && !removeVideo) {
      src = keptVideoDisplay;
    }

    if (!src) { wrap.innerHTML = ''; return; }

    wrap.innerHTML = `
      <video src="${escapeHtml(src)}" controls preload="metadata"></video>
      <div class="pm-video-meta">
        <span class="pm-tag">${newVideo ? 'New video selected' : 'Current video'}</span>
        <button type="button" class="pm-video-remove" id="videoRemoveBtn"><ion-icon name="trash-outline"></ion-icon> Remove video</button>
      </div>`;

    document.getElementById('videoRemoveBtn').addEventListener('click', () => {
      if (newVideo) {
        newVideo = null;
        document.getElementById('propertyVideo').value = '';
      } else {
        removeVideo = true;
      }
      renderVideo();
    });
  }

  function closeModal() {
    document.getElementById('propertyModal').classList.remove('open');
  }

  async function saveProperty(event) {
    event.preventDefault();
    const saveBtn = document.getElementById('saveBtn');
    const id = document.getElementById('propertyId').value;

    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('title', document.getElementById('title').value);
    fd.append('price', document.getElementById('price').value);
    fd.append('type', document.getElementById('type').value);
    fd.append('location', document.getElementById('location').value);
    fd.append('bedrooms', document.getElementById('bedrooms').value);
    fd.append('bathrooms', document.getElementById('bathrooms').value);
    fd.append('area', document.getElementById('area').value);
    fd.append('description', document.getElementById('description').value);
    fd.append('approvalStatus', document.getElementById('approvalStatus').value);
    fd.append('keepImages', JSON.stringify(keptImages));
    newFiles.forEach(file => fd.append('propertyImages[]', file));
    if (newVideo) fd.append('propertyVideos[]', newVideo);
    fd.append('removeVideo', removeVideo ? '1' : '0');

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    try {
      await apiFetch(API_URL, { method: 'POST', body: fd });
      showToast(id ? 'Property updated' : 'Property created');
      closeModal();
      loadProperties();
    } catch (err) {
      showToast(err.message || 'Error saving property', true);
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Property';
    }
  }

  function showToast(msg, isError = false) {
    document.querySelector('.admin-toast')?.remove();
    const toast = document.createElement('div');
    toast.className = 'admin-toast' + (isError ? ' error' : '');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
  }

  // Upload zone events
  const uploadZone = document.getElementById('uploadZone');
  const fileInput = document.getElementById('propertyImages');
  uploadZone.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', () => {
    for (const f of fileInput.files) newFiles.push(f);
    fileInput.value = '';
    renderThumbs();
  });
  ['dragover', 'dragenter'].forEach(ev => uploadZone.addEventListener(ev, e => { e.preventDefault(); uploadZone.classList.add('dragover'); }));
  ['dragleave', 'drop'].forEach(ev => uploadZone.addEventListener(ev, e => { e.preventDefault(); uploadZone.classList.remove('dragover'); }));
  uploadZone.addEventListener('drop', e => {
    for (const f of e.dataTransfer.files) { if (f.type.startsWith('image/')) newFiles.push(f); }
    renderThumbs();
  });

  // Media type toggle
  document.querySelectorAll('.pm-mt-btn').forEach(btn => {
    btn.addEventListener('click', () => setMediaTab(btn.dataset.media));
  });

  // Video upload zone events
  const videoZone = document.getElementById('videoZone');
  const videoInput = document.getElementById('propertyVideo');
  videoZone.addEventListener('click', () => videoInput.click());
  videoInput.addEventListener('change', () => {
    const file = videoInput.files[0];
    if (file) { newVideo = file; removeVideo = false; renderVideo(); }
  });
  ['dragover', 'dragenter'].forEach(ev => videoZone.addEventListener(ev, e => { e.preventDefault(); videoZone.classList.add('dragover'); }));
  ['dragleave', 'drop'].forEach(ev => videoZone.addEventListener(ev, e => { e.preventDefault(); videoZone.classList.remove('dragover'); }));
  videoZone.addEventListener('drop', e => {
    const file = [...e.dataTransfer.files].find(f => f.type.startsWith('video/'));
    if (file) { newVideo = file; removeVideo = false; renderVideo(); }
  });

  document.getElementById('addPropertyBtn').addEventListener('click', () => openEditModal());
  document.getElementById('closeModalBtn').addEventListener('click', closeModal);
  document.getElementById('closeModalBtn2').addEventListener('click', closeModal);
  document.getElementById('propertyModal').addEventListener('click', e => {
    if (e.target === document.getElementById('propertyModal')) closeModal();
  });
  document.getElementById('searchInput').addEventListener('input', renderProperties);
  document.getElementById('propertyForm').addEventListener('submit', saveProperty);

  loadProperties();
</script>
</body>
</html>
