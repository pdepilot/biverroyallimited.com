<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__) . '/includes/BlogRepository.php';
require_once dirname(__DIR__) . '/includes/site_paths.php';

AdminPermissions::require(AdminPermissions::PERM_BLOG);
BlogRepository::ensureSchema();

$activeNav = 'blog';
$pageTitle = 'Blog | Biver Royalty Homes Admin';
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$pageStylesheet = '../assets/css/admin-content.css';
$publicBlogUrl = '../blog.php';
$categories = BlogRepository::CATEGORIES;
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
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"><ion-icon name="menu-outline"></ion-icon></button>
      <h1 class="page-title">Blog</h1>
      <span class="admin-badge">Signed in as <?= $adminName ?></span>
    </header>
    <div class="admin-content-pad">
      <div class="panel">
        <div class="panel-toolbar">
          <div>
            <h2>Blog Posts</h2>
            <p class="hint">Published posts appear on the public Blog page. Upload a cover image when saving.</p>
            <p class="status-text" id="statusText">Loading...</p>
          </div>
          <div class="panel-toolbar-actions">
            <a class="btn-outline" href="<?= htmlspecialchars($publicBlogUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
              <ion-icon name="open-outline"></ion-icon> View public blog
            </a>
            <button type="button" class="btn-gold" id="addPostBtn"><ion-icon name="add-outline"></ion-icon> Add Post</button>
          </div>
        </div>
        <div id="postList" class="content-list"></div>
      </div>
    </div>
  </div>
</div>

<div id="postModal" class="modal" aria-hidden="true">
  <div class="modal-card">
    <h3 id="modalTitle">Add Blog Post</h3>
    <form id="postForm" class="content-form-grid">
      <input type="hidden" id="postId">
      <div class="form-field"><label for="title">Title</label><input type="text" id="title" required maxlength="255"></div>
      <div class="form-field"><label for="slug">Slug (optional)</label><input type="text" id="slug" maxlength="220" placeholder="auto-generated-from-title"></div>
      <div class="form-field"><label for="excerpt">Excerpt</label><textarea id="excerpt" rows="2" placeholder="Short summary for cards"></textarea></div>
      <div class="form-field"><label for="content">Content</label><textarea id="content" required rows="8"></textarea></div>
      <div class="form-row-2">
        <div class="form-field">
          <label for="category">Category</label>
          <select id="category">
            <?php foreach ($categories as $key => $label): ?>
              <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field"><label for="authorName">Author</label><input type="text" id="authorName" value="Biver Royalty Homes" maxlength="120"></div>
      </div>
      <div class="form-field">
        <label>Cover image</label>
        <img id="coverPreview" class="post-cover-thumb" alt="" hidden>
        <div class="media-upload" id="coverUploadZone">
          <ion-icon name="cloud-upload-outline"></ion-icon>
          <div class="media-upload-title">Click to upload or <span>browse</span></div>
          <div class="media-upload-hint">JPG, PNG, WEBP or GIF · max 5MB</div>
          <input type="file" id="coverImage" accept="image/jpeg,image/png,image/webp,image/gif">
        </div>
      </div>
      <label class="checkbox-inline"><input type="checkbox" id="isPublished" checked> Publish on website</label>
      <div class="modal-actions">
        <button type="button" class="btn-outline" id="closeModalBtn">Cancel</button>
        <button type="submit" class="btn-gold">Save Post</button>
      </div>
    </form>
  </div>
</div>

<script>
const API = 'api/blog.php';
const CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
let posts = [];
let coverFile = null;
let currentCover = '';

function showToast(msg, err = false) {
  document.querySelector('.toast')?.remove();
  const t = document.createElement('div');
  t.className = 'toast' + (err ? ' error' : '');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

function esc(s) {
  return String(s ?? '').replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
}

function mediaSrc(path) {
  if (!path) return '';
  if (/^(https?:|blob:)/i.test(path)) return path;
  let p = String(path).replace(/^\.\//, '');
  return '../' + p.replace(/^\//, '');
}

function categoryLabel(key) {
  return CATEGORIES[key] || String(key || '').replace(/[-_]/g, ' ');
}

async function loadPosts() {
  const res = await fetch(API, { credentials: 'same-origin', cache: 'no-store' });
  const data = await res.json();
  if (!data.success) throw new Error(data.message || 'Failed to load');
  posts = data.posts || [];
  const published = (data.stats && data.stats.published) || posts.filter(p => p.isPublished).length;
  document.getElementById('statusText').textContent =
    `${posts.length} total · ${published} published on website`;
  renderPosts();
}

function renderPosts() {
  const el = document.getElementById('postList');
  if (!posts.length) {
    el.innerHTML = '<p class="hint">No blog posts yet. Add your first article.</p>';
    return;
  }
  el.innerHTML = posts.map(p => `
    <article class="content-item">
      <div class="blog-post-row">
        <img class="blog-post-thumb" src="${esc(mediaSrc(p.coverImage) || '../assets/images/biver-logo.png')}" alt="">
        <div class="blog-post-body">
          <h4>${esc(p.title)} ${p.isPublished
            ? '<span class="badge-active">Published</span>'
            : '<span class="badge-inactive">Draft</span>'}</h4>
          <p>${esc(p.excerpt || p.content.slice(0, 140))}</p>
          <small class="hint blog-post-meta">${esc(p.categoryLabel || categoryLabel(p.category))} · ${esc(p.authorName)} · ${esc(p.slug)} · ${p.viewCount || 0} views</small>
          <div class="content-item-actions">
            <button type="button" class="btn-outline" onclick="togglePost(${p.id})">${p.isPublished ? 'Unpublish' : 'Publish'}</button>
            <button type="button" class="btn-outline" onclick="editPost(${p.id})">Edit</button>
            <a class="btn-outline" href="../blog-post.php?slug=${encodeURIComponent(p.slug)}" target="_blank" rel="noopener">Preview</a>
            <button type="button" class="btn-outline" onclick="deletePost(${p.id})">Delete</button>
          </div>
        </div>
      </div>
    </article>`).join('');
}

function setCoverPreview(url) {
  const img = document.getElementById('coverPreview');
  if (url) {
    img.src = url;
    img.hidden = false;
  } else {
    img.src = '';
    img.hidden = true;
  }
}

function openModal(post = null) {
  document.getElementById('modalTitle').textContent = post ? 'Edit Blog Post' : 'Add Blog Post';
  document.getElementById('postId').value = post?.id || '';
  document.getElementById('title').value = post?.title || '';
  document.getElementById('slug').value = post?.slug || '';
  document.getElementById('excerpt').value = post?.excerpt || '';
  document.getElementById('content').value = post?.content || '';
  document.getElementById('category').value = post?.category || 'market-insights';
  document.getElementById('authorName').value = post?.authorName || 'Biver Royalty Homes';
  document.getElementById('isPublished').checked = post ? !!post.isPublished : true;
  document.getElementById('coverImage').value = '';
  coverFile = null;
  currentCover = post?.coverImage || '';
  setCoverPreview(mediaSrc(currentCover));
  document.getElementById('postModal').classList.add('open');
}

function closeModal() {
  document.getElementById('postModal').classList.remove('open');
}

function editPost(id) {
  const post = posts.find(p => p.id === id);
  if (post) openModal(post);
}

async function togglePost(id) {
  try {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action: 'toggle', id })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    showToast(data.message || 'Updated');
    await loadPosts();
  } catch (e) { showToast(e.message, true); }
}

async function deletePost(id) {
  if (!confirm('Delete this blog post?')) return;
  try {
    const res = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action: 'delete', id })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message);
    showToast('Post deleted');
    await loadPosts();
  } catch (e) { showToast(e.message, true); }
}

const zone = document.getElementById('coverUploadZone');
const fileInput = document.getElementById('coverImage');
zone?.addEventListener('click', () => fileInput.click());
fileInput?.addEventListener('change', () => {
  const file = fileInput.files?.[0];
  if (!file) return;
  coverFile = file;
  setCoverPreview(URL.createObjectURL(file));
});

document.getElementById('addPostBtn').onclick = () => openModal();
document.getElementById('closeModalBtn').onclick = closeModal;
document.getElementById('postModal').addEventListener('click', (e) => {
  if (e.target.id === 'postModal') closeModal();
});

document.getElementById('postForm').onsubmit = async (e) => {
  e.preventDefault();
  try {
    const id = parseInt(document.getElementById('postId').value, 10) || 0;
    const fd = new FormData();
    if (id) fd.append('id', String(id));
    fd.append('title', document.getElementById('title').value);
    fd.append('slug', document.getElementById('slug').value);
    fd.append('excerpt', document.getElementById('excerpt').value);
    fd.append('content', document.getElementById('content').value);
    fd.append('category', document.getElementById('category').value);
    fd.append('authorName', document.getElementById('authorName').value);
    fd.append('isPublished', document.getElementById('isPublished').checked ? '1' : '0');
    if (coverFile) fd.append('coverImage', coverFile);

    const res = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) throw new Error(data.message || 'Save failed');
    showToast(data.message || 'Post saved');
    closeModal();
    await loadPosts();
  } catch (err) { showToast(err.message, true); }
};

loadPosts().catch(e => showToast(e.message, true));
</script>
</body>
</html>
