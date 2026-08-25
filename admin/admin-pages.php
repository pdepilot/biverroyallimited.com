<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
AdminPermissions::require(AdminPermissions::PERM_CONTENT);

$activeNav = 'pages';
$pageTitle = 'Site Pages | Biver Royalty Homes Admin';
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
      <h1 class="page-title">Services &amp; Terms Pages</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>
    <div class="admin-content-pad">
      <div class="page-actions-top">
        <a class="btn-outline" href="admin-about.php">Edit About Us page</a>
        <a class="btn-outline" href="../terms.php" target="_blank" rel="noopener noreferrer">View Terms page</a>
      </div>
      <div class="content-tabs">
        <button type="button" class="content-tab active" data-page="services">Services Page</button>
        <button type="button" class="content-tab" data-page="terms">Terms &amp; Conditions</button>
      </div>
      <div class="panel">
        <p class="hint" id="pageHint">Edit hero copy, showcase headings, CTA, and service cards.</p>
        <form id="pageForm" class="content-form-grid"></form>
        <div class="toolbar-row" style="margin-top:16px;">
          <button type="button" class="btn-gold" id="savePageBtn">Save Page Content</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const API = 'api/pages.php';
let currentPage = 'services';
let pageContent = {};

function showToast(msg, err = false) {
  document.querySelector('.toast')?.remove();
  const t = document.createElement('div');
  t.className = 'toast' + (err ? ' error' : '');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

function esc(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;');
}

function field(label, path, value, rows = 1) {
  const id = path.replace(/\./g, '_');
  if (rows > 1) {
    return `<div class="form-field"><label for="${id}">${label}</label><textarea id="${id}" data-path="${path}" rows="${rows}">${esc(value)}</textarea></div>`;
  }
  return `<div class="form-field"><label for="${id}">${label}</label><input type="text" id="${id}" data-path="${path}" value="${esc(value)}"></div>`;
}

function renderForm() {
  const c = pageContent;
  const form = document.getElementById('pageForm');
  const hint = document.getElementById('pageHint');

  if (currentPage === 'terms') {
    hint.textContent = 'Edit the Terms & Conditions hero, intro, and legal sections shown on the public terms page.';
    const h = c.hero || {};
    const sections = c.sections || [];
    form.innerHTML = `
      <h3>Hero</h3>
      ${field('Eyebrow', 'hero.eyebrow', h.eyebrow)}
      ${field('Title', 'hero.title', h.title)}
      ${field('Lead', 'hero.lead', h.lead, 2)}
      ${field('Updated label', 'updatedLabel', c.updatedLabel || 'Last updated')}
      ${field('Intro paragraph', 'intro', c.intro || '', 4)}
      <h3>Sections</h3>
      <p class="hint">One section per block. Add or remove as needed.</p>
      <div id="termsSections"></div>
      <button type="button" class="btn-outline" id="addTermSection">+ Add Section</button>
    `;
    const wrap = document.getElementById('termsSections');
    wrap.innerHTML = sections.map((s, i) => `
      <div class="item-editor" data-index="${i}">
        <div class="form-field"><label>Title</label><input type="text" class="term-title" value="${esc(s.title)}"></div>
        <div class="form-field"><label>Content</label><textarea class="term-content" rows="5">${esc(s.content)}</textarea></div>
        <button type="button" class="btn-outline remove-term-section">Remove</button>
      </div>
    `).join('');
    document.getElementById('addTermSection').onclick = () => {
      pageContent.sections = pageContent.sections || [];
      pageContent.sections.push({ title: '', content: '' });
      renderForm();
    };
    wrap.querySelectorAll('.remove-term-section').forEach(btn => {
      btn.onclick = (e) => {
        const idx = +e.target.closest('.item-editor').dataset.index;
        pageContent.sections.splice(idx, 1);
        renderForm();
      };
    });
    return;
  }

  hint.textContent = 'Edit hero text, showcase headings, CTA, and service cards for the Services page.';
  const h = c.hero || {};
  const sh = c.showcase || {};
  const cta = c.cta || {};
  form.innerHTML = `
    <h3>Hero</h3>
    ${field('Badge', 'hero.badge', h.badge)}
    ${field('Title (HTML ok)', 'hero.title', h.title)}
    ${field('Description', 'hero.description', h.description, 3)}
    <h3>Showcase section</h3>
    ${field('Eyebrow', 'showcase.eyebrow', sh.eyebrow)}
    ${field('Title', 'showcase.title', sh.title)}
    <h3>CTA</h3>
    ${field('Title', 'cta.title', cta.title)}
    ${field('Text', 'cta.text', cta.text, 2)}
    ${field('Button label', 'cta.label', cta.label)}
    <h3>Service cards</h3>
    <p class="hint">Edit service cards as JSON (icon, title, description, features array, linkLabel, linkPage).</p>
    <textarea id="cardsJson" rows="12" style="font-family:monospace;font-size:12px;">${esc(JSON.stringify(c.cards || [], null, 2))}</textarea>
  `;
}

function setPath(obj, path, value) {
  const parts = path.split('.');
  let cur = obj;
  for (let i = 0; i < parts.length - 1; i++) {
    if (!cur[parts[i]]) cur[parts[i]] = {};
    cur = cur[parts[i]];
  }
  cur[parts[parts.length - 1]] = value;
}

function collectForm() {
  const out = JSON.parse(JSON.stringify(pageContent));
  document.querySelectorAll('#pageForm [data-path]').forEach(el => {
    setPath(out, el.dataset.path, el.value);
  });
  if (currentPage === 'services') {
    try { out.cards = JSON.parse(document.getElementById('cardsJson').value); }
    catch { throw new Error('Invalid service cards JSON'); }
  }
  if (currentPage === 'terms') {
    out.sections = [...document.querySelectorAll('#termsSections .item-editor')].map(el => ({
      title: el.querySelector('.term-title').value,
      content: el.querySelector('.term-content').value
    }));
  }
  return out;
}

async function loadPage(page) {
  currentPage = page;
  const res = await fetch(`${API}?page=${page}`, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) throw new Error(data.message);
  pageContent = data.content || {};
  renderForm();
}

document.querySelectorAll('.content-tab').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.content-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadPage(btn.dataset.page).catch(e => showToast(e.message, true));
  };
});

document.getElementById('savePageBtn').onclick = async () => {
  try {
    const content = collectForm();
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ page: currentPage, content })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    pageContent = data.content;
    renderForm();
    showToast('Page saved');
  } catch (e) { showToast(e.message, true); }
};

loadPage('services').catch(e => showToast(e.message, true));
</script>
</body>
</html>
