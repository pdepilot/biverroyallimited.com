<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';

$activeNav = 'locations';
$pageTitle = 'Service Areas | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-locations.css';

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
  <style>
    .area-upload {
      border: 2px dashed #d4af37;
      background: rgba(212, 175, 55, 0.06);
      border-radius: 14px;
      padding: 22px 16px;
      text-align: center;
      cursor: pointer;
      transition: 0.2s;
    }
    .area-upload:hover, .area-upload.dragover { background: rgba(212, 175, 55, 0.14); border-color: #b8860b; }
    .area-upload ion-icon { font-size: 36px; color: #b8860b; }
    .area-upload-title { font-weight: 700; color: #2c2418; margin-top: 4px; }
    .area-upload-title span { color: #b8860b; text-decoration: underline; }
    .area-upload-hint { color: #7a6f5e; font-size: 0.78rem; margin-top: 2px; }
    .area-upload-preview { position: relative; width: 100%; max-width: 260px; margin-bottom: 10px; }
    .area-upload-preview img {
      width: 100%; height: 150px; object-fit: cover;
      border-radius: 12px; border: 1px solid #e7ddc7; display: block;
    }
    .area-upload-remove {
      position: absolute; top: 8px; right: 8px;
      width: 26px; height: 26px; border: none; border-radius: 50%;
      background: rgba(0,0,0,0.65); color: #fff; cursor: pointer;
      font-size: 15px; display: flex; align-items: center; justify-content: center;
    }
    .area-upload-remove:hover { background: #dc3545; }
  </style>
</head>
<body>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu">
        <ion-icon name="menu-outline"></ion-icon>
      </button>
      <h1 class="page-title">Service Areas (Homepage)</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad--sm">
      <div class="layout">
        <div class="panel">
          <h2>Section Text</h2>
          <p class="hint">Edit the intro paragraph and call-to-action shown above and below the area cards on the homepage.</p>
          <form id="sectionForm" class="form-grid">
            <div class="form-field">
              <label for="intro">Intro paragraph</label>
              <textarea id="intro" rows="3"></textarea>
            </div>
            <div class="form-field">
              <label for="ctaText">CTA text</label>
              <input type="text" id="ctaText">
            </div>
            <div class="form-row-2">
              <div class="form-field">
                <label for="ctaLabel">CTA button label</label>
                <input type="text" id="ctaLabel">
              </div>
              <div class="form-field">
                <label for="ctaLink">CTA link</label>
                <input type="text" id="ctaLink" placeholder="contact.php">
              </div>
            </div>
            <button type="submit" class="btn-gold"><ion-icon name="save-outline"></ion-icon> Save Section Text</button>
          </form>
        </div>

        <div class="panel">
          <div class="toolbar">
            <div>
              <h2>Area Cards</h2>
              <p class="status-text" id="statusText">Loading...</p>
            </div>
            <button type="button" class="btn-gold" id="addAreaBtn"><ion-icon name="add-outline"></ion-icon> Add Area</button>
          </div>
          <div id="areasGrid" class="areas-admin-grid"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="areaModal" class="modal" aria-hidden="true">
  <div class="modal-card">
    <h3 id="modalTitle">Add Service Area</h3>
    <form id="areaForm" class="form-grid">
      <input type="hidden" id="areaId">
      <div class="form-row-2">
        <div class="form-field">
          <label for="title">Area name</label>
          <input type="text" id="title" required maxlength="120">
        </div>
        <div class="form-field">
          <label for="tag">Badge tag</label>
          <input type="text" id="tag" maxlength="40" placeholder="Popular">
        </div>
      </div>
      <div class="form-field">
        <label>Area Image</label>
        <div id="areaImagePreview" class="area-upload-preview" hidden>
          <img id="areaImagePreviewImg" src="" alt="Area image preview">
          <button type="button" class="area-upload-remove" id="areaImageRemove" aria-label="Remove image">&times;</button>
        </div>
        <div class="area-upload" id="areaUploadZone">
          <ion-icon name="cloud-upload-outline"></ion-icon>
          <div class="area-upload-title">Click to upload or <span>browse</span></div>
          <div class="area-upload-hint">JPG, PNG, WEBP or GIF · max 5MB</div>
          <input type="file" id="areaImage" accept="image/*" hidden>
        </div>
      </div>
      <div class="form-field">
        <label for="description">Description</label>
        <textarea id="description" required rows="3"></textarea>
      </div>
      <div class="form-row-2">
        <div class="form-field">
          <label for="meta1Icon">Meta 1 icon (ion-icon name)</label>
          <input type="text" id="meta1Icon" value="home-outline">
        </div>
        <div class="form-field">
          <label for="meta1Text">Meta 1 text</label>
          <input type="text" id="meta1Text">
        </div>
      </div>
      <div class="form-row-2">
        <div class="form-field">
          <label for="meta2Icon">Meta 2 icon</label>
          <input type="text" id="meta2Icon" value="star-outline">
        </div>
        <div class="form-field">
          <label for="meta2Text">Meta 2 text</label>
          <input type="text" id="meta2Text">
        </div>
      </div>
      <div class="form-row-2">
        <div class="form-field">
          <label for="linkUrl">View listings link</label>
          <input type="text" id="linkUrl" value="property.php">
        </div>
        <div class="form-field">
          <label for="sortOrder">Sort order</label>
          <input type="number" id="sortOrder" value="0" min="0">
        </div>
      </div>
      <label class="checkbox-inline"><input type="checkbox" id="isPublished" checked> Published on homepage</label>
      <div class="modal-actions">
        <button type="button" class="btn-outline" id="closeModalBtn">Cancel</button>
        <button type="submit" class="btn-gold">Save Area</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API = 'api/locations.php';
  let areas = [];
  let newAreaImage = null; // File selected for upload

  async function apiPost(payload) {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
  }

  async function apiForm(formData) {
    const res = await fetch(API, { method: 'POST', credentials: 'same-origin', body: formData });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data.message || 'Request failed');
    return data;
  }

  function showToast(msg, isError = false) {
    document.querySelector('.toast')?.remove();
    const toast = document.createElement('div');
    toast.className = 'toast' + (isError ? ' error' : '');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"]/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
  }

  async function loadAreas() {
    try {
      const res = await fetch(API, { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load');
      areas = data.areas || [];
      const section = data.section || {};
      document.getElementById('intro').value = section.intro || '';
      document.getElementById('ctaText').value = section.ctaText || '';
      document.getElementById('ctaLabel').value = section.ctaLabel || '';
      document.getElementById('ctaLink').value = section.ctaLink || 'contact.php';
      document.getElementById('statusText').textContent = `${areas.length} area(s) · ${areas.filter(a => a.isPublished).length} published`;
      renderAreas();
    } catch (err) {
      showToast(err.message, true);
      document.getElementById('statusText').textContent = 'Could not load areas';
    }
  }

  function renderAreas() {
    const grid = document.getElementById('areasGrid');
    if (!areas.length) {
      grid.innerHTML = '<p class="admin-text-muted">No service areas yet. Add your first neighborhood card.</p>';
      return;
    }
    grid.innerHTML = areas.map((area) => `
      <article class="area-admin-card">
        <img src="${escapeHtml(area.imageUrl)}" alt="${escapeHtml(area.title)}" loading="lazy">
        <div class="area-admin-body">
          <span class="area-tag">${escapeHtml(area.tag || 'Area')}</span>
          <h3>${escapeHtml(area.title)}${area.isPublished ? '' : ' <small class="admin-hidden-badge">(hidden)</small>'}</h3>
          <p>${escapeHtml(area.description)}</p>
          <div class="card-actions">
            <button type="button" data-edit="${area.id}">Edit</button>
            <button type="button" class="delete" data-delete="${area.id}">Delete</button>
          </div>
        </div>
      </article>
    `).join('');

    grid.querySelectorAll('[data-edit]').forEach((btn) => {
      btn.addEventListener('click', () => openModal(Number(btn.dataset.edit)));
    });
    grid.querySelectorAll('[data-delete]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        if (!confirm('Delete this service area?')) return;
        try {
          await apiPost({ action: 'delete', id: Number(btn.dataset.delete) });
          showToast('Area deleted');
          loadAreas();
        } catch (err) {
          showToast(err.message, true);
        }
      });
    });
  }

  function openModal(id = null) {
    const modal = document.getElementById('areaModal');
    const area = id ? areas.find((a) => a.id === id) : null;
    document.getElementById('modalTitle').textContent = area ? 'Edit Service Area' : 'Add Service Area';
    document.getElementById('areaId').value = area ? area.id : '';
    document.getElementById('title').value = area?.title || '';
    document.getElementById('tag').value = area?.tag || '';
    newAreaImage = null;
    document.getElementById('areaImage').value = '';
    setImagePreview(area?.imageUrl || '');
    document.getElementById('description').value = area?.description || '';
    document.getElementById('meta1Icon').value = area?.meta1Icon || 'home-outline';
    document.getElementById('meta1Text').value = area?.meta1Text || '';
    document.getElementById('meta2Icon').value = area?.meta2Icon || 'star-outline';
    document.getElementById('meta2Text').value = area?.meta2Text || '';
    document.getElementById('linkUrl').value = area?.linkUrl || 'property.php';
    document.getElementById('sortOrder').value = area?.sortOrder ?? 0;
    document.getElementById('isPublished').checked = area ? !!area.isPublished : true;
    modal.classList.add('open');
  }

  function closeModal() {
    document.getElementById('areaModal').classList.remove('open');
  }

  document.getElementById('sectionForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await apiPost({
        action: 'save_section',
        intro: document.getElementById('intro').value,
        ctaText: document.getElementById('ctaText').value,
        ctaLabel: document.getElementById('ctaLabel').value,
        ctaLink: document.getElementById('ctaLink').value
      });
      showToast('Section text saved');
    } catch (err) {
      showToast(err.message, true);
    }
  });

  function setImagePreview(url) {
    const wrap = document.getElementById('areaImagePreview');
    const img = document.getElementById('areaImagePreviewImg');
    if (url) {
      img.src = url;
      wrap.hidden = false;
    } else {
      img.src = '';
      wrap.hidden = true;
    }
  }

  const areaUploadZone = document.getElementById('areaUploadZone');
  const areaImageInput = document.getElementById('areaImage');
  areaUploadZone?.addEventListener('click', () => areaImageInput.click());
  areaImageInput?.addEventListener('change', () => {
    const file = areaImageInput.files[0];
    if (file) {
      newAreaImage = file;
      setImagePreview(URL.createObjectURL(file));
    }
  });
  ['dragover', 'dragenter'].forEach(ev => areaUploadZone?.addEventListener(ev, e => { e.preventDefault(); areaUploadZone.classList.add('dragover'); }));
  ['dragleave', 'drop'].forEach(ev => areaUploadZone?.addEventListener(ev, e => { e.preventDefault(); areaUploadZone.classList.remove('dragover'); }));
  areaUploadZone?.addEventListener('drop', e => {
    const file = [...e.dataTransfer.files].find(f => f.type.startsWith('image/'));
    if (file) { newAreaImage = file; setImagePreview(URL.createObjectURL(file)); }
  });
  document.getElementById('areaImageRemove')?.addEventListener('click', () => {
    newAreaImage = null;
    areaImageInput.value = '';
    setImagePreview('');
  });

  document.getElementById('areaForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('areaId').value;
    try {
      const fd = new FormData();
      fd.append('action', 'save');
      if (id) fd.append('id', id);
      fd.append('title', document.getElementById('title').value);
      fd.append('tag', document.getElementById('tag').value);
      fd.append('description', document.getElementById('description').value);
      fd.append('meta1Icon', document.getElementById('meta1Icon').value);
      fd.append('meta1Text', document.getElementById('meta1Text').value);
      fd.append('meta2Icon', document.getElementById('meta2Icon').value);
      fd.append('meta2Text', document.getElementById('meta2Text').value);
      fd.append('linkUrl', document.getElementById('linkUrl').value);
      fd.append('sortOrder', document.getElementById('sortOrder').value);
      fd.append('isPublished', document.getElementById('isPublished').checked ? '1' : '0');
      if (newAreaImage) fd.append('areaImage', newAreaImage);

      await apiForm(fd);
      showToast(id ? 'Area updated' : 'Area created');
      closeModal();
      loadAreas();
    } catch (err) {
      showToast(err.message, true);
    }
  });

  document.getElementById('addAreaBtn')?.addEventListener('click', () => openModal());
  document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
  document.getElementById('areaModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'areaModal') closeModal();
  });

  loadAreas();
</script>
</body>
</html>
