<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/site_paths.php';

$activeNav = 'promo';
$pageTitle = 'Promo Banner | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$siteBase = siteRootPath();
$pageStylesheet = '../assets/css/admin-promo-banner.css';

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
      <h1 class="page-title">Homepage Promo Banner</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>

    <div class="admin-content-pad">
      <div class="promo-layout">
        <div class="panel">
          <h2>Banner Settings</h2>
          <p class="hint">Upload designer fliers for desktop and mobile. The banner appears on the homepage and links visitors to your properties page when clicked.</p>

          <div class="status-row">
            <span class="status-pill off" id="statusPill">Disabled</span>
            <span id="updatedAt" class="admin-updated-at"></span>
            <label class="toggle-wrap">
              <input type="checkbox" id="enabledToggle">
              <span>Show banner on homepage</span>
            </label>
          </div>

          <form id="settingsForm" class="form-grid">
            <div class="form-row-2">
              <div class="form-field">
                <label for="eyebrow">Eyebrow label</label>
                <input type="text" id="eyebrow" name="eyebrow" maxlength="60" placeholder="Limited Time Offer">
              </div>
              <div class="form-field">
                <label for="badgeText">Badge text</label>
                <input type="text" id="badgeText" name="badgeText" maxlength="30" placeholder="Featured">
              </div>
            </div>
            <label class="checkbox-inline">
              <input type="checkbox" id="showBadge" name="showBadge" checked>
              Show badge on flier image
            </label>
            <div class="form-field">
              <label for="headline">Headline</label>
              <input type="text" id="headline" name="headline" maxlength="120" required>
            </div>
            <div class="form-field">
              <label for="subheadline">Subheadline</label>
              <textarea id="subheadline" name="subheadline" maxlength="240"></textarea>
            </div>
            <div class="form-row-2">
              <div class="form-field">
                <label for="ctaLabel">Button label</label>
                <input type="text" id="ctaLabel" name="ctaLabel" maxlength="60">
              </div>
              <div class="form-field">
                <label for="linkPage">Click destination</label>
                <select id="linkPage" name="linkPage">
                  <option value="property">Properties page</option>
                  <option value="list-your-property">List Your Property</option>
                  <option value="contact">Contact</option>
                  <option value="services">Services</option>
                </select>
              </div>
            </div>
            <div class="form-field">
              <label for="altText">Image alt text (accessibility)</label>
              <input type="text" id="altText" name="altText" maxlength="240">
            </div>
            <p class="link-preview">Visitors go to: <strong id="linkPreview">—</strong></p>
            <div class="btn-row">
              <button type="submit" class="admin-btn-primary">Save Settings</button>
            </div>
          </form>

          <hr class="admin-divider">

          <h2 class="admin-section-title">Designer Fliers</h2>
          <p class="hint">Recommended: desktop 1200×520px (landscape), mobile 750×950px (portrait). JPG, PNG, or WebP up to 8 MB.</p>

          <div class="upload-card">
            <h3>Desktop flier</h3>
            <p>Shown on tablets and desktops.</p>
            <img id="desktopPreview" class="upload-preview" alt="Desktop flier preview">
            <div class="upload-meta" id="desktopMeta"></div>
            <div class="upload-actions">
              <input type="file" id="desktopFile" accept="image/jpeg,image/png,image/webp">
              <button type="button" class="admin-btn-primary" id="desktopUploadBtn">Upload Desktop</button>
              <button type="button" class="admin-btn-outline" id="desktopRemoveBtn">Remove</button>
            </div>
          </div>

          <div class="upload-card">
            <h3>Mobile flier</h3>
            <p>Optional — used on phones. Falls back to desktop if empty.</p>
            <img id="mobilePreview" class="upload-preview" alt="Mobile flier preview">
            <div class="upload-meta" id="mobileMeta"></div>
            <div class="upload-actions">
              <input type="file" id="mobileFile" accept="image/jpeg,image/png,image/webp">
              <button type="button" class="admin-btn-primary" id="mobileUploadBtn">Upload Mobile</button>
              <button type="button" class="admin-btn-outline" id="mobileRemoveBtn">Remove</button>
            </div>
          </div>
        </div>

        <div class="panel">
          <h2>Live Preview</h2>
          <p class="hint">Approximate appearance on the homepage.</p>
          <div class="preview-shell">
            <div class="preview-label">
              <span>Homepage banner preview</span>
              <span id="previewMode">Fallback</span>
            </div>
            <div class="preview-frame">
              <div class="preview-mock">
                <div class="preview-mock-header">
                  <div class="preview-mock-eyebrow" id="previewEyebrow">Limited Time Offer</div>
                  <div class="preview-mock-title" id="previewHeadline">New Listings Just Dropped</div>
                  <div class="preview-mock-sub" id="previewSubheadline"></div>
                </div>
                <div class="preview-mock-media" id="previewMedia">
                  <span id="previewPlaceholder">Upload a designer flier to replace this area</span>
                  <img id="previewImage" alt="" hidden>
                  <span class="preview-mock-badge" id="previewBadge" hidden>Featured</span>
                  <span class="preview-mock-cta" id="previewCta">Browse Properties</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const API = 'api/promo-banner.php';
  const SITE_BASE = <?= json_encode($siteBase, JSON_UNESCAPED_SLASHES) ?>;

  const els = {
    statusPill: document.getElementById('statusPill'),
    updatedAt: document.getElementById('updatedAt'),
    enabledToggle: document.getElementById('enabledToggle'),
    settingsForm: document.getElementById('settingsForm'),
    linkPreview: document.getElementById('linkPreview'),
    desktopPreview: document.getElementById('desktopPreview'),
    mobilePreview: document.getElementById('mobilePreview'),
    desktopMeta: document.getElementById('desktopMeta'),
    mobileMeta: document.getElementById('mobileMeta'),
    previewEyebrow: document.getElementById('previewEyebrow'),
    previewHeadline: document.getElementById('previewHeadline'),
    previewSubheadline: document.getElementById('previewSubheadline'),
    previewMedia: document.getElementById('previewMedia'),
    previewImage: document.getElementById('previewImage'),
    previewPlaceholder: document.getElementById('previewPlaceholder'),
    previewBadge: document.getElementById('previewBadge'),
    previewCta: document.getElementById('previewCta'),
    previewMode: document.getElementById('previewMode'),
  };

  let state = null;

  function toast(msg, isError) {
    const node = document.createElement('div');
    node.className = 'toast' + (isError ? ' error' : '');
    node.textContent = msg;
    document.body.appendChild(node);
    setTimeout(() => node.remove(), 3200);
  }

  function formatBytes(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  function pageUrl(name) {
    const base = SITE_BASE || '';
    return (base ? base : '') + '/' + String(name).replace(/^\//, '');
  }

  function bindPreviewFields() {
    const map = [
      ['eyebrow', els.previewEyebrow],
      ['headline', els.previewHeadline],
      ['subheadline', els.previewSubheadline],
      ['ctaLabel', els.previewCta],
      ['badgeText', els.previewBadge],
    ];
    map.forEach(([id, target]) => {
      const input = document.getElementById(id);
      input?.addEventListener('input', () => {
        if (id === 'badgeText') {
          target.textContent = input.value || 'Featured';
          updateBadgeVisibility();
          return;
        }
        if (id === 'subheadline') {
          target.textContent = input.value;
          target.style.display = input.value ? 'block' : 'none';
          return;
        }
        target.textContent = input.value;
      });
    });

    document.getElementById('showBadge')?.addEventListener('change', updateBadgeVisibility);
    document.getElementById('linkPage')?.addEventListener('change', () => {
      els.linkPreview.textContent = pageUrl(document.getElementById('linkPage').value);
    });
  }

  function updateBadgeVisibility() {
    const show = document.getElementById('showBadge')?.checked;
    const text = document.getElementById('badgeText')?.value.trim();
    els.previewBadge.hidden = !(show && text);
    if (text) els.previewBadge.textContent = text;
  }

  function applyState(data) {
    state = data;
    const cfg = data.config || {};
    els.enabledToggle.checked = !!cfg.enabled;
    els.statusPill.textContent = cfg.enabled ? 'Live on homepage' : 'Disabled';
    els.statusPill.className = 'status-pill ' + (cfg.enabled ? 'on' : 'off');
    els.updatedAt.textContent = cfg.updatedAt ? 'Updated ' + new Date(cfg.updatedAt).toLocaleString() : '';

    document.getElementById('eyebrow').value = cfg.eyebrow || '';
    document.getElementById('badgeText').value = cfg.badgeText || '';
    document.getElementById('showBadge').checked = !!cfg.showBadge;
    document.getElementById('headline').value = cfg.headline || '';
    document.getElementById('subheadline').value = cfg.subheadline || '';
    document.getElementById('ctaLabel').value = cfg.ctaLabel || '';
    document.getElementById('linkPage').value = cfg.linkPage || 'property';
    document.getElementById('altText').value = cfg.altText || '';
    els.linkPreview.textContent = data.linkUrl || pageUrl(cfg.linkPage || 'property');

    applyFilePreview('desktop', data.files?.desktop, els.desktopPreview, els.desktopMeta);
    applyFilePreview('mobile', data.files?.mobile, els.mobilePreview, els.mobileMeta);

    els.previewEyebrow.textContent = cfg.eyebrow || '';
    els.previewHeadline.textContent = cfg.headline || '';
    els.previewSubheadline.textContent = cfg.subheadline || '';
    els.previewSubheadline.style.display = cfg.subheadline ? 'block' : 'none';
    els.previewCta.textContent = cfg.ctaLabel || 'Browse Properties';
    els.previewBadge.textContent = cfg.badgeText || 'Featured';
    updateBadgeVisibility();

    const flierUrl = data.flier?.desktop || data.flier?.mobile || null;
    if (flierUrl) {
      els.previewImage.src = flierUrl + '?t=' + Date.now();
      els.previewImage.hidden = false;
      els.previewPlaceholder.hidden = true;
      els.previewMode.textContent = 'Designer flier';
    } else {
      els.previewImage.hidden = true;
      els.previewPlaceholder.hidden = false;
      els.previewMode.textContent = 'Fallback design';
    }
  }

  function applyFilePreview(slot, meta, imgEl, metaEl) {
    if (meta?.exists && meta.url) {
      imgEl.src = meta.url + '?t=' + Date.now();
      imgEl.classList.add('visible');
      metaEl.textContent = formatBytes(meta.size) + (meta.modified ? ' · ' + new Date(meta.modified).toLocaleString() : '');
    } else {
      imgEl.removeAttribute('src');
      imgEl.classList.remove('visible');
      metaEl.textContent = 'No ' + slot + ' flier uploaded yet';
    }
  }

  async function loadState() {
    const res = await fetch(API);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Failed to load');
    applyState(data);
  }

  async function postForm(action, fields, fileField) {
    const form = new FormData();
    form.append('action', action);
    Object.entries(fields).forEach(([k, v]) => form.append(k, v));
    if (fileField?.file) form.append(fileField.name, fileField.file);
    const res = await fetch(API, { method: 'POST', body: form });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Request failed');
    return data;
  }

  els.settingsForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const data = await postForm('save', {
        enabled: els.enabledToggle.checked ? '1' : '0',
        eyebrow: document.getElementById('eyebrow').value,
        badgeText: document.getElementById('badgeText').value,
        showBadge: document.getElementById('showBadge').checked ? '1' : '0',
        headline: document.getElementById('headline').value,
        subheadline: document.getElementById('subheadline').value,
        ctaLabel: document.getElementById('ctaLabel').value,
        linkPage: document.getElementById('linkPage').value,
        altText: document.getElementById('altText').value,
      });
      applyState(data.state);
      toast(data.message || 'Saved');
    } catch (err) {
      toast(err.message, true);
    }
  });

  async function uploadSlot(slot, inputId, fieldName) {
    const input = document.getElementById(inputId);
    if (!input?.files?.[0]) {
      toast('Choose an image first', true);
      return;
    }
    try {
      const data = await postForm('upload', { slot }, { name: fieldName, file: input.files[0] });
      input.value = '';
      applyState(data.state);
      toast(data.message || 'Uploaded');
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function removeSlot(slot) {
    if (!confirm('Remove this flier image?')) return;
    try {
      const data = await postForm('remove', { slot });
      applyState(data.state);
      toast(data.message || 'Removed');
    } catch (err) {
      toast(err.message, true);
    }
  }

  document.getElementById('desktopUploadBtn')?.addEventListener('click', () => uploadSlot('desktop', 'desktopFile', 'flierDesktop'));
  document.getElementById('mobileUploadBtn')?.addEventListener('click', () => uploadSlot('mobile', 'mobileFile', 'flierMobile'));
  document.getElementById('desktopRemoveBtn')?.addEventListener('click', () => removeSlot('desktop'));
  document.getElementById('mobileRemoveBtn')?.addEventListener('click', () => removeSlot('mobile'));


  bindPreviewFields();
  loadState().catch((err) => toast(err.message, true));
})();
</script>
</body>
</html>
