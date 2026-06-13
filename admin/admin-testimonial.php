<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <title>Royal Voices | Testimonials Manager</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <?php require dirname(__DIR__) . '/includes/admin_assets.php'; ?>
  <link rel="stylesheet" href="../assets/css/admin-testimonial.css">

  </head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<?php $activeNav = 'testimonials'; ?>
<div class="dashboard">
  <?php require dirname(__DIR__) . '/includes/admin_sidebar.php'; ?>

  <div class="main">
    <div class="top-bar">
      <div class="admin-header-actions">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-grip-lines"></i></button>
        <h1 class="page-title"><i class="fas fa-comment-dots"></i> Voices of Nobility</h1>
      </div>
      <div class="search-add">
        <div class="search-box">
          <i class="fas fa-search admin-search-icon"></i>
          <input type="text" id="searchInput" placeholder="Search by name...">
        </div>
        <button class="btn-gold" id="addTestimonialBtn"><i class="fas fa-plus-circle"></i> Add Testimonial</button>
      </div>
    </div>
    <div id="testimonialsContainer" class="testimonials-grid">
      <div class="loader"><i class="fas fa-spinner fa-pulse"></i> Loading royal testimonials...</div>
    </div>
  </div>
</div>

<!-- Modal for testimonial -->
<div id="testimonialModal" class="modal">
  <div class="modal-card">
    <h3 id="modalTitle">✨ New Royal Testimony</h3>
    <form id="testimonialForm">
      <input type="hidden" id="testimonialId">
      <input type="text" id="clientName" placeholder="Full name" required>
      <input type="number" id="rating" placeholder="Rating (1-5)" min="1" max="5" step="1" required>
      <textarea id="message" rows="4" placeholder="Share the noble experience..." required></textarea>
      <input type="text" id="avatarInitials" placeholder="Initials (e.g., JD) - optional">
      <div class="modal-buttons">
        <button type="button" class="btn-outline" id="closeModalBtn">Cancel</button>
        <button type="submit" class="btn-gold">Save Testimony</button>
      </div>
    </form>
  </div>
</div>

<script>
  const API = 'api/testimonials.php';
  let testimonials = [];

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

  async function loadTestimonials() {
    try {
      const res = await fetch(API, { credentials: 'same-origin' });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to load');
      testimonials = data.testimonials || [];
      renderTestimonials();
    } catch (err) {
      console.error(err);
      showToast('Could not fetch testimonials', true);
      document.getElementById('testimonialsContainer').innerHTML =
        '<div class="admin-empty-wide">Could not load testimonials. Run sql/install_content.php first.</div>';
    }
  }

  function getId(t) {
    return String(t.id ?? t._id ?? '');
  }

  function renderStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
      stars += i <= rating ? '★' : '☆';
    }
    return stars;
  }

  function renderTestimonials() {
    const container = document.getElementById('testimonialsContainer');
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    let filtered = testimonials.filter(t => (t.name || '').toLowerCase().includes(searchTerm));
    if (filtered.length === 0) {
      container.innerHTML = `<div class="admin-empty-wide"><i class="fas fa-crown"></i> No testimonials yet. Be the first to add a royal voice.</div>`;
      return;
    }
    container.innerHTML = filtered.map(t => {
      const id = getId(t);
      const initials = t.initials || (t.name ? t.name.split(' ').map(n=>n[0]).join('').toUpperCase().slice(0,2) : '👑');
      return `
        <div class="testimonial-card" data-id="${id}">
          <div class="card-header">
            <div class="avatar">${escapeHtml(initials)}</div>
            <div class="client-info">
              <h4>${escapeHtml(t.name)}</h4>
              <div class="stars">${renderStars(t.rating || 5)}</div>
            </div>
          </div>
          <div class="message">“ ${escapeHtml(t.message)} ”</div>
          <div class="card-actions">
            <button class="action-icon edit" data-id="${id}"><i class="fas fa-feather-alt"></i> Edit</button>
            <button class="action-icon delete" data-id="${id}"><i class="fas fa-trash"></i> Delete</button>
          </div>
        </div>
      `;
    }).join('');

    document.querySelectorAll('.edit').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        openEditModal(btn.getAttribute('data-id'));
      });
    });
    document.querySelectorAll('.delete').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        if (confirm('Remove this noble testimony?')) {
          await deleteTestimonial(btn.getAttribute('data-id'));
        }
      });
    });
  }

  function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

  async function deleteTestimonial(id) {
    try {
      await apiPost({ action: 'delete', id: Number(id) });
      showToast('Testimonial removed', false);
      loadTestimonials();
    } catch (err) {
      showToast('Error deleting', true);
    }
  }

  function openEditModal(id = null) {
    const modal = document.getElementById('testimonialModal');
    const form = document.getElementById('testimonialForm');
    form.reset();
    document.getElementById('testimonialId').value = '';
    document.getElementById('modalTitle').innerHTML = id ? '✍️ Refine Royal Voice' : '🏆 New Testimonial';
    if (id) {
      const t = testimonials.find(item => getId(item) === String(id));
      if (t) {
        document.getElementById('testimonialId').value = getId(t);
        document.getElementById('clientName').value = t.name || '';
        document.getElementById('rating').value = t.rating || 5;
        document.getElementById('message').value = t.message || '';
        document.getElementById('avatarInitials').value = t.initials || '';
      }
    }
    modal.style.display = 'flex';
  }

  async function saveTestimonial(e) {
    e.preventDefault();
    const id = document.getElementById('testimonialId').value;
    const payload = {
      action: 'save',
      name: document.getElementById('clientName').value,
      rating: parseInt(document.getElementById('rating').value, 10),
      message: document.getElementById('message').value,
      initials: document.getElementById('avatarInitials').value || ''
    };
    if (id) payload.id = Number(id);
    try {
      await apiPost(payload);
      showToast(id ? 'Testimonial updated ✨' : 'New testimony added!');
      closeModal();
      loadTestimonials();
    } catch (err) {
      showToast(err.message || 'Failed to save', true);
    }
  }

  function closeModal() {
    document.getElementById('testimonialModal').style.display = 'none';
  }

  function showToast(msg, isError = false) {
    let toast = document.querySelector('.toast-msg');
    if(toast) toast.remove();
    toast = document.createElement('div');
    toast.className = 'toast-msg';
    toast.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i> ${msg}`;
    toast.style.borderLeftColor = isError ? '#e74c3c' : '#D4AF37';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }


  document.getElementById('searchInput')?.addEventListener('input', () => renderTestimonials());
  document.getElementById('addTestimonialBtn')?.addEventListener('click', () => openEditModal());
  document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
  document.getElementById('testimonialModal')?.addEventListener('click', (e) => {
    if(e.target === document.getElementById('testimonialModal')) closeModal();
  });
  document.getElementById('testimonialForm')?.addEventListener('submit', saveTestimonial);

  loadTestimonials();
</script>
</body>
</html>