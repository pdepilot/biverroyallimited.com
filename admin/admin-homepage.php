<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
AdminPermissions::require(AdminPermissions::PERM_CONTENT);

$activeNav = 'homepage';
$pageTitle = 'Homepage Content | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-content.css';
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
      <button type="button" class="menu-toggle" id="menuToggle"><ion-icon name="menu-outline"></ion-icon></button>
      <h1 class="page-title">Homepage Content</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>
    <div class="admin-content-pad">
      <div class="content-tabs">
        <button type="button" class="content-tab active" data-tab="slides">Hero Slides</button>
        <button type="button" class="content-tab" data-tab="stats">Hero Stats</button>
        <button type="button" class="content-tab" data-tab="sections">Section Headings</button>
      </div>

      <div class="panel content-panel active" id="panel-slides">
        <h2>Hero Slideshow</h2>
        <p class="hint">Edit eyebrow, title (HTML allowed for &lt;span class="accent"&gt;), tagline, and background image URL per slide.</p>
        <div id="slidesEditor"></div>
        <button type="button" class="btn-outline" id="addSlideBtn">+ Add Slide</button>
        <button type="button" class="btn-gold" id="saveSlidesBtn" style="margin-left:8px;">Save Slides</button>
      </div>

      <div class="panel content-panel" id="panel-stats">
        <h2>Hero Statistics Bar</h2>
        <div id="statsEditor"></div>
        <button type="button" class="btn-gold" id="saveStatsBtn">Save Stats</button>
      </div>

      <div class="panel content-panel" id="panel-sections">
        <h2>Section Headings</h2>
        <p class="hint">Titles and subtitles for homepage sections (properties, testimonials, and service areas load from their own admin pages).</p>
        <form id="sectionsForm" class="content-form-grid"></form>
        <button type="button" class="btn-gold" id="saveSectionsBtn">Save Section Headings</button>
      </div>
    </div>
  </div>
</div>

<script>
const API = 'api/homepage.php';
let content = { slides: [], stats: [], sections: {} };

function showToast(msg, err = false) {
  document.querySelector('.toast')?.remove();
  const t = document.createElement('div');
  t.className = 'toast' + (err ? ' error' : '');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

document.querySelectorAll('.content-tab').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.content-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.content-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
  };
});

function slideHtml(slide, i) {
  return `<div class="slide-editor" data-index="${i}">
    <div class="form-field"><label>Eyebrow</label><input type="text" class="slide-eyebrow" value="${esc(slide.eyebrow)}"></div>
    <div class="form-field"><label>Title (HTML ok)</label><input type="text" class="slide-title" value="${esc(slide.title)}"></div>
    <div class="form-field"><label>Tagline</label><textarea class="slide-tagline" rows="2">${esc(slide.tagline)}</textarea></div>
    <div class="form-field"><label>Background image URL</label><input type="url" class="slide-bg" value="${esc(slide.bgImage)}"></div>
    <button type="button" class="btn-outline remove-slide">Remove</button>
  </div>`;
}

function statHtml(stat, i) {
  return `<div class="stat-editor" data-index="${i}">
    <div class="form-row-2">
      <div class="form-field"><label>Icon (ion-icon)</label><input type="text" class="stat-icon" value="${esc(stat.icon)}"></div>
      <div class="form-field"><label>Number</label><input type="text" class="stat-num" value="${esc(stat.num)}"></div>
    </div>
    <div class="form-field"><label>Label</label><input type="text" class="stat-label" value="${esc(stat.label)}"></div>
  </div>`;
}

function esc(s) { return String(s ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

function renderSlides() {
  const el = document.getElementById('slidesEditor');
  el.innerHTML = content.slides.map((s, i) => slideHtml(s, i)).join('');
  el.querySelectorAll('.remove-slide').forEach(btn => {
    btn.onclick = (e) => {
      const idx = parseInt(e.target.closest('.slide-editor').dataset.index, 10);
      content.slides.splice(idx, 1);
      renderSlides();
    };
  });
}

function renderStats() {
  document.getElementById('statsEditor').innerHTML = content.stats.map((s, i) => statHtml(s, i)).join('');
}

function renderSections() {
  const form = document.getElementById('sectionsForm');
  const sections = content.sections || {};
  form.innerHTML = Object.entries(sections).map(([key, fields]) => `
    <fieldset style="border:1px solid #eee;padding:12px;border-radius:8px;">
      <legend style="font-weight:600;text-transform:capitalize;">${key}</legend>
      ${Object.entries(fields).map(([field, val]) => `
        <div class="form-field">
          <label>${field}</label>
          <input type="text" data-section="${key}" data-field="${field}" value="${esc(val)}">
        </div>`).join('')}
    </fieldset>`).join('');
}

function collectSlides() {
  return [...document.querySelectorAll('.slide-editor')].map(el => ({
    eyebrow: el.querySelector('.slide-eyebrow').value,
    title: el.querySelector('.slide-title').value,
    tagline: el.querySelector('.slide-tagline').value,
    bgImage: el.querySelector('.slide-bg').value
  }));
}

function collectStats() {
  return [...document.querySelectorAll('.stat-editor')].map(el => ({
    icon: el.querySelector('.stat-icon').value,
    num: el.querySelector('.stat-num').value,
    label: el.querySelector('.stat-label').value
  }));
}

function collectSections() {
  const sections = JSON.parse(JSON.stringify(content.sections || {}));
  document.querySelectorAll('#sectionsForm input[data-section]').forEach(inp => {
    const sec = inp.dataset.section;
    const field = inp.dataset.field;
    if (!sections[sec]) sections[sec] = {};
    sections[sec][field] = inp.value;
  });
  return sections;
}

async function save(partial) {
  const body = {
    slides: partial.slides ?? content.slides,
    stats: partial.stats ?? content.stats,
    sections: partial.sections ?? content.sections
  };
  const res = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(body) });
  const data = await res.json();
  if (!data.success) throw new Error(data.message || 'Save failed');
  content = data.content;
  renderSlides(); renderStats(); renderSections();
  showToast('Saved');
}

document.getElementById('addSlideBtn').onclick = () => {
  content.slides.push({ eyebrow: '', title: '', tagline: '', bgImage: '' });
  renderSlides();
};
document.getElementById('saveSlidesBtn').onclick = () => save({ slides: collectSlides() }).catch(e => showToast(e.message, true));
document.getElementById('saveStatsBtn').onclick = () => save({ stats: collectStats() }).catch(e => showToast(e.message, true));
document.getElementById('saveSectionsBtn').onclick = () => save({ sections: collectSections() }).catch(e => showToast(e.message, true));

fetch(API, { credentials: 'same-origin' }).then(r => r.json()).then(data => {
  if (!data.success) throw new Error(data.message);
  content = data.content;
  renderSlides(); renderStats(); renderSections();
}).catch(e => showToast(e.message, true));
</script>
</body>
</html>
