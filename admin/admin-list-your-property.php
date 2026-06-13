<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/site_paths.php';

$isSellPropertyPage = !empty($_SELL_PROPERTY_PAGE);
$activeNav = 'listings';
$pageTitle = $isSellPropertyPage
    ? 'Sell Your Property Submissions | Biver Royalty Homes Admin'
    : 'List Your Property Submissions | Biver Royalty Homes Admin';
$pageHeading = $isSellPropertyPage ? 'Sell Your Property' : 'List Your Property';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$siteBase = siteRootPath();
$pageStylesheet = '../assets/css/admin-list-your-property.css';

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
      <h1 class="page-title"><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="stats-row">
        <div class="stat-pill"><strong id="statTotal">—</strong><span>Total</span></div>
        <div class="stat-pill pending"><strong id="statPending">—</strong><span>Pending</span></div>
        <div class="stat-pill"><strong id="statApproved">—</strong><span>Approved</span></div>
        <div class="stat-pill"><strong id="statRejected">—</strong><span>Rejected</span></div>
      </div>

      <div class="toolbar">
        <input type="search" id="searchInput" placeholder="Search title, owner, location...">
        <select id="statusFilter">
          <option value="">All statuses</option>
          <option value="pending" selected>Pending review</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <button type="button" id="refreshBtn" class="admin-btn-outline">Refresh</button>
      </div>

      <div id="submissionsGrid" class="submissions-grid">
        <div class="loader">Loading submissions...</div>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="detailModal" aria-hidden="true">
  <div class="modal-box">
    <button type="button" class="modal-close" id="closeModal" aria-label="Close">&times;</button>
    <h3 id="modalTitle">Submission Details</h3>
    <div id="modalBody"></div>
    <textarea id="adminNotes" placeholder="Admin notes (required for rejection)"></textarea>
    <div class="modal-actions">
      <button type="button" class="admin-btn-primary" id="saveBtn">Save Changes</button>
      <button type="button" class="admin-btn-primary" id="approveBtn">Approve & Publish</button>
      <button type="button" class="admin-btn-outline" id="pendingBtn">Mark Pending</button>
      <button type="button" class="admin-btn-danger" id="rejectBtn">Reject</button>
      <button type="button" class="admin-btn-outline" id="deleteBtn">Delete</button>
    </div>
  </div>
</div>

<script>
(function() {
  const API = 'api/list-your-property.php';
  const SITE_BASE = <?= json_encode($siteBase, JSON_UNESCAPED_SLASHES) ?>;
  let submissions = [];
  let current = null;
  let editKeepImages = [];
  let editMediaUrls = [];
  let editRemoveVideo = false;
  let editNewImages = new DataTransfer();
  let editNewVideo = null;


  function toast(msg, isError = false) {
    document.querySelector('.toast')?.remove();
    const el = document.createElement('div');
    el.className = 'toast' + (isError ? ' error' : '');
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
  }

  async function api(method, url, body) {
    const opts = { method, credentials: 'same-origin', headers: {} };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed');
    return data;
  }

  function formatPrice(price, type) {
    const formatted = '\u20A6' + Number(price).toLocaleString();
    return type === 'rent' ? formatted + '/month' : formatted;
  }

  function mediaUrl(path) {
    if (!path) return 'https://placehold.co/600x400?text=Property';
    if (/^https?:\/\//i.test(path)) return path;
    if (path.startsWith('/')) return path;
    const normalized = path.replace(/^\/+/, '');
    return (SITE_BASE ? SITE_BASE + '/' : '../') + normalized;
  }

  function allImages(item) {
    if (Array.isArray(item.images) && item.images.length) {
      return item.images;
    }
    const urls = [];
    if (item.imageUrl) urls.push(item.imageUrl);
    if (Array.isArray(item.galleryUrls)) urls.push(...item.galleryUrls);
    return urls;
  }

  function fieldValue(id, fallback = '') {
    const el = document.getElementById(id);
    return el ? el.value.trim() : fallback;
  }

  function renderEditMediaGallery() {
    const container = document.getElementById('editMediaGallery');
    if (!container) return;

    if (!editKeepImages.length) {
      container.innerHTML = '<div class="detail-row">No images kept. Add at least one before saving.</div>';
      return;
    }

    container.innerHTML = editKeepImages.map((path, index) => `
      <div class="media-tile">
        <img src="${mediaUrl(editMediaUrls[index] || path)}" alt="Property image ${index + 1}">
        <button type="button" class="media-remove" data-remove-image="${index}" aria-label="Remove image">&times;</button>
      </div>
    `).join('');

    container.querySelectorAll('[data-remove-image]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const index = Number(btn.dataset.removeImage);
        editKeepImages.splice(index, 1);
        editMediaUrls.splice(index, 1);
        renderEditMediaGallery();
      });
    });
  }

  function renderNewMediaPreview() {
    const imageWrap = document.getElementById('editNewImagesPreview');
    const videoWrap = document.getElementById('editNewVideoPreview');
    if (imageWrap) {
      imageWrap.innerHTML = Array.from(editNewImages.files).map((file, index) => `
        <div class="new-media-item">
          <span>${escapeHtml(file.name)}</span>
          <button type="button" data-remove-new-image="${index}" aria-label="Remove">&times;</button>
        </div>
      `).join('');
      imageWrap.querySelectorAll('[data-remove-new-image]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const next = new DataTransfer();
          Array.from(editNewImages.files).forEach((file, i) => {
            if (i !== Number(btn.dataset.removeNewImage)) next.items.add(file);
          });
          editNewImages = next;
          renderNewMediaPreview();
        });
      });
    }
    if (videoWrap) {
      videoWrap.innerHTML = editNewVideo
        ? `<div class="new-media-item"><span>${escapeHtml(editNewVideo.name)}</span><button type="button" id="removeNewVideoBtn" aria-label="Remove">&times;</button></div>`
        : '';
      document.getElementById('removeNewVideoBtn')?.addEventListener('click', () => {
        editNewVideo = null;
        renderNewMediaPreview();
      });
    }
  }

  function renderEditForm(item) {
    const stored = Array.isArray(item.storedImages) ? item.storedImages : [];
    const urls = allImages(item);
    editKeepImages = [...stored];
    editMediaUrls = urls.length ? [...urls] : stored.map((path) => mediaUrl(path));
    editRemoveVideo = false;
    editNewImages = new DataTransfer();
    editNewVideo = null;

    return `
      <form id="editSubmissionForm" class="edit-form" onsubmit="return false;">
        <div class="edit-section-title">Photos & video</div>
        <div id="editMediaGallery" class="media-gallery"></div>
        <div class="media-upload-row">
          <label>Add images <input type="file" id="editAddImages" accept="image/*" multiple></label>
          <label>Add/replace video <input type="file" id="editAddVideo" accept="video/*"></label>
        </div>
        <div id="editNewImagesPreview" class="new-media-preview"></div>
        <div id="editNewVideoPreview" class="new-media-preview"></div>
        <div id="editVideoSection">
          ${item.storedVideo && !editRemoveVideo ? `<video src="${mediaUrl(item.videoUrl)}" controls class="admin-video-preview admin-video-preview--spaced"></video>` : ''}
        </div>
        <div id="editVideoControls" class="media-upload-row admin-video-preview--flat">
          ${item.storedVideo && !editRemoveVideo ? `<button type="button" class="admin-btn-outline" id="removeExistingVideoBtn">Remove current video</button>` : ''}
          ${item.storedVideo && editRemoveVideo ? `<span class="admin-remove-video-note">Current video will be removed on save.</span><button type="button" class="admin-btn-outline" id="undoRemoveVideoBtn">Keep video</button>` : ''}
        </div>

        <div class="edit-section-title">Property details</div>
        <div class="edit-grid">
          <div class="edit-field full"><label for="editTitle">Title</label><input id="editTitle" required value="${escapeHtml(item.title || '')}"></div>
          <div class="edit-field"><label for="editPrice">Price (NGN)</label><input id="editPrice" required value="${escapeHtml(String(item.price || ''))}"></div>
          <div class="edit-field"><label for="editListingPurpose">Listing purpose</label>
            <select id="editListingPurpose">
              ${['sale','rent','shortlet','lease'].map((v) => `<option value="${v}" ${ (item.listingPurpose || item.type) === v ? 'selected' : ''}>${v}</option>`).join('')}
            </select>
          </div>
          <div class="edit-field"><label for="editPropertyCategory">Category</label><input id="editPropertyCategory" value="${escapeHtml(item.propertyCategory || '')}"></div>
          <div class="edit-field"><label for="editLocation">Location</label><input id="editLocation" required value="${escapeHtml(item.location || '')}"></div>
          <div class="edit-field"><label for="editPropertyAddress">Address</label><input id="editPropertyAddress" required value="${escapeHtml(item.propertyAddress || '')}"></div>
          <div class="edit-field"><label for="editBedrooms">Bedrooms</label><input id="editBedrooms" type="number" min="0" value="${escapeHtml(String(item.bedrooms ?? 0))}"></div>
          <div class="edit-field"><label for="editBathrooms">Bathrooms</label><input id="editBathrooms" type="number" min="0" value="${escapeHtml(String(item.bathrooms ?? 0))}"></div>
          <div class="edit-field"><label for="editPropertySize">Size</label><input id="editPropertySize" value="${escapeHtml(item.propertySize || '')}"></div>
          <div class="edit-field"><label for="editArea">Area (sqm)</label><input id="editArea" type="number" min="0" value="${escapeHtml(String(item.area ?? 0))}"></div>
          <div class="edit-field"><label for="editOwnershipStatus">Ownership</label><input id="editOwnershipStatus" value="${escapeHtml(item.ownershipStatus || '')}"></div>
          <div class="edit-field full"><label for="editPropertyFeatures">Features</label><input id="editPropertyFeatures" value="${escapeHtml(item.propertyFeatures || '')}"></div>
          <div class="edit-field full"><label for="editDescription">Description</label><textarea id="editDescription" required>${escapeHtml(item.description || '')}</textarea></div>
        </div>

        <div class="edit-section-title">Owner contact</div>
        <div class="edit-grid">
          <div class="edit-field"><label for="editOwnerName">Owner name</label><input id="editOwnerName" required value="${escapeHtml(item.ownerName || '')}"></div>
          <div class="edit-field"><label for="editOwnerPhone">Phone</label><input id="editOwnerPhone" required value="${escapeHtml(item.ownerPhone || '')}"></div>
          <div class="edit-field"><label for="editOwnerEmail">Email</label><input id="editOwnerEmail" type="email" value="${escapeHtml(item.ownerEmail || '')}"></div>
          <div class="edit-field"><label for="editContactMethod">Preferred contact</label>
            <select id="editContactMethod">
              ${['phone','whatsapp','email'].map((v) => `<option value="${v}" ${item.contactMethod === v ? 'selected' : ''}>${v}</option>`).join('')}
            </select>
          </div>
        </div>
        <div class="detail-row"><strong>Status:</strong> ${escapeHtml(item.approvalStatus)}</div>
      </form>
    `;
  }

  function updateVideoSection(item) {
    const section = document.getElementById('editVideoSection');
    const controls = document.getElementById('editVideoControls');
    if (!section || !controls) return;

    if (item.storedVideo && !editRemoveVideo && !editNewVideo) {
      section.innerHTML = `<video src="${mediaUrl(item.videoUrl)}" controls class="admin-video-preview"></video>`;
    } else {
      section.innerHTML = '';
    }

    if (item.storedVideo && !editRemoveVideo && !editNewVideo) {
      controls.innerHTML = `<button type="button" class="admin-btn-outline" id="removeExistingVideoBtn">Remove current video</button>`;
      document.getElementById('removeExistingVideoBtn')?.addEventListener('click', () => {
        editRemoveVideo = true;
        updateVideoSection(item);
      });
    } else if (item.storedVideo && editRemoveVideo && !editNewVideo) {
      controls.innerHTML = `<span class="admin-remove-video-note">Current video will be removed on save.</span> <button type="button" class="admin-btn-outline" id="undoRemoveVideoBtn">Keep video</button>`;
      document.getElementById('undoRemoveVideoBtn')?.addEventListener('click', () => {
        editRemoveVideo = false;
        updateVideoSection(item);
      });
    } else {
      controls.innerHTML = '';
    }
  }

  function bindEditMediaHandlers(item) {
    renderEditMediaGallery();
    renderNewMediaPreview();
    updateVideoSection(item);

    document.getElementById('editAddImages')?.addEventListener('change', (event) => {
      Array.from(event.target.files || []).forEach((file) => editNewImages.items.add(file));
      event.target.value = '';
      renderNewMediaPreview();
    });

    document.getElementById('editAddVideo')?.addEventListener('change', (event) => {
      editNewVideo = event.target.files?.[0] || null;
      if (editNewVideo) editRemoveVideo = false;
      event.target.value = '';
      renderNewMediaPreview();
      updateVideoSection(item);
    });
  }

  function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total ?? 0;
    document.getElementById('statPending').textContent = stats.pending ?? 0;
    document.getElementById('statApproved').textContent = stats.approved ?? 0;
    document.getElementById('statRejected').textContent = stats.rejected ?? 0;
  }

  function renderGrid() {
    const grid = document.getElementById('submissionsGrid');
    if (!submissions.length) {
      grid.innerHTML = '<div class="empty-state">No submissions found for this filter.</div>';
      return;
    }

    grid.innerHTML = submissions.map((item) => {
      const status = item.approvalStatus || 'pending';
      const images = allImages(item);
      const imageCount = images.length;
      return `
        <article class="submission-card">
          <div class="thumb admin-card-thumb" style="--card-img:url('${mediaUrl(images[0] || item.imageUrl)}')">
            <span class="badge badge-${status}">${status}</span>
            ${imageCount > 1 ? `<span class="media-count">${imageCount} photos</span>` : ''}
          </div>
          <div class="submission-body">
            <h4>${escapeHtml(item.title)}</h4>
            <div class="meta">${escapeHtml(item.location || '')}</div>
            <div class="owner">${escapeHtml(item.ownerName || 'Owner')} · ${escapeHtml(item.ownerPhone || '')}</div>
            <div class="price">${formatPrice(item.price, item.type)}</div>
            <div class="card-actions">
              <button type="button" class="view" data-view="${item.id}">Review</button>
              ${status !== 'approved' ? `<button type="button" class="approve" data-approve="${item.id}">Approve</button>` : ''}
              ${status !== 'rejected' ? `<button type="button" class="reject" data-reject="${item.id}">Reject</button>` : ''}
            </div>
          </div>
        </article>`;
    }).join('');

    grid.querySelectorAll('[data-view]').forEach((btn) => btn.addEventListener('click', () => openModal(btn.dataset.view)));
    grid.querySelectorAll('[data-approve]').forEach((btn) => btn.addEventListener('click', () => quickAction('approve', btn.dataset.approve)));
    grid.querySelectorAll('[data-reject]').forEach((btn) => btn.addEventListener('click', () => {
      openModal(btn.dataset.reject);
      document.getElementById('adminNotes').focus();
    }));
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  async function loadSubmissions() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value.trim();
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (search) params.set('search', search);

    try {
      const data = await api('GET', API + (params.toString() ? '?' + params.toString() : ''));
      submissions = data.submissions || [];
      renderStats(data.stats || {});
      renderGrid();
    } catch (err) {
      toast(err.message, true);
      document.getElementById('submissionsGrid').innerHTML = '<div class="empty-state">Unable to load submissions.</div>';
    }
  }

  function openModal(id) {
    current = submissions.find((s) => String(s.id) === String(id));
    if (!current) return;

    document.getElementById('modalTitle').textContent = current.title;
    document.getElementById('adminNotes').value = current.adminNotes || '';
    document.getElementById('modalBody').innerHTML = renderEditForm(current);
    bindEditMediaHandlers(current);
    document.getElementById('detailModal').classList.add('open');
  }

  async function saveSubmission() {
    if (!current) return;

    if (!editKeepImages.length && !editNewImages.files.length) {
      toast('Keep or upload at least one property image.', true);
      return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', String(current.id));
    formData.append('title', fieldValue('editTitle'));
    formData.append('price', fieldValue('editPrice'));
    formData.append('listingPurpose', fieldValue('editListingPurpose'));
    formData.append('propertyCategory', fieldValue('editPropertyCategory'));
    formData.append('location', fieldValue('editLocation'));
    formData.append('propertyAddress', fieldValue('editPropertyAddress'));
    formData.append('bedrooms', fieldValue('editBedrooms', '0'));
    formData.append('bathrooms', fieldValue('editBathrooms', '0'));
    formData.append('propertySize', fieldValue('editPropertySize'));
    formData.append('area', fieldValue('editArea', '0'));
    formData.append('ownershipStatus', fieldValue('editOwnershipStatus'));
    formData.append('propertyFeatures', fieldValue('editPropertyFeatures'));
    formData.append('description', fieldValue('editDescription'));
    formData.append('ownerName', fieldValue('editOwnerName'));
    formData.append('ownerPhone', fieldValue('editOwnerPhone'));
    formData.append('ownerEmail', fieldValue('editOwnerEmail'));
    formData.append('contactMethod', fieldValue('editContactMethod'));
    formData.append('adminNotes', document.getElementById('adminNotes').value.trim());
    formData.append('keepImages', JSON.stringify(editKeepImages));
    formData.append('removeVideo', editRemoveVideo ? '1' : '0');
    Array.from(editNewImages.files).forEach((file) => formData.append('propertyImages[]', file));
    if (editNewVideo) formData.append('propertyVideos[]', editNewVideo);

    try {
      const res = await fetch(API, { method: 'POST', body: formData, credentials: 'same-origin' });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) throw new Error(data.message || 'Save failed');
      toast(data.message || 'Submission updated.');
      if (data.submission) {
        current = data.submission;
        const idx = submissions.findIndex((s) => String(s.id) === String(current.id));
        if (idx >= 0) submissions[idx] = data.submission;
        document.getElementById('adminNotes').value = current.adminNotes || '';
        document.getElementById('modalBody').innerHTML = renderEditForm(current);
        bindEditMediaHandlers(current);
      }
      loadSubmissions();
    } catch (err) {
      toast(err.message, true);
    }
  }

  function closeModal() {
    document.getElementById('detailModal').classList.remove('open');
    current = null;
  }

  async function quickAction(action, id, notes = '') {
    try {
      const res = await api('POST', API, { action, id: Number(id), notes });
      toast(res.message);
      closeModal();
      loadSubmissions();
    } catch (err) {
      toast(err.message, true);
    }
  }

  document.getElementById('closeModal').addEventListener('click', closeModal);
  document.getElementById('detailModal').addEventListener('click', (e) => { if (e.target.id === 'detailModal') closeModal(); });
  document.getElementById('saveBtn').addEventListener('click', saveSubmission);
  document.getElementById('approveBtn').addEventListener('click', () => current && quickAction('approve', current.id, document.getElementById('adminNotes').value.trim()));
  document.getElementById('pendingBtn').addEventListener('click', () => current && quickAction('mark_pending', current.id, document.getElementById('adminNotes').value.trim()));
  document.getElementById('rejectBtn').addEventListener('click', () => {
    if (!current) return;
    const notes = document.getElementById('adminNotes').value.trim();
    if (!notes) { toast('Please provide a rejection reason.', true); return; }
    quickAction('reject', current.id, notes);
  });
  document.getElementById('deleteBtn').addEventListener('click', async () => {
    if (!current || !confirm('Delete this submission permanently?')) return;
    try {
      await api('DELETE', API + '?id=' + current.id);
      toast('Submission deleted.');
      closeModal();
      loadSubmissions();
    } catch (err) {
      toast(err.message, true);
    }
  });

  document.getElementById('refreshBtn').addEventListener('click', loadSubmissions);
  document.getElementById('statusFilter').addEventListener('change', loadSubmissions);
  document.getElementById('searchInput').addEventListener('input', debounce(loadSubmissions, 350));

  function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  }

  loadSubmissions();
})();
</script>
</body>
</html>
