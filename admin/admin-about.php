<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
AdminPermissions::require(AdminPermissions::PERM_CONTENT);

$activeNav = 'about';
$pageTitle = 'About Page | Biver Royalty Homes Admin';
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
      <h1 class="page-title">About Us Page</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>
    <div class="admin-content-pad">
      <div class="page-actions-top">
        <a class="btn-outline" href="../about.php" target="_blank" rel="noopener noreferrer">View public About page</a>
        <button type="button" class="btn-gold" id="saveAllBtn">Save All Changes</button>
      </div>
      <p class="hint">Manage every section of the frontend About Us page. Changes appear on the live site after you save.</p>

      <div class="content-tabs">
        <button type="button" class="content-tab active" data-tab="hero">Hero</button>
        <button type="button" class="content-tab" data-tab="narrative">Story</button>
        <button type="button" class="content-tab" data-tab="philosophy">Pillars</button>
        <button type="button" class="content-tab" data-tab="journey">Journey</button>
        <button type="button" class="content-tab" data-tab="values">Values</button>
        <button type="button" class="content-tab" data-tab="team">Team</button>
        <button type="button" class="content-tab" data-tab="cta">CTA</button>
      </div>

      <div class="panel content-panel active" id="panel-hero">
        <h2>Hero</h2>
        <div class="content-form-grid" id="heroForm"></div>
        <h3 style="margin-top:20px;">Hero stats</h3>
        <div id="statsEditor"></div>
        <div class="toolbar-row">
          <button type="button" class="btn-outline" id="addStatBtn">+ Add Stat</button>
        </div>
      </div>

      <div class="panel content-panel" id="panel-narrative">
        <h2>Narrative / Story</h2>
        <div class="content-form-grid" id="narrativeForm"></div>
      </div>

      <div class="panel content-panel" id="panel-philosophy">
        <h2>Philosophy Pillars</h2>
        <p class="hint">Ionicon names without the ion- prefix, e.g. <code>shield-checkmark-outline</code>.</p>
        <div id="philosophyEditor"></div>
        <div class="toolbar-row">
          <button type="button" class="btn-outline" id="addPillarBtn">+ Add Pillar</button>
        </div>
      </div>

      <div class="panel content-panel" id="panel-journey">
        <h2>Journey Timeline</h2>
        <div class="content-form-grid" id="journeyMetaForm"></div>
        <div id="journeyEditor" style="margin-top:16px;"></div>
        <div class="toolbar-row">
          <button type="button" class="btn-outline" id="addJourneyBtn">+ Add Milestone</button>
        </div>
      </div>

      <div class="panel content-panel" id="panel-values">
        <h2>Core Values</h2>
        <div class="content-form-grid" id="valuesMetaForm"></div>
        <div id="valuesEditor" style="margin-top:16px;"></div>
        <div class="toolbar-row">
          <button type="button" class="btn-outline" id="addValueBtn">+ Add Value</button>
        </div>
      </div>

      <div class="panel content-panel" id="panel-team">
        <h2>Team</h2>
        <div class="content-form-grid" id="teamMetaForm"></div>
        <div id="teamEditor" style="margin-top:16px;"></div>
        <div class="toolbar-row">
          <button type="button" class="btn-outline" id="addMemberBtn">+ Add Team Member</button>
        </div>
      </div>

      <div class="panel content-panel" id="panel-cta">
        <h2>Bottom CTA</h2>
        <div class="content-form-grid" id="ctaForm"></div>
      </div>
    </div>
  </div>
</div>

<script>
const API = 'api/pages.php';
let content = {
  hero: { subtitle: '', title: '', description: '', stats: [] },
  narrative: {},
  philosophy: { items: [] },
  journey: { eyebrow: '', title: '', items: [] },
  values: { image: '', items: [] },
  team: { eyebrow: '', title: '', members: [] },
  cta: { title: '', text: '', label: '', link: 'contact' }
};

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

const pendingUploads = {
  narrativeMain: null,
  narrativeFloat: null,
  valuesImage: null,
  team: {}
};

function fieldHtml(label, cls, value, rows = 1) {
  if (rows > 1) {
    return `<div class="form-field"><label>${label}</label><textarea class="${cls}" rows="${rows}">${esc(value)}</textarea></div>`;
  }
  return `<div class="form-field"><label>${label}</label><input type="text" class="${cls}" value="${esc(value)}"></div>`;
}

function mediaSrc(path) {
  if (!path) return '';
  if (/^(https?:|blob:|data:)/i.test(path)) return path;
  let p = String(path).replace(/^\.\//, '');
  if (p.startsWith('../')) return p;
  return '../' + p.replace(/^\//, '');
}

function uploadFieldHtml(label, hiddenClass, path, pendingKey, opts = {}) {
  const previewClass = opts.sm ? 'media-upload-preview sm' : 'media-upload-preview';
  const src = mediaSrc(path);
  const previewHidden = src ? '' : ' hidden';
  return `
    <div class="form-field" data-upload-key="${esc(pendingKey)}">
      <label>${label}</label>
      <input type="hidden" class="${hiddenClass}" value="${esc(path || '')}">
      <div class="${previewClass}" ${previewHidden ? 'hidden' : ''}>
        <img src="${esc(src)}" alt="Preview">
        <button type="button" class="media-upload-remove" aria-label="Clear selected file">&times;</button>
      </div>
      <div class="media-upload" tabindex="0" role="button">
        <ion-icon name="cloud-upload-outline"></ion-icon>
        <div class="media-upload-title">Click to upload or <span>browse</span></div>
        <div class="media-upload-hint">JPG, PNG, WEBP or GIF · max 5MB</div>
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif">
      </div>
    </div>`;
}

function bindUploadField(root, pendingKey, teamIndex = null) {
  const zone = root.querySelector('.media-upload');
  const input = root.querySelector('input[type="file"]');
  const preview = root.querySelector('.media-upload-preview');
  const img = preview?.querySelector('img');
  const removeBtn = preview?.querySelector('.media-upload-remove');
  const hidden = root.querySelector('input[type="hidden"]');
  if (!zone || !input) return;

  const setPending = (file) => {
    if (teamIndex !== null) pendingUploads.team[teamIndex] = file;
    else pendingUploads[pendingKey] = file;
  };

  const clearPending = () => {
    if (teamIndex !== null) delete pendingUploads.team[teamIndex];
    else pendingUploads[pendingKey] = null;
  };

  const showPreview = (url) => {
    if (!preview || !img) return;
    if (url) {
      img.src = url;
      preview.hidden = false;
    } else {
      img.src = '';
      preview.hidden = true;
    }
  };

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
  });
  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file) return;
    setPending(file);
    showPreview(URL.createObjectURL(file));
  });
  ['dragover', 'dragenter'].forEach(ev => zone.addEventListener(ev, e => {
    e.preventDefault();
    zone.classList.add('dragover');
  }));
  ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => {
    e.preventDefault();
    zone.classList.remove('dragover');
  }));
  zone.addEventListener('drop', e => {
    const file = [...(e.dataTransfer?.files || [])].find(f => f.type.startsWith('image/'));
    if (!file) return;
    setPending(file);
    showPreview(URL.createObjectURL(file));
  });
  removeBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    clearPending();
    input.value = '';
    // Keep the previously saved image path; only discard an unsaved new file.
    showPreview(mediaSrc(hidden?.value || ''));
  });
}

document.querySelectorAll('.content-tab').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.content-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.content-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
  };
});

function renderHero() {
  const h = content.hero || {};
  document.getElementById('heroForm').innerHTML = `
    ${fieldHtml('Subtitle', 'hero-subtitle', h.subtitle)}
    ${fieldHtml('Title (HTML allowed for gold accent)', 'hero-title', h.title)}
    ${fieldHtml('Description', 'hero-description', h.description, 3)}
  `;
  document.getElementById('statsEditor').innerHTML = (h.stats || []).map((s, i) => `
    <div class="item-editor" data-index="${i}">
      <div class="form-row-2">
        ${fieldHtml('Number', 'stat-num', s.num)}
        ${fieldHtml('Label', 'stat-label', s.label)}
      </div>
      <button type="button" class="btn-outline remove-stat">Remove</button>
    </div>
  `).join('');
  document.querySelectorAll('.remove-stat').forEach(btn => {
    btn.onclick = (e) => {
      const idx = +e.target.closest('.item-editor').dataset.index;
      content.hero.stats.splice(idx, 1);
      renderHero();
    };
  });
}

function renderNarrative() {
  const n = content.narrative || {};
  document.getElementById('narrativeForm').innerHTML = `
    ${fieldHtml('Badge', 'nar-badge', n.badge)}
    ${fieldHtml('Title', 'nar-title', n.title)}
    ${fieldHtml('Paragraph 1', 'nar-p1', n.paragraph1, 3)}
    ${fieldHtml('Paragraph 2', 'nar-p2', n.paragraph2, 3)}
    ${fieldHtml('Quote', 'nar-quote', n.quote, 2)}
    ${fieldHtml('Signature', 'nar-signature', n.signature)}
    <div class="form-row-2">
      ${uploadFieldHtml('Main image', 'nar-main', n.mainImage, 'narrativeMain')}
      ${uploadFieldHtml('Floating image', 'nar-float', n.floatImage, 'narrativeFloat')}
    </div>
    ${fieldHtml('Image caption', 'nar-caption', n.caption)}
  `;
  const form = document.getElementById('narrativeForm');
  form.querySelectorAll('[data-upload-key]').forEach(el => {
    bindUploadField(el, el.dataset.uploadKey);
  });
}

function renderPhilosophy() {
  const items = content.philosophy?.items || [];
  document.getElementById('philosophyEditor').innerHTML = items.map((p, i) => `
    <div class="item-editor" data-index="${i}">
      <div class="form-row-2">
        ${fieldHtml('Icon (ion-icon name)', 'pillar-icon', p.icon)}
        ${fieldHtml('Title', 'pillar-title', p.title)}
      </div>
      ${fieldHtml('Text', 'pillar-text', p.text, 3)}
      <button type="button" class="btn-outline remove-pillar">Remove</button>
    </div>
  `).join('');
  document.querySelectorAll('.remove-pillar').forEach(btn => {
    btn.onclick = (e) => {
      const idx = +e.target.closest('.item-editor').dataset.index;
      content.philosophy.items.splice(idx, 1);
      renderPhilosophy();
    };
  });
}

function renderJourney() {
  const j = content.journey || {};
  document.getElementById('journeyMetaForm').innerHTML = `
    ${fieldHtml('Eyebrow', 'journey-eyebrow', j.eyebrow)}
    ${fieldHtml('Section title', 'journey-title', j.title)}
  `;
  document.getElementById('journeyEditor').innerHTML = (j.items || []).map((item, i) => `
    <div class="item-editor" data-index="${i}">
      <div class="form-row-2">
        ${fieldHtml('Year', 'journey-year', item.year)}
        ${fieldHtml('Title', 'journey-item-title', item.title)}
      </div>
      ${fieldHtml('Text', 'journey-text', item.text, 3)}
      <button type="button" class="btn-outline remove-journey">Remove</button>
    </div>
  `).join('');
  document.querySelectorAll('.remove-journey').forEach(btn => {
    btn.onclick = (e) => {
      const idx = +e.target.closest('.item-editor').dataset.index;
      content.journey.items.splice(idx, 1);
      renderJourney();
    };
  });
}

function renderValues() {
  const v = content.values || {};
  document.getElementById('valuesMetaForm').innerHTML = uploadFieldHtml('Side image', 'values-image', v.image, 'valuesImage');
  const meta = document.getElementById('valuesMetaForm');
  meta.querySelectorAll('[data-upload-key]').forEach(el => bindUploadField(el, el.dataset.uploadKey));

  document.getElementById('valuesEditor').innerHTML = (v.items || []).map((item, i) => `
    <div class="item-editor" data-index="${i}">
      <div class="form-row-2">
        ${fieldHtml('Icon (ion-icon name)', 'value-icon', item.icon)}
        ${fieldHtml('Title', 'value-title', item.title)}
      </div>
      ${fieldHtml('Text', 'value-text', item.text, 3)}
      <button type="button" class="btn-outline remove-value">Remove</button>
    </div>
  `).join('');
  document.querySelectorAll('.remove-value').forEach(btn => {
    btn.onclick = (e) => {
      const idx = +e.target.closest('.item-editor').dataset.index;
      content.values.items.splice(idx, 1);
      renderValues();
    };
  });
}

function renderTeam() {
  const t = content.team || {};
  document.getElementById('teamMetaForm').innerHTML = `
    ${fieldHtml('Eyebrow', 'team-eyebrow', t.eyebrow)}
    ${fieldHtml('Section title', 'team-title', t.title)}
  `;
  document.getElementById('teamEditor').innerHTML = (t.members || []).map((m, i) => `
    <div class="item-editor" data-index="${i}">
      <div class="form-row-2">
        ${fieldHtml('Name', 'member-name', m.name)}
        ${fieldHtml('Role', 'member-role', m.role)}
      </div>
      ${uploadFieldHtml('Photo', 'member-image', m.image, 'team-' + i, { sm: true })}
      <button type="button" class="btn-outline remove-member">Remove</button>
    </div>
  `).join('');
  document.querySelectorAll('#teamEditor .item-editor').forEach(el => {
    const idx = +el.dataset.index;
    const upload = el.querySelector('[data-upload-key]');
    if (upload) bindUploadField(upload, upload.dataset.uploadKey, idx);
  });
  document.querySelectorAll('.remove-member').forEach(btn => {
    btn.onclick = (e) => {
      const idx = +e.target.closest('.item-editor').dataset.index;
      collectFromDom();
      content.team.members.splice(idx, 1);
      // Re-index pending team uploads after removal.
      const next = {};
      Object.keys(pendingUploads.team).forEach(k => {
        const n = +k;
        if (n < idx) next[n] = pendingUploads.team[n];
        else if (n > idx) next[n - 1] = pendingUploads.team[n];
      });
      pendingUploads.team = next;
      renderTeam();
    };
  });
}

function renderCta() {
  const c = content.cta || {};
  document.getElementById('ctaForm').innerHTML = `
    ${fieldHtml('Title', 'cta-title', c.title)}
    ${fieldHtml('Text', 'cta-text', c.text, 2)}
    ${fieldHtml('Button label', 'cta-label', c.label)}
    ${fieldHtml('Button link page (e.g. contact)', 'cta-link', c.link || 'contact')}
  `;
}

function collectFromDom() {
  content.hero = {
    subtitle: document.querySelector('.hero-subtitle')?.value ?? '',
    title: document.querySelector('.hero-title')?.value ?? '',
    description: document.querySelector('.hero-description')?.value ?? '',
    stats: [...document.querySelectorAll('#statsEditor .item-editor')].map(el => ({
      num: el.querySelector('.stat-num').value,
      label: el.querySelector('.stat-label').value
    }))
  };
  content.narrative = {
    badge: document.querySelector('.nar-badge')?.value ?? '',
    title: document.querySelector('.nar-title')?.value ?? '',
    paragraph1: document.querySelector('.nar-p1')?.value ?? '',
    paragraph2: document.querySelector('.nar-p2')?.value ?? '',
    quote: document.querySelector('.nar-quote')?.value ?? '',
    signature: document.querySelector('.nar-signature')?.value ?? '',
    mainImage: document.querySelector('.nar-main')?.value ?? '',
    floatImage: document.querySelector('.nar-float')?.value ?? '',
    caption: document.querySelector('.nar-caption')?.value ?? ''
  };
  content.philosophy = {
    items: [...document.querySelectorAll('#philosophyEditor .item-editor')].map(el => ({
      icon: el.querySelector('.pillar-icon').value,
      title: el.querySelector('.pillar-title').value,
      text: el.querySelector('.pillar-text').value
    }))
  };
  content.journey = {
    eyebrow: document.querySelector('.journey-eyebrow')?.value ?? '',
    title: document.querySelector('.journey-title')?.value ?? '',
    items: [...document.querySelectorAll('#journeyEditor .item-editor')].map(el => ({
      year: el.querySelector('.journey-year').value,
      title: el.querySelector('.journey-item-title').value,
      text: el.querySelector('.journey-text').value
    }))
  };
  content.values = {
    image: document.querySelector('.values-image')?.value ?? '',
    items: [...document.querySelectorAll('#valuesEditor .item-editor')].map(el => ({
      icon: el.querySelector('.value-icon').value,
      title: el.querySelector('.value-title').value,
      text: el.querySelector('.value-text').value
    }))
  };
  content.team = {
    eyebrow: document.querySelector('.team-eyebrow')?.value ?? '',
    title: document.querySelector('.team-title')?.value ?? '',
    members: [...document.querySelectorAll('#teamEditor .item-editor')].map(el => ({
      name: el.querySelector('.member-name').value,
      role: el.querySelector('.member-role').value,
      image: el.querySelector('.member-image')?.value ?? ''
    }))
  };
  content.cta = {
    title: document.querySelector('.cta-title')?.value ?? '',
    text: document.querySelector('.cta-text')?.value ?? '',
    label: document.querySelector('.cta-label')?.value ?? '',
    link: document.querySelector('.cta-link')?.value ?? 'contact'
  };
}

function clearPendingUploads() {
  pendingUploads.narrativeMain = null;
  pendingUploads.narrativeFloat = null;
  pendingUploads.valuesImage = null;
  pendingUploads.team = {};
}

function renderAll() {
  renderHero();
  renderNarrative();
  renderPhilosophy();
  renderJourney();
  renderValues();
  renderTeam();
  renderCta();
}

document.getElementById('addStatBtn').onclick = () => {
  collectFromDom();
  content.hero.stats = content.hero.stats || [];
  content.hero.stats.push({ num: '', label: '' });
  renderHero();
};
document.getElementById('addPillarBtn').onclick = () => {
  collectFromDom();
  content.philosophy.items.push({ icon: 'star-outline', title: '', text: '' });
  renderPhilosophy();
};
document.getElementById('addJourneyBtn').onclick = () => {
  collectFromDom();
  content.journey.items.push({ year: '', title: '', text: '' });
  renderJourney();
};
document.getElementById('addValueBtn').onclick = () => {
  collectFromDom();
  content.values.items.push({ icon: 'people-outline', title: '', text: '' });
  renderValues();
};
document.getElementById('addMemberBtn').onclick = () => {
  collectFromDom();
  content.team.members.push({ name: '', role: '', image: '' });
  renderTeam();
};

async function loadAbout() {
  const res = await fetch(`${API}?page=about`, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) throw new Error(data.message || 'Failed to load');
  content = data.content || content;
  content.hero = content.hero || { stats: [] };
  content.hero.stats = content.hero.stats || [];
  content.philosophy = content.philosophy || { items: [] };
  content.philosophy.items = content.philosophy.items || [];
  content.journey = content.journey || { items: [] };
  content.journey.items = content.journey.items || [];
  content.values = content.values || { items: [] };
  content.values.items = content.values.items || [];
  content.team = content.team || { members: [] };
  content.team.members = content.team.members || [];
  content.cta = content.cta || {};
  content.narrative = content.narrative || {};
  clearPendingUploads();
  renderAll();
}

document.getElementById('saveAllBtn').onclick = async () => {
  try {
    collectFromDom();
    const fd = new FormData();
    fd.append('page', 'about');
    fd.append('content', JSON.stringify(content));
    if (pendingUploads.narrativeMain) fd.append('narrative_mainImage', pendingUploads.narrativeMain);
    if (pendingUploads.narrativeFloat) fd.append('narrative_floatImage', pendingUploads.narrativeFloat);
    if (pendingUploads.valuesImage) fd.append('values_image', pendingUploads.valuesImage);
    Object.entries(pendingUploads.team).forEach(([i, file]) => {
      if (file) fd.append('team_image_' + i, file);
    });

    const res = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data.message || 'Save failed');
    content = data.content;
    clearPendingUploads();
    renderAll();
    showToast('About page saved');
  } catch (e) {
    showToast(e.message, true);
  }
};

loadAbout().catch(e => showToast(e.message, true));
</script>
</body>
</html>
