<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<?php require_once __DIR__ . '/includes/SeoService.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'Houses for Sale & Rent in Owerri, Imo State | Biver Royalty',
    'description' => 'Browse verified houses, apartments, and land for sale or rent in Owerri with Biver Royalty Homes Ltd. Filter by type, price, and neighbourhood.',
    'keywords' => 'houses for sale Owerri, houses for rent Owerri, properties Imo State, apartments Owerri, Biver Royalty Homes listings',
    'page' => 'property',
    'stylesheets' => ['./assets/css/property.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'Properties'],
    ],
]);
?>
</head>
<body>

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

  <main id="main-content">
    <section class="properties-hero">
      <div class="container">
        <h1>Houses for Sale &amp; Rent in <span class="gold-accent">Owerri</span></h1>
        <p>Explore verified homes, apartments, and land across Owerri, Imo State — listed by Biver Royalty Homes Ltd</p>
      </div>
    </section>

    <div class="filter-bar">
      <div class="container">
        <div class="filter-form">
          <div class="filter-group"><input type="text" id="propertyFilterInput" placeholder="Search by location or title..."></div>
          <div class="filter-group"><select id="typeFilter"><option value="">All Types</option><option value="sale">For Sale</option><option value="rent">For Rent</option></select></div>
          <div class="filter-group"><select id="sortFilter"><option value="newest">Newest First</option><option value="price_low">Price: Low to High</option><option value="price_high">Price: High to Low</option></select></div>
          <button class="search-btn" id="searchBtn"><ion-icon name="search-outline"></ion-icon>Search Properties</button>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="properties-toolbar">
        <div class="view-toggle">
          <button class="view-btn active" data-view="grid"><ion-icon name="grid-outline"></ion-icon> Grid</button>
          <button class="view-btn" data-view="list"><ion-icon name="list-outline"></ion-icon> List</button>
        </div>
        <div><span id="resultsCount">Loading properties...</span></div>
      </div>

      <div id="propertiesContainer">
        <div class="skeleton-grid" id="skeletonLoader">
          <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div></div></div>
          <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div></div></div>
          <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div></div></div>
          <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div></div></div>
          <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div></div></div>
          <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div></div></div>
        </div>
        <div id="propertiesGrid" class="properties-grid u-hidden"></div>
        <div id="errorState" class="error-state u-hidden">
          <ion-icon name="alert-circle-outline"></ion-icon>
          <h3>Unable to Load Properties</h3>
          <p>We're having trouble connecting to our property database. Please check your internet connection and try again.</p>
          <button class="retry-btn" onclick="location.reload()">Try Again</button>
        </div>
      </div>

      <div class="pagination" id="pagination"></div>
    </div>
  </main>

  <!-- =============================================
       FOOTER
  ============================================= -->
  <footer class="footer" role="contentinfo">
    <div class="footer-top">
      <div class="container">
        <div class="footer-brand">
          <a href="<?= pageHref('index') ?>" class="logo">
            <img src="./assets/images/biver-logo.png" alt="Biver Royalty Homes" width="150" height="auto" loading="lazy">
          </a>
          <p class="section-text">
            We are a real estate company built on Integrity. We help our clients bring their dream homes to reality within their budget.
          </p>
          <ul class="contact-list">
            <li>
              <a href="<?= pageHref('contact') ?>" class="contact-link">
                <ion-icon name="location-outline"></ion-icon>
                <address>No. 31 Wetheral Road, Angelina Plaza Opposite Reem Fuel Station Owerri, Imo State.</address>
              </a>
            </li>
            <li>
              <a href="tel:+2349036851168" class="contact-link">
                <ion-icon name="call-outline"></ion-icon>
                <span>+234 903 685 1168</span>
              </a>
            </li>
            <li>
              <a href="mailto:biverroyaltyhomes01@gmail.com" class="contact-link">
                <ion-icon name="mail-outline"></ion-icon>
                <span>biverroyaltyhomes01@gmail.com</span>
              </a>
            </li>
          </ul>
          <ul class="social-list">
            <li><a href="https://www.facebook.com/share/1B8mwpRi5L/" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><ion-icon name="logo-facebook"></ion-icon></a></li>
            <li><a href="#" class="social-link" aria-label="Twitter"><ion-icon name="logo-twitter"></ion-icon></a></li>
            <li><a href="https://www.instagram.com/biverroyaltyhomes.ng" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><ion-icon name="logo-instagram"></ion-icon></a></li>
            <li><a href="https://www.tiktok.com/@biverroyaltyhomesltd" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><ion-icon name="logo-youtube"></ion-icon></a></li>
          </ul>
        </div>
        <div class="footer-link-box">
          <ul class="footer-list">
            <li><p class="footer-list-title">Company</p></li>
            <li><a href="<?= pageHref('about') ?>" class="footer-link">About Us</a></li>
            <li><a href="<?= pageHref('blog') ?>" class="footer-link">Blog</a></li>
            <li><a href="<?= pageHref('property') ?>" class="footer-link">All Properties</a></li>
            <li><a href="<?= pageHref('locations') ?>" class="footer-link">Owerri Locations</a></li>
            <li><a href="<?= pageHref('faqs') ?>" class="footer-link">FAQ</a></li>
            <li><a href="<?= pageHref('contact') ?>" class="footer-link">Contact Us</a></li>
          </ul>
          <ul class="footer-list">
            <li><p class="footer-list-title">Services</p></li>
            <li><a href="<?= pageHref('services') ?>" class="footer-link">Our Services</a></li>
            <li><a href="<?= pageHref('property') ?>" class="footer-link">Buy a Home</a></li>
            <li><a href="<?= pageHref('property') ?>" class="footer-link">Rent a Home</a></li>
            <li><a href="<?= pageHref('list-your-property') ?>" class="footer-link">List Your Property</a></li>
            <li><a href="<?= pageHref('services') ?>" class="footer-link">Estate Management</a></li>
            <li><a href="<?= pageHref('contact') ?>" class="footer-link">Property Consultation</a></li>
          </ul>
          <ul class="footer-list">
            <li><p class="footer-list-title">Explore</p></li>
            <li><a href="<?= pageHref('property') ?>" class="footer-link">Browse Properties</a></li>
            <li><a href="<?= pageHref('list-your-property') ?>" class="footer-link">Sell With Us</a></li>
            <li><a href="<?= pageHref('faqs') ?>" class="footer-link">FAQ</a></li>
            <li><a href="<?= pageHref('blog') ?>" class="footer-link">Blog</a></li>
            <li><a href="<?= pageHref('contact') ?>" class="footer-link">Contact Us</a></li>
            <li><a href="<?= pageHref('terms') ?>" class="footer-link">Terms &amp; Conditions</a></li>
            <li><a href="<?= pageHref('privacy') ?>" class="footer-link">Privacy Policy</a></li>
            <li><a href="<?= pageHref('cookie-policy') ?>" class="footer-link">Cookie Policy</a></li>
          </ul>
        </div>
      </div>
    </div>
    <?php require __DIR__ . '/assets/includes/newsletter-strip.php'; ?>
    <div class="footer-bottom">
      <div class="container">
        <p class="copyright">
          &copy; 2025 <a href="#">Biver Royalty Homes</a>. All Rights Reserved | Designed by <a href="#">ERIBS Tech</a>
        </p>
      </div>
    </div>
  </footer>

  <button id="scrollToTop"><ion-icon name="chevron-up-outline"></ion-icon></button>

    <script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <script>
    const PROPERTIES_API = window.BIVER_SITE?.propertiesApi || 'api/properties.php';
    let allProperties = [];
    let propertiesLoadFailed = false;
    let currentPage = 1;
    let itemsPerPage = 3;
    let currentView = 'grid';
    let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');

    function showToast(message) {
      const existing = document.querySelector('.toast-notification');
      if (existing) existing.remove();
      const toast = document.createElement('div');
      toast.className = 'toast-notification';
      toast.innerHTML = `<ion-icon name="checkmark-circle-outline"></ion-icon><span>${message}</span>`;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3000);
    }

    function toggleFavorite(propertyId, btnElement) {
      const index = favorites.indexOf(propertyId);
      if (index === -1) { favorites.push(propertyId); showToast('Added to favorites!'); }
      else { favorites.splice(index, 1); showToast('Removed from favorites'); }
      localStorage.setItem('favorites', JSON.stringify(favorites));
      if (btnElement) { btnElement.classList.toggle('active'); btnElement.querySelector('ion-icon').name = favorites.includes(propertyId) ? 'heart' : 'heart-outline'; }
      renderProperties();
    }

    function formatPrice(price, type) {
      if (type === 'rent') return `\u20A6${Number(price).toLocaleString()}/month`;
      return `\u20A6${Number(price).toLocaleString()}`;
    }

    function getPropertyImage(property) {
      if (property.imageUrl) return resolveMediaUrl(property.imageUrl);
      if (property.images && property.images[0]) return resolveMediaUrl(property.images[0]);
      return 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop';
    }

    function resolveMediaUrl(url) {
      if (!url) return '';
      if (url.startsWith('http') || url.startsWith('/')) return url;
      if (url.startsWith('assets/')) return './' + url;
      return url;
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      }[m]));
    }

    function createPropertyCard(property) {
      const isFavorite = favorites.includes(property._id);
      const imageUrl = getPropertyImage(property);
      const badgeClass = property.type === 'rent' ? 'rent' : 'sale';
      const badgeText = property.type === 'rent' ? 'For Rent' : 'For Sale';
      const detailUrl = window.BIVER_SITE?.propertyDetail
        ? window.BIVER_SITE.propertyDetail(property._id)
        : 'property-detail?id=' + encodeURIComponent(property._id);
      return `
        <div class="property-card">
          <div class="card-banner">
            <img src="${imageUrl}" alt="${escapeHtml(property.title)}" loading="lazy">
            <div class="card-badge ${badgeClass}">${badgeText}</div>
            <button class="favorite-btn ${isFavorite ? 'active' : ''}" onclick="event.stopPropagation(); toggleFavorite('${property._id}', this)">
              <ion-icon name="${isFavorite ? 'heart' : 'heart-outline'}"></ion-icon>
            </button>
          </div>
          <div class="card-content">
            <div class="card-price">${formatPrice(property.price, property.type)}</div>
            <h3 class="card-title">${escapeHtml(property.title)}</h3>
            <div class="card-location"><ion-icon name="location-outline"></ion-icon> ${escapeHtml(property.location || 'Owerri, Imo')}</div>
            <div class="card-features">
              <div class="feature"><ion-icon name="bed-outline"></ion-icon> ${property.bedrooms || 2} Beds</div>
              <div class="feature"><ion-icon name="water-outline"></ion-icon> ${property.bathrooms || 2} Baths</div>
              ${property.area ? `<div class="feature"><ion-icon name="square-outline"></ion-icon> ${property.area} sq ft</div>` : ''}
            </div>
          </div>
          <div class="card-footer">
            <div class="agent-info">
              <div class="agent-avatar"><ion-icon name="person-outline"></ion-icon></div>
              <span class="agent-name">Biver Royalty</span>
            </div>
            <a href="${detailUrl}" class="view-property">View Details <ion-icon name="arrow-forward-outline"></ion-icon></a>
          </div>
        </div>
      `;
    }

    function hideEl(el) { if (el) el.classList.add('u-hidden'); }
    function showEl(el) { if (el) el.classList.remove('u-hidden'); }

    function renderProperties() {
      const grid = document.getElementById('propertiesGrid');
      const skeleton = document.getElementById('skeletonLoader');
      const errorState = document.getElementById('errorState');
      const resultsCount = document.getElementById('resultsCount');
      
      if (propertiesLoadFailed) {
        hideEl(skeleton);
        hideEl(grid);
        showEl(errorState);
        errorState.innerHTML = '<ion-icon name="alert-circle-outline"></ion-icon><h3>Unable to Load Properties</h3><p>We\'re having trouble connecting to our property database. Please check your connection and try again.</p><button class="retry-btn" onclick="location.reload()">Try Again</button>';
        resultsCount.textContent = '0 properties';
        document.getElementById('pagination').innerHTML = '';
        return;
      }

      if (!allProperties.length) {
        hideEl(skeleton);
        hideEl(grid);
        showEl(errorState);
        errorState.innerHTML = '<ion-icon name="home-outline"></ion-icon><h3>No Properties Available</h3><p>There are currently no approved listings. Please check back soon or contact us for assistance.</p><a href="' + (window.BIVER_SITE?.page ? window.BIVER_SITE.page('contact') : 'contact') + '" class="retry-btn">Contact Us</a>';
        resultsCount.textContent = '0 properties';
        document.getElementById('pagination').innerHTML = '';
        return;
      }
      
      const filtered = allProperties;
      const typeFilter = document.getElementById('typeFilter').value;
      const searchTerm = document.getElementById('propertyFilterInput').value.toLowerCase();
      const sortBy = document.getElementById('sortFilter').value;
      
      let filteredProps = filtered.filter(p => {
        if (typeFilter && p.type !== typeFilter) return false;
        if (searchTerm && !p.title.toLowerCase().includes(searchTerm) && !(p.location || '').toLowerCase().includes(searchTerm)) return false;
        return true;
      });
      
      if (sortBy === 'price_low') filteredProps.sort((a,b) => a.price - b.price);
      else if (sortBy === 'price_high') filteredProps.sort((a,b) => b.price - a.price);
      else filteredProps.sort((a,b) => new Date(b.createdAt) - new Date(a.createdAt));

      const totalPages = Math.max(1, Math.ceil(filteredProps.length / itemsPerPage));
      if (currentPage > totalPages) currentPage = totalPages;
      const start = (currentPage - 1) * itemsPerPage;
      const paginated = filteredProps.slice(start, start + itemsPerPage);

      resultsCount.textContent = filteredProps.length
        ? `Showing ${paginated.length} of ${filteredProps.length} properties (page ${currentPage} of ${totalPages})`
        : '0 properties';
      
      if (paginated.length === 0) {
        hideEl(grid);
        hideEl(skeleton);
        showEl(errorState);
        errorState.innerHTML = '<ion-icon name="home-outline"></ion-icon><h3>No properties found</h3><p>Try adjusting your search or filters</p><button class="retry-btn" onclick="document.getElementById(\'propertyFilterInput\').value=\'\'; document.getElementById(\'typeFilter\').value=\'\'; renderProperties()">Clear Filters</button>';
        document.getElementById('pagination').innerHTML = '';
        return;
      }
      
      hideEl(errorState);
      hideEl(skeleton);
      showEl(grid);
      grid.className = `properties-grid ${currentView === 'list' ? 'list-view' : ''}`;
      grid.innerHTML = paginated.map(p => createPropertyCard(p)).join('');

      const paginationDiv = document.getElementById('pagination');
      if (totalPages <= 1) { paginationDiv.innerHTML = ''; return; }

      let pagesHtml = '';
      if (currentPage > 1) {
        pagesHtml += `<button class="page-btn" data-page="${currentPage - 1}" aria-label="Previous page">&lsaquo;</button>`;
      }
      for (let i = 1; i <= totalPages; i++) {
        pagesHtml += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
      }
      if (currentPage < totalPages) {
        pagesHtml += `<button class="page-btn" data-page="${currentPage + 1}" aria-label="Next page">&rsaquo;</button>`;
      }
      paginationDiv.innerHTML = pagesHtml;
      document.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', () => { currentPage = parseInt(btn.dataset.page); renderProperties(); window.scrollTo({ top: 400, behavior: 'smooth' }); });
      });
    }

    async function loadProperties() {
      const skeleton = document.getElementById('skeletonLoader');
      const errorState = document.getElementById('errorState');
      showEl(skeleton);
      hideEl(errorState);

      try {
        const response = await fetch(`${PROPERTIES_API}?limit=100`);
        const data = await response.json();

        if (!response.ok || data.success === false) {
          throw new Error(data.message || 'Failed to load properties');
        }

        allProperties = data.properties || [];
        propertiesLoadFailed = false;
      } catch (error) {
        console.error('Property load failed:', error.message);
        allProperties = [];
        propertiesLoadFailed = true;
      }

      currentPage = 1;
      renderProperties();
    }
    
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentView = btn.dataset.view;
        renderProperties();
      });
    });
    
    document.getElementById('searchBtn').addEventListener('click', () => { currentPage = 1; renderProperties(); });
    document.getElementById('propertyFilterInput').addEventListener('keypress', (e) => { if (e.key === 'Enter') { currentPage = 1; renderProperties(); } });
    document.getElementById('typeFilter').addEventListener('change', () => { currentPage = 1; renderProperties(); });
    document.getElementById('sortFilter').addEventListener('change', () => { renderProperties(); });

    (function applySearchFromUrl() {
      const params = new URLSearchParams(window.location.search);
      const q = (params.get('search') || params.get('q') || '').trim();
      const input = document.getElementById('propertyFilterInput');
      if (q && input) input.value = q;
    })();
    
    loadProperties();
  </script>
  <?php require __DIR__ . '/assets/includes/site-end.php'; ?>
  <?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>