<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
AdminPermissions::require(AdminPermissions::PERM_CHATBOT);

$activeNav = 'chatbot-content';
$pageTitle = 'Chatbot Knowledge | Biver Royalty Homes Admin';
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
      <h1 class="page-title">Chatbot Knowledge Base</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>
    <div class="admin-content-pad">
      <div class="content-tabs">
        <button type="button" class="content-tab active" data-tab="intents">Intents</button>
        <button type="button" class="content-tab" data-tab="responses">Responses</button>
        <button type="button" class="content-tab" data-tab="knowledge">Knowledge Articles</button>
      </div>

      <div class="panel content-panel active" id="panel-intents">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h2>Chatbot Intents</h2>
          <button type="button" class="btn-gold" id="addIntentBtn">Add Intent</button>
        </div>
        <div id="intentsList" class="content-list"></div>
      </div>

      <div class="panel content-panel" id="panel-responses">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
          <h2>Intent Responses</h2>
          <button type="button" class="btn-gold" id="addResponseBtn">Add Response</button>
        </div>
        <div id="responsesList" class="content-list"></div>
      </div>

      <div class="panel content-panel" id="panel-knowledge">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h2>Knowledge Articles</h2>
          <button type="button" class="btn-gold" id="addKnowledgeBtn">Add Article</button>
        </div>
        <div id="knowledgeList" class="content-list"></div>
      </div>
    </div>
  </div>
</div>

<div id="editModal" class="modal" aria-hidden="true">
  <div class="modal-card" style="max-width:640px;">
    <h3 id="modalTitle">Edit</h3>
    <form id="editForm" class="content-form-grid"></form>
    <div class="modal-actions">
      <button type="button" class="btn-outline" id="closeModalBtn">Cancel</button>
      <button type="button" class="btn-gold" id="saveModalBtn">Save</button>
    </div>
  </div>
</div>

<script>
const API = 'api/chatbot-content.php';
let intents = [], responses = [], knowledge = [];
let modalType = 'intent', modalId = 0;

function showToast(msg, err = false) {
  document.querySelector('.toast')?.remove();
  const t = document.createElement('div');
  t.className = 'toast' + (err ? ' error' : '');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

function esc(s) { return String(s ?? '').replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }

document.querySelectorAll('.content-tab').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.content-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.content-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
  };
});

async function loadAll() {
  const res = await fetch(API, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.success) throw new Error(data.message);
  intents = data.intents || [];
  responses = data.responses || [];
  knowledge = data.knowledge || [];
  renderAll();
}

function renderAll() {
  document.getElementById('intentsList').innerHTML = intents.map(i => `
    <article class="content-item">
      <h4>${esc(i.name)} <small>(${esc(i.intentKey)})</small> ${i.isActive ? '' : '<span class="badge-inactive">Inactive</span>'}</h4>
      <p>${esc(i.description)}</p>
      <small class="hint">Keywords: ${esc((i.keywords||[]).join(', '))}</small>
      <div class="content-item-actions">
        <button type="button" class="btn-outline" onclick="openIntent(${i.id})">Edit</button>
        <button type="button" class="btn-outline" onclick="del('intent',${i.id})">Delete</button>
      </div>
    </article>`).join('') || '<p class="hint">No intents. Run chatbot SQL install or add one.</p>';

  document.getElementById('responsesList').innerHTML = responses.map(r => `
    <article class="content-item">
      <h4>${esc(r.intentName)}</h4>
      <p>${esc(r.responseText)}</p>
      <div class="content-item-actions">
        <button type="button" class="btn-outline" onclick="openResponse(${r.id})">Edit</button>
        <button type="button" class="btn-outline" onclick="del('response',${r.id})">Delete</button>
      </div>
    </article>`).join('') || '<p class="hint">No responses yet.</p>';

  document.getElementById('knowledgeList').innerHTML = knowledge.map(k => `
    <article class="content-item">
      <h4>${esc(k.title)}</h4>
      <p>${esc(k.content).slice(0, 200)}...</p>
      <div class="content-item-actions">
        <button type="button" class="btn-outline" onclick="openKnowledge(${k.id})">Edit</button>
        <button type="button" class="btn-outline" onclick="del('knowledge',${k.id})">Delete</button>
      </div>
    </article>`).join('') || '<p class="hint">No knowledge articles yet.</p>';
}

function openModal() { document.getElementById('editModal').classList.add('open'); }
function closeModal() { document.getElementById('editModal').classList.remove('open'); }

function openIntent(id) {
  const i = intents.find(x => x.id === id) || { intentKey:'', name:'', description:'', keywords:[], priority:50, isActive:true };
  modalType = 'intent'; modalId = id || 0;
  document.getElementById('modalTitle').textContent = id ? 'Edit Intent' : 'Add Intent';
  document.getElementById('editForm').innerHTML = `
    <input type="hidden" id="f_id" value="${id||''}">
    <div class="form-field"><label>Intent key</label><input id="f_intentKey" value="${esc(i.intentKey)}" required></div>
    <div class="form-field"><label>Name</label><input id="f_name" value="${esc(i.name)}" required></div>
    <div class="form-field"><label>Description</label><textarea id="f_description" rows="2">${esc(i.description)}</textarea></div>
    <div class="form-field"><label>Keywords (comma-separated)</label><input id="f_keywords" value="${esc((i.keywords||[]).join(', '))}"></div>
    <div class="form-field"><label>Priority</label><input type="number" id="f_priority" value="${i.priority||50}" min="0" max="100"></div>
    <label><input type="checkbox" id="f_isActive" ${i.isActive!==false?'checked':''}> Active</label>`;
  openModal();
}

function openResponse(id) {
  const r = responses.find(x => x.id === id) || { intentId: intents[0]?.id || 0, responseText:'', weight:1, isActive:true };
  modalType = 'response'; modalId = id || 0;
  document.getElementById('modalTitle').textContent = id ? 'Edit Response' : 'Add Response';
  const opts = intents.map(i => `<option value="${i.id}" ${i.id===r.intentId?'selected':''}>${esc(i.name)}</option>`).join('');
  document.getElementById('editForm').innerHTML = `
    <div class="form-field"><label>Intent</label><select id="f_intentId">${opts}</select></div>
    <div class="form-field"><label>Response text</label><textarea id="f_responseText" rows="4" required>${esc(r.responseText)}</textarea></div>
    <div class="form-field"><label>Weight</label><input type="number" id="f_weight" value="${r.weight||1}" min="1" max="100"></div>
    <label><input type="checkbox" id="f_isActive" ${r.isActive!==false?'checked':''}> Active</label>`;
  openModal();
}

function openKnowledge(id) {
  const k = knowledge.find(x => x.id === id) || { title:'', content:'', keywords:[], category:'general', priority:50, isActive:true };
  modalType = 'knowledge'; modalId = id || 0;
  document.getElementById('modalTitle').textContent = id ? 'Edit Article' : 'Add Article';
  document.getElementById('editForm').innerHTML = `
    <div class="form-field"><label>Title</label><input id="f_title" value="${esc(k.title)}" required></div>
    <div class="form-field"><label>Content</label><textarea id="f_content" rows="6" required>${esc(k.content)}</textarea></div>
    <div class="form-field"><label>Keywords</label><input id="f_keywords" value="${esc((k.keywords||[]).join(', '))}"></div>
    <div class="form-field"><label>Category</label><input id="f_category" value="${esc(k.category)}"></div>
    <div class="form-field"><label>Priority</label><input type="number" id="f_priority" value="${k.priority||50}"></div>
    <label><input type="checkbox" id="f_isActive" ${k.isActive!==false?'checked':''}> Active</label>`;
  openModal();
}

window.openIntent = (id) => openIntent(id);
window.openResponse = (id) => openResponse(id);
window.openKnowledge = (id) => openKnowledge(id);

window.del = async (type, id) => {
  if (!confirm('Delete this item?')) return;
  const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({ action:'delete', type, id }) });
  const data = await res.json();
  if (!data.success) return showToast(data.message, true);
  showToast('Deleted');
  loadAll();
};

document.getElementById('addIntentBtn').onclick = () => openIntent(0);
document.getElementById('addResponseBtn').onclick = () => openResponse(0);
document.getElementById('addKnowledgeBtn').onclick = () => openKnowledge(0);
document.getElementById('closeModalBtn').onclick = closeModal;

document.getElementById('saveModalBtn').onclick = async () => {
  let payload = { type: modalType };
  if (modalType === 'intent') {
    payload = { ...payload, id: modalId||undefined, intentKey: f('f_intentKey'), name: f('f_name'), description: f('f_description'), keywords: f('f_keywords'), priority: +f('f_priority'), isActive: document.getElementById('f_isActive').checked };
  } else if (modalType === 'response') {
    payload = { ...payload, type:'response', id: modalId||undefined, intentId: +f('f_intentId'), responseText: f('f_responseText'), weight: +f('f_weight'), isActive: document.getElementById('f_isActive').checked };
  } else {
    payload = { ...payload, type:'knowledge', id: modalId||undefined, title: f('f_title'), content: f('f_content'), keywords: f('f_keywords'), category: f('f_category'), priority: +f('f_priority'), isActive: document.getElementById('f_isActive').checked };
  }
  const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify(payload) });
  const data = await res.json();
  if (!data.success) return showToast(data.message, true);
  showToast('Saved');
  closeModal();
  loadAll();
};

function f(id) { return document.getElementById(id)?.value || ''; }

loadAll().catch(e => showToast(e.message, true));
</script>
</body>
</html>
