/**
 * Admin mobile sidebar toggle — shared across all admin pages.
 */
(function () {
  'use strict';

  function isMobileNav() {
    return window.matchMedia('(max-width: 992px)').matches;
  }

  function initAdminMobileNav() {
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (!toggle || !sidebar) {
      return;
    }

    if (toggle.dataset.navBound === '1') {
      return;
    }
    toggle.dataset.navBound = '1';

    let backdrop = document.getElementById('adminSidebarBackdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'adminSidebarBackdrop';
      backdrop.className = 'admin-sidebar-backdrop';
      backdrop.setAttribute('aria-hidden', 'true');
      document.body.appendChild(backdrop);
    }

    function setSidebarOpen(open) {
      sidebar.classList.toggle('open', open);
      sidebar.classList.toggle('active', open);
      backdrop.classList.toggle('is-visible', open);
      document.body.classList.toggle('admin-nav-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeSidebar() {
      setSidebarOpen(false);
    }

    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!isMobileNav()) {
        return;
      }
      const willOpen = !sidebar.classList.contains('open');
      setSidebarOpen(willOpen);
    });

    backdrop.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('.nav-link, .logout-btn').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobileNav()) {
          closeSidebar();
        }
      });
    });

    window.addEventListener('resize', function () {
      if (!isMobileNav()) {
        closeSidebar();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeSidebar();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminMobileNav);
  } else {
    initAdminMobileNav();
  }
})();
