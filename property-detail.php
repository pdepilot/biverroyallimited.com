<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<!DOCTYPE html>
<?php require_once __DIR__ . '/includes/site_paths.php'; ?>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="View full property details, photos, video tour, and features with Biver Royalty Homes.">
  <title>Property Details | Biver Royalty Homes</title>
  <link rel="shortcut icon" href="./assets/images/biver-logo.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/site-variables.css">
  <link rel="stylesheet" href="./assets/css/site-utilities.css">
  <link rel="stylesheet" href="./assets/css/property-detail.css">
  <link rel="stylesheet" href="./assets/css/site-header.css">
  <?php require __DIR__ . '/includes/site_bootstrap.php'; ?>
  </head>
<body>

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <div class="page-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= siteEscape(pageUrl('index')) ?>">Home</a>
      <ion-icon name="chevron-forward-outline"></ion-icon>
      <a href="<?= siteEscape(pageUrl('property')) ?>">Properties</a>
      <ion-icon name="chevron-forward-outline"></ion-icon>
      <span id="breadcrumbTitle">Property Details</span>
    </nav>

    <div id="detailContent">
      <div class="state-box">
        <p>Loading property details...</p>
      </div>
    </div>
  </div>
</main>

<footer class="footer" role="contentinfo">
  <div class="footer-top">
    <div class="container">
      <div class="footer-brand">
        <a href="<?= siteEscape(pageUrl('index')) ?>" class="logo">
          <img src="./assets/images/biver-logo.png" alt="Biver Royalty Homes" width="150" height="auto" loading="lazy">
        </a>
        <p class="section-text">We are a real estate company built on Integrity. We help our clients bring their dream homes to reality within their budget.</p>
        <ul class="contact-list">
          <li><a href="<?= pageHref('contact') ?>" class="contact-link"><ion-icon name="location-outline"></ion-icon><address>No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State.</address></a></li>
          <li><a href="tel:+2349033137432" class="contact-link"><ion-icon name="call-outline"></ion-icon><span>+234 903 313 7432</span></a></li>
          <li><a href="mailto:biverroyaltyhomes01@gmail.com" class="contact-link"><ion-icon name="mail-outline"></ion-icon><span>biverroyaltyhomes01@gmail.com</span></a></li>
        </ul>
      </div>
      <div class="footer-link-box">
        <ul class="footer-list">
          <li><p class="footer-list-title">Company</p></li>
          <li><a href="<?= siteEscape(pageUrl('about')) ?>" class="footer-link">About Us</a></li>
          <li><a href="<?= siteEscape(pageUrl('property')) ?>" class="footer-link">All Properties</a></li>
          <li><a href="<?= siteEscape(pageUrl('contact')) ?>" class="footer-link">Contact Us</a></li>
        </ul>
        <ul class="footer-list">
          <li><p class="footer-list-title">Services</p></li>
          <li><a href="<?= siteEscape(pageUrl('list-your-property')) ?>" class="footer-link">List Your Property</a></li>
          <li><a href="<?= siteEscape(pageUrl('services')) ?>" class="footer-link">Our Services</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <p class="copyright">&copy; 2025 <a href="<?= siteEscape(pageUrl('index')) ?>">Biver Royalty Homes</a>. All Rights Reserved</p>
    </div>
  </div>
</footer>

<div class="lightbox" id="lightbox" aria-hidden="true" role="dialog" aria-label="Image gallery">
  <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close gallery">&times;</button>
  <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous image">&#8249;</button>
  <img id="lightboxImage" src="" alt="">
  <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next image">&#8250;</button>
  <span class="lightbox-counter" id="lightboxCounter"></span>
</div>

<button id="scrollToTop" type="button" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>

<script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<script>
(function() {
  'use strict';

  const API = window.BIVER_SITE?.propertiesApi || 'api/properties.php';
  let galleryImages = [];
  let lightboxIndex = 0;
  let currentProperty = null;

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
  }

  function resolveMediaUrl(url) {
    if (!url) return '';
    if (url.startsWith('http') || url.startsWith('/')) return url;
    if (url.startsWith('assets/')) return './' + url;
    return url;
  }

  function formatPrice(price, type) {
    const formatted = '\u20A6' + Number(price).toLocaleString();
    return type === 'rent' ? formatted + '/month' : formatted;
  }

  function formatDate(value) {
    if (!value) return 'Recently listed';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Recently listed';
    return date.toLocaleDateString('en-NG', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  function listingLabel(property) {
    if (property.listingPurpose) {
      return String(property.listingPurpose).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }
    return property.type === 'rent' ? 'For Rent' : 'For Sale';
  }

  function getAllImages(property) {
    if (Array.isArray(property.images) && property.images.length) {
      return property.images.map(resolveMediaUrl);
    }
    const urls = [];
    if (property.imageUrl) urls.push(resolveMediaUrl(property.imageUrl));
    if (Array.isArray(property.galleryUrls)) {
      property.galleryUrls.forEach((url) => urls.push(resolveMediaUrl(url)));
    }
    if (!urls.length) {
      urls.push('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=800&fit=crop');
    }
    return urls;
  }

  function parseFeatures(raw) {
    if (!raw) return [];
    return String(raw).split(/[,;|•\n]+/).map((item) => item.trim()).filter(Boolean);
  }

  function openLightbox(index) {
    lightboxIndex = index;
    updateLightbox();
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
  }

  function updateLightbox() {
    const img = document.getElementById('lightboxImage');
    img.src = galleryImages[lightboxIndex];
    img.alt = `${currentProperty?.title || 'Property'} photo ${lightboxIndex + 1}`;
    document.getElementById('lightboxCounter').textContent = `${lightboxIndex + 1} / ${galleryImages.length}`;
  }

  document.getElementById('lightboxClose').addEventListener('click', closeLightbox);
  document.getElementById('lightbox').addEventListener('click', (e) => { if (e.target.id === 'lightbox') closeLightbox(); });
  document.getElementById('lightboxPrev').addEventListener('click', () => {
    lightboxIndex = (lightboxIndex - 1 + galleryImages.length) % galleryImages.length;
    updateLightbox();
  });
  document.getElementById('lightboxNext').addEventListener('click', () => {
    lightboxIndex = (lightboxIndex + 1) % galleryImages.length;
    updateLightbox();
  });
  document.addEventListener('keydown', (e) => {
    if (!document.getElementById('lightbox').classList.contains('open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') document.getElementById('lightboxPrev').click();
    if (e.key === 'ArrowRight') document.getElementById('lightboxNext').click();
  });

  function renderGallery(images, property) {
    const badgeClass = property.type === 'rent' ? 'rent' : 'sale';
    const singleClass = images.length <= 1 ? 'gallery-single' : '';
    const side = images.slice(1, 3);
    const moreCount = Math.max(0, images.length - 3);

    let sideHtml = side.map((url, i) => `
      <div class="gallery-side-item" data-index="${i + 1}" role="button" tabindex="0" aria-label="View photo ${i + 2}">
        <img src="${escapeHtml(url)}" alt="">
        ${i === 1 && moreCount > 0 ? `<div class="gallery-more">+${moreCount} more</div>` : ''}
      </div>
    `).join('');

    if (images.length === 2) {
      sideHtml = `
        <div class="gallery-side-item" data-index="1" role="button" tabindex="0">
          <img src="${escapeHtml(images[1])}" alt="">
        </div>
        <div class="gallery-side-item gallery-side-placeholder">More views below</div>`;
    }

    const thumbs = images.map((url, i) => `
      <button type="button" class="${i === 0 ? 'active' : ''}" data-thumb="${i}" aria-label="Show photo ${i + 1}">
        <img src="${escapeHtml(url)}" alt="">
      </button>
    `).join('');

    return `
      <div class="gallery-hero ${singleClass}">
        <div class="gallery-main" data-index="0" role="button" tabindex="0" aria-label="View main photo">
          <img id="heroMainImage" src="${escapeHtml(images[0])}" alt="${escapeHtml(property.title)}">
          <span class="gallery-badge ${badgeClass}">${escapeHtml(listingLabel(property))}</span>
          <span class="gallery-count"><ion-icon name="images-outline"></ion-icon> ${images.length} Photos</span>
        </div>
        ${images.length > 1 ? `<div class="gallery-side">${sideHtml}</div>` : ''}
      </div>
      ${images.length > 1 ? `<div class="thumb-strip" id="thumbStrip">${thumbs}</div>` : ''}
    `;
  }

  function bindGalleryEvents() {
    document.querySelectorAll('[data-index]').forEach((el) => {
      const open = () => openLightbox(Number(el.dataset.index));
      el.addEventListener('click', open);
      el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
    });

    document.querySelectorAll('[data-thumb]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const index = Number(btn.dataset.thumb);
        const hero = document.getElementById('heroMainImage');
        if (hero) hero.src = galleryImages[index];
        document.querySelectorAll('[data-thumb]').forEach((b) => b.classList.toggle('active', b === btn));
        document.querySelector('.gallery-main')?.setAttribute('data-index', String(index));
      });
    });
  }

  function renderInfoRows(property) {
    const rows = [
      ['Property type', property.propertyCategory || 'Residential'],
      ['Listing purpose', listingLabel(property)],
      ['Location', property.location],
      ['Full address', property.propertyAddress],
      ['Plot / size', property.propertySize || (property.area ? property.area + ' sqm' : null)],
      ['Ownership', property.ownershipStatus],
      ['Listed on', formatDate(property.createdAt)],
      ['Reference ID', 'BRH-' + property.id]
    ].filter(([, value]) => value);

    return rows.map(([label, value]) => `
      <div class="info-row"><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(String(value))}</dd></div>
    `).join('');
  }

  function renderProperty(property) {
    currentProperty = property;
    galleryImages = getAllImages(property);
    const video = property.videoUrl ? resolveMediaUrl(property.videoUrl) : '';
    const features = parseFeatures(property.propertyFeatures);
    const contactUrl = (window.BIVER_SITE?.page || ((n, p) => n + (p ? '?' + new URLSearchParams(p) : '')))('contact', { property: property.title });
    const whatsappText = encodeURIComponent(`Hello Biver Royalty Homes, I am interested in: ${property.title} (Ref BRH-${property.id})`);
    const whatsappUrl = 'https://wa.me/2349033137432?text=' + whatsappText;

    document.title = `${property.title} | Biver Royalty Homes`;
    document.getElementById('breadcrumbTitle').textContent = property.title;

    document.getElementById('detailContent').innerHTML = `
      ${renderGallery(galleryImages, property)}

      <div class="detail-layout">
        <div class="detail-main">
          <div class="title-block">
            <h1>${escapeHtml(property.title)}</h1>
            <div class="title-meta">
              <span><ion-icon name="location-outline"></ion-icon>${escapeHtml(property.location)}</span>
              ${property.propertyAddress ? `<span><ion-icon name="navigate-outline"></ion-icon>${escapeHtml(property.propertyAddress)}</span>` : ''}
              <span><ion-icon name="calendar-outline"></ion-icon>${formatDate(property.createdAt)}</span>
            </div>
          </div>

          <div class="spec-grid">
            <div class="spec-card"><ion-icon name="bed-outline"></ion-icon><strong>${property.bedrooms || 0}</strong><span>Bedrooms</span></div>
            <div class="spec-card"><ion-icon name="water-outline"></ion-icon><strong>${property.bathrooms || 0}</strong><span>Bathrooms</span></div>
            <div class="spec-card"><ion-icon name="resize-outline"></ion-icon><strong>${property.area || property.propertySize || '—'}</strong><span>${property.area ? 'Sqm' : 'Size'}</span></div>
            <div class="spec-card"><ion-icon name="home-outline"></ion-icon><strong>${escapeHtml(property.propertyCategory || 'Home')}</strong><span>Category</span></div>
          </div>

          <div class="section-card">
            <h2 class="section-heading">About this property</h2>
            <div class="description-text">${escapeHtml(property.description || 'Contact our team for a full briefing on this property, title documents, and inspection availability.')}</div>
          </div>

          ${features.length ? `
          <div class="section-card">
            <h2 class="section-heading">Features &amp; amenities</h2>
            <div class="feature-chips">
              ${features.map((feature) => `<span class="feature-chip"><ion-icon name="checkmark-circle-outline"></ion-icon>${escapeHtml(feature)}</span>`).join('')}
            </div>
          </div>` : ''}

          <div class="section-card">
            <h2 class="section-heading">Property information</h2>
            <dl class="info-table">${renderInfoRows(property)}</dl>
          </div>

          <div class="section-card">
            <h2 class="section-heading">${video ? 'Video tour' : 'Media'}</h2>
            ${video
              ? `<video src="${escapeHtml(video)}" controls playsinline preload="metadata" poster="${escapeHtml(galleryImages[0])}">Your browser does not support video playback.</video>`
              : `<div class="video-placeholder"><span><ion-icon name="videocam-outline"></ion-icon>Video tour available on request</span></div>`
            }
          </div>
        </div>

        <aside class="detail-sidebar">
          <div class="price-card">
            <p class="price-label">${escapeHtml(listingLabel(property))}</p>
            <p class="price-value">${formatPrice(property.price, property.type)}</p>
            <p class="price-note">Verified listing by Biver Royalty Homes</p>
            <div class="price-stats">
              <div class="price-stat"><strong>${property.bedrooms || 0}</strong><span>Beds</span></div>
              <div class="price-stat"><strong>${property.bathrooms || 0}</strong><span>Baths</span></div>
              <div class="price-stat"><strong>${property.area || '—'}</strong><span>${property.area ? 'Sqm' : 'Size'}</span></div>
            </div>
            <div class="cta-stack">
              <a href="${contactUrl}" class="cta-btn cta-btn-primary"><ion-icon name="calendar-outline"></ion-icon>Schedule Viewing</a>
              <a href="${whatsappUrl}" class="cta-btn cta-btn-outline" target="_blank" rel="noopener noreferrer"><ion-icon name="logo-whatsapp"></ion-icon>Chat on WhatsApp</a>
              <a href="tel:+2349033137432" class="cta-btn cta-btn-ghost"><ion-icon name="call-outline"></ion-icon>+234 903 313 7432</a>
              <button type="button" class="cta-btn cta-btn-ghost" id="shareBtn"><ion-icon name="share-social-outline"></ion-icon>Share Listing</button>
            </div>
            <div class="trust-row">
              <div class="trust-item"><ion-icon name="shield-checkmark-outline"></ion-icon>Verified listing</div>
              <div class="trust-item"><ion-icon name="people-outline"></ion-icon>Expert agents</div>
              <div class="trust-item"><ion-icon name="document-text-outline"></ion-icon>Title support</div>
            </div>
          </div>
        </aside>
      </div>

      <section class="related-section" id="relatedSection" hidden>
        <h2>You may also like</h2>
        <div class="related-grid" id="relatedGrid"></div>
      </section>
    `;

    bindGalleryEvents();

    document.getElementById('shareBtn')?.addEventListener('click', async () => {
      const shareData = { title: property.title, text: `Check out this property: ${property.title}`, url: window.location.href };
      try {
        if (navigator.share) await navigator.share(shareData);
        else {
          await navigator.clipboard.writeText(window.location.href);
          alert('Link copied to clipboard.');
        }
      } catch (_) { /* user cancelled */ }
    });

    loadRelated(property.id);
  }

  async function loadRelated(currentId) {
    try {
      const res = await fetch(`${API}?limit=4`);
      const data = await res.json();
      const list = (data.properties || []).filter((p) => String(p.id) !== String(currentId)).slice(0, 3);
      if (!list.length) return;

      document.getElementById('relatedSection').hidden = false;
      document.getElementById('relatedGrid').innerHTML = list.map((p) => {
        const img = getAllImages(p)[0];
        const detailUrl = window.BIVER_SITE?.propertyDetail
          ? window.BIVER_SITE.propertyDetail(p._id || p.id)
          : 'property-detail?id=' + encodeURIComponent(p._id || p.id);
        return `
          <a href="${detailUrl}" class="related-card">
            <img src="${escapeHtml(img)}" alt="${escapeHtml(p.title)}" loading="lazy">
            <div class="related-card-body">
              <h3>${escapeHtml(p.title)}</h3>
              <p>${escapeHtml(p.location || '')}</p>
              <span class="related-price">${formatPrice(p.price, p.type)}</span>
            </div>
          </a>`;
      }).join('');
    } catch (_) { /* optional section */ }
  }

  function renderError(message) {
    document.getElementById('detailContent').innerHTML = `
      <div class="state-box">
        <h2>Property Not Available</h2>
        <p>${escapeHtml(message)}</p>
        <a href="<?= siteEscape(pageUrl('property')) ?>" class="btn btn-primary">Browse All Properties</a>
      </div>`;
  }

  async function loadProperty() {
    const id = new URLSearchParams(window.location.search).get('id');
    if (!id) {
      renderError('No property was selected.');
      return;
    }

    try {
      const res = await fetch(`${API}?id=${encodeURIComponent(id)}`);
      const data = await res.json();
      if (!res.ok || data.success === false || !data.property) {
        throw new Error(data.message || 'Property not found.');
      }
      renderProperty(data.property);
    } catch (err) {
      renderError(err.message || 'Unable to load this property.');
    }
  }

  loadProperty();
})();
</script>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>
