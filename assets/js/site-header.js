/**
 * Shared header: sticky scroll, mobile nav, active link, property search modal.
 */
(function () {
  'use strict';

  const API_BASE_URL = 'https://api.biverroyaltyhomesltd.com/api';

  function formatNumber(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function initHeaderScroll() {
    const header = document.getElementById('header');
    if (!header) return;
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 80);
    }, { passive: true });
  }

  function initNavbar() {
    const navbar = document.querySelector('[data-navbar]');
    const overlay = document.querySelector('[data-overlay]');
    const navCloseBtn = document.querySelector('[data-nav-close-btn]');
    const navOpenBtn = document.querySelector('[data-nav-open-btn]');
    const navbarLinks = document.querySelectorAll('[data-nav-link]');

    if (!navbar || !overlay) return;

    let navIsOpen = false;

    function syncMobileHeaderOffset() {
      const headerEl = document.getElementById('header');
      if (!headerEl || window.innerWidth >= 1200) {
        document.documentElement.style.removeProperty('--mobile-header-height');
        return;
      }
      document.documentElement.style.setProperty(
        '--mobile-header-height',
        `${headerEl.offsetHeight}px`
      );
    }

    function openNav() {
      if (navIsOpen) return;
      syncMobileHeaderOffset();
      navIsOpen = true;
      navbar.classList.add('active');
      overlay.classList.add('active');
      document.body.classList.add('nav-open');
      document.body.style.overflow = 'hidden';
      if (navOpenBtn) navOpenBtn.setAttribute('aria-expanded', 'true');
      overlay.setAttribute('aria-hidden', 'false');
      setTimeout(() => {
        const firstLink = navbar.querySelector('.navbar-link');
        if (firstLink) firstLink.focus({ preventScroll: true });
      }, 420);
    }

    function closeNav() {
      if (!navIsOpen) return;
      navIsOpen = false;
      navbar.classList.remove('active');
      overlay.classList.remove('active');
      document.body.classList.remove('nav-open');
      document.body.style.overflow = '';
      overlay.setAttribute('aria-hidden', 'true');
      if (navOpenBtn) {
        navOpenBtn.setAttribute('aria-expanded', 'false');
        navOpenBtn.focus({ preventScroll: true });
      }
    }

    syncMobileHeaderOffset();
    window.addEventListener('resize', syncMobileHeaderOffset, { passive: true });

    if (navOpenBtn) {
      navOpenBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openNav();
      });
    }

    if (navCloseBtn) navCloseBtn.addEventListener('click', closeNav);
    overlay.addEventListener('click', closeNav);

    navbarLinks.forEach((link) => {
      link.addEventListener('click', () => setTimeout(closeNav, 150));
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navIsOpen) closeNav();
    });

    let touchStartX = 0;
    let touchStartY = 0;
    navbar.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].clientX;
      touchStartY = e.changedTouches[0].clientY;
    }, { passive: true });
    navbar.addEventListener('touchend', (e) => {
      const dx = e.changedTouches[0].clientX - touchStartX;
      const dy = Math.abs(e.changedTouches[0].clientY - touchStartY);
      if (dx < -60 && dy < 80) closeNav();
    }, { passive: true });
  }

  function initActiveNavLink() {
    const path = window.location.pathname;
    const page = path.split('/').pop() || 'index.html';

    document.querySelectorAll('.navbar-link').forEach((link) => {
      const href = link.getAttribute('href') || '';
      if (!href || href.startsWith('http')) return;
      const linkPage = href.split('/').pop();
      const isActive =
        linkPage === page ||
        (page === '' && linkPage === 'index.html');

      if (isActive) {
        link.classList.add('active-page');
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  let searchCache = null;
  let activeFilter = 'all';

  function initSearchModal() {
    const modal = document.getElementById('searchModal');
    const closeBtn = document.getElementById('searchModalClose');
    const input = document.getElementById('searchInput');
    const resultsEl = document.getElementById('searchResults');
    const filterBtns = document.querySelectorAll('.search-filter-chip');
    const openBtns = document.querySelectorAll('[data-search-open]');

    if (!modal) return;

    let searchDebounceTimer;

    function openModal() {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
      openBtns.forEach((b) => b.setAttribute('aria-expanded', 'true'));
      setTimeout(() => input && input.focus(), 350);
      if (!searchCache) loadSearchData();
    }

    function closeModal() {
      modal.classList.remove('open');
      if (!document.querySelector('.navbar.active')) {
        document.body.style.overflow = '';
      }
      openBtns.forEach((b) => b.setAttribute('aria-expanded', 'false'));
      if (input) input.value = '';
      if (resultsEl) {
        resultsEl.innerHTML = '';
        resultsEl.classList.remove('has-results');
      }
    }

    openBtns.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        openModal();
      });
    });
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });

    filterBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        filterBtns.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter;
        renderResults(input ? input.value.trim() : '');
      });
    });

    if (input) {
      input.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
          renderResults(input.value.trim());
        }, 250);
      });
    }
  }

  async function loadSearchData() {
    const resultsEl = document.getElementById('searchResults');
    if (!resultsEl) return;
    resultsEl.innerHTML = '<div class="search-loading">Loading properties...</div>';
    resultsEl.classList.add('has-results');
    try {
      const response = await fetch(`${API_BASE_URL}/properties?limit=100`);
      const data = await response.json();
      searchCache = (data.properties || []).filter((p) => p.approvalStatus === 'approved');
      resultsEl.innerHTML = '';
      resultsEl.classList.remove('has-results');
    } catch {
      resultsEl.innerHTML =
        '<div class="search-no-results"><ion-icon name="alert-circle-outline"></ion-icon><p>Could not load properties. Check your connection.</p></div>';
    }
  }

  function renderResults(query) {
    const resultsEl = document.getElementById('searchResults');
    if (!resultsEl || !searchCache) return;

    let filtered = searchCache;

    if (activeFilter === 'sale') filtered = filtered.filter((p) => p.type !== 'rent');
    else if (activeFilter === 'rent') filtered = filtered.filter((p) => p.type === 'rent');
    else if (activeFilter === '1') filtered = filtered.filter((p) => p.bedrooms == 1);
    else if (activeFilter === '2') filtered = filtered.filter((p) => p.bedrooms == 2);
    else if (activeFilter === '3') filtered = filtered.filter((p) => (p.bedrooms || 0) >= 3);

    if (query.length > 0) {
      const q = query.toLowerCase();
      filtered = filtered.filter(
        (p) =>
          (p.title || '').toLowerCase().includes(q) ||
          (p.location || '').toLowerCase().includes(q) ||
          (p.description || '').toLowerCase().includes(q) ||
          String(p.price || '').includes(q)
      );
    }

    if (query.length === 0 && activeFilter === 'all') {
      resultsEl.innerHTML = '';
      resultsEl.classList.remove('has-results');
      return;
    }

    if (filtered.length === 0) {
      resultsEl.innerHTML =
        '<div class="search-no-results"><ion-icon name="home-outline"></ion-icon><p>No properties found. Try a different search.</p></div>';
      resultsEl.classList.add('has-results');
      return;
    }

    const base = API_BASE_URL.replace('/api', '');
    resultsEl.innerHTML = filtered
      .slice(0, 12)
      .map((p) => {
        const imgSrc =
          p.images && p.images.length > 0
            ? p.images[0].startsWith('http')
              ? p.images[0]
              : base + p.images[0]
            : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=120&h=80&fit=crop';
        const price =
          p.type === 'rent'
            ? '₦' + formatNumber(p.price) + '/mo'
            : '₦' + formatNumber(p.price);
        const badgeClass = p.type === 'rent' ? 'rent' : '';
        const badgeLabel = p.type === 'rent' ? 'For Rent' : 'For Sale';
        return `<a href="property-detail.html?id=${p._id}" class="search-result-item">
          <img src="${imgSrc}" alt="${p.title || ''}" class="search-result-thumb" loading="lazy">
          <div class="search-result-info">
            <div class="search-result-title">${p.title || 'Property'}</div>
            <div class="search-result-meta">${p.location || 'Owerri'} &bull; ${price}</div>
          </div>
          <span class="search-result-badge ${badgeClass}">${badgeLabel}</span>
        </a>`;
      })
      .join('');
    resultsEl.classList.add('has-results');
  }

  function initScrollToTop() {
    const btn = document.getElementById('scrollToTop');
    if (!btn) return;
    window.addEventListener(
      'scroll',
      () => {
        btn.classList.toggle('visible', window.scrollY > 600);
      },
      { passive: true }
    );
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  function init() {
    initHeaderScroll();
    initNavbar();
    initActiveNavLink();
    initSearchModal();
    initScrollToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.BiverSiteHeader = { init };
})();
