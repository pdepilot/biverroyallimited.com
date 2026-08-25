<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<?php
require_once __DIR__ . '/includes/PageContentService.php';
require_once __DIR__ . '/includes/site_helpers.php';
require_once __DIR__ . '/includes/SeoService.php';
$aboutPage = PageContentService::getPage('about');
$aboutHero = $aboutPage['hero'] ?? [];
$aboutNarrative = $aboutPage['narrative'] ?? [];
$aboutPhilosophy = $aboutPage['philosophy']['items'] ?? [];
$aboutJourney = $aboutPage['journey'] ?? [];
$aboutJourneyItems = $aboutJourney['items'] ?? [];
$aboutValues = $aboutPage['values'] ?? [];
$aboutValueItems = $aboutValues['items'] ?? [];
$aboutTeam = $aboutPage['team'] ?? [];
$aboutTeamMembers = $aboutTeam['members'] ?? [];
$aboutCta = $aboutPage['cta'] ?? [];
$ctaLink = pageHref((string) ($aboutCta['link'] ?? 'contact'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'About Biver Royalty Homes Ltd | Real Estate Agency in Owerri',
    'description' => 'Meet Biver Royalty Homes Ltd — Owerri’s integrity-first real estate agency on Wetheral Road, helping families buy, rent, and sell homes across Imo State.',
    'keywords' => 'about Biver Royalty Homes Ltd, real estate agency Owerri, estate agent Imo State, Wetheral Road Owerri',
    'page' => 'about',
    'stylesheets' => ['./assets/css/about.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'About Us'],
    ],
]);
?>
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- =============================================
       TOP BAR
  ============================================= -->
  <div class="topbar" role="banner">
    <div class="topbar-inner">
      <div class="topbar-left">
        <a href="mailto:biverroyaltyhomes01@gmail.com" class="topbar-link" aria-label="Email us">
          <ion-icon name="mail-outline"></ion-icon>
          <span>biverroyaltyhomes01@gmail.com</span>
        </a>
        <div class="topbar-divider" aria-hidden="true"></div>
        <a href="#" class="topbar-link" aria-label="Our location">
          <ion-icon name="location-outline"></ion-icon>
          <address>No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State</address>
        </a>
        <div class="topbar-divider" aria-hidden="true"></div>
        <a href="tel:+2349036851168" class="topbar-link" aria-label="Call us">
          <ion-icon name="call-outline"></ion-icon>
          <span>+234 903 685 1168</span>
        </a>
      </div>
      <div class="topbar-right">
        <div class="topbar-socials" aria-label="Social media links">
          <a href="https://www.facebook.com/share/1B8mwpRi5L/" class="topbar-social-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
            <ion-icon name="logo-facebook"></ion-icon>
          </a>
          <a href="https://www.instagram.com/biverroyaltyhomes.ng" class="topbar-social-link" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <ion-icon name="logo-instagram"></ion-icon>
          </a>
          <a href="https://www.tiktok.com/@biverroyaltyhomesltd" class="topbar-social-link" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
            <ion-icon name="logo-youtube"></ion-icon>
          </a>
        </div>
        <a href="<?= pageHref('list-your-property') ?>" class="topbar-cta">List Property</a>
      </div>
    </div>
  </div>

  <!-- =============================================
       SEARCH MODAL � Feature #2
       Fullscreen property search with instant filtering.
       Triggered by the search button in the header/bottom bar.
  ============================================= -->
  <div id="searchModal" role="dialog" aria-modal="true" aria-label="Search properties">
    <button class="search-modal-close" id="searchModalClose" aria-label="Close search">
      <ion-icon name="close-outline"></ion-icon>
    </button>
    <p class="search-modal-eyebrow">Property Search</p>
    <h2 class="search-modal-heading">Find Your <em>Dream</em> Home</h2>
    <div class="search-input-wrap">
      <input type="search" id="searchInput"
        placeholder="Search by location, type, or price..."
        autocomplete="off" spellcheck="false"
        aria-label="Search properties">
      <ion-icon name="search-outline" class="search-input-icon" aria-hidden="true"></ion-icon>
    </div>
    <div class="search-filters" role="group" aria-label="Filter by type">
      <button class="search-filter-chip active" data-filter="all">All</button>
      <button class="search-filter-chip" data-filter="sale">For Sale</button>
      <button class="search-filter-chip" data-filter="rent">For Rent</button>
      <button class="search-filter-chip" data-filter="1">1 Bed</button>
      <button class="search-filter-chip" data-filter="2">2 Beds</button>
      <button class="search-filter-chip" data-filter="3">3+ Beds</button>
    </div>
    <div class="search-results-wrap" id="searchResults" aria-live="polite" aria-label="Search results"></div>
  </div>

  <!-- =============================================
       HEADER
  ============================================= -->
  <header class="header" id="header" data-header>
    <div class="header-bottom">
      <div class="container">
        <a href="<?= pageHref('index') ?>" class="logo" aria-label="Biver Royalty Homes Home">
          <img src="./assets/images/biver-logo.png" alt="Biver Royalty Homes" width="auto" height="50">
        </a>

        <nav class="navbar" data-navbar aria-label="Main navigation">
          <div class="navbar-inner-wrap">
            <div class="navbar-top">
              <a href="<?= pageHref('index') ?>" class="logo">
                <img src="./assets/images/biver-logo.png" alt="Biver Royalty Homes" width="140">
              </a>
              <button class="nav-close-btn" data-nav-close-btn aria-label="Close Menu">
                <ion-icon name="close-outline"></ion-icon>
              </button>
            </div>

            <div class="navbar-bottom">
              <ul class="navbar-list" role="menubar">
                <li role="none"><a href="<?= pageHref('index') ?>" class="navbar-link" data-nav-link role="menuitem">Home</a></li>
                <li role="none"><a href="<?= pageHref('about') ?>" class="navbar-link" data-nav-link role="menuitem">About</a></li>
                <li role="none"><a href="<?= pageHref('services') ?>" class="navbar-link" data-nav-link role="menuitem">Services</a></li>
                <li role="none"><a href="<?= pageHref('property') ?>" class="navbar-link" data-nav-link role="menuitem">Properties</a></li>
                <li role="none"><a href="<?= pageHref('locations') ?>" class="navbar-link" data-nav-link role="menuitem">Locations</a></li>
                <li role="none"><a href="<?= pageHref('faqs') ?>" class="navbar-link" data-nav-link role="menuitem">FAQs</a></li>
                <li role="none"><a href="<?= pageHref('blog') ?>" class="navbar-link" data-nav-link role="menuitem">Blog</a></li>
                <li role="none"><a href="<?= pageHref('contact') ?>" class="navbar-link" data-nav-link role="menuitem">Contact</a></li>
              </ul>
            </div>

            <div class="navbar-footer">
              <p class="navbar-footer-title">Get in Touch</p>
              <a href="tel:+2349036851168" class="navbar-footer-link">
                <ion-icon name="call-outline"></ion-icon>
                <span>+234 903 685 1168</span>
              </a>
              <a href="mailto:biverroyaltyhomes01@gmail.com" class="navbar-footer-link">
                <ion-icon name="mail-outline"></ion-icon>
                <span>biverroyaltyhomes01@gmail.com</span>
              </a>
              <a href="<?= pageHref('list-your-property') ?>" class="navbar-cta">List Your Property</a>
            </div>
          </div>
        </nav>

        <div class="header-bottom-actions">
          <button class="header-bottom-actions-btn" data-search-open aria-label="Search properties" aria-expanded="false" aria-controls="searchModal">
            <ion-icon name="search-outline"></ion-icon>
            <span>Search</span>
          </button>
          <button class="header-bottom-actions-btn" data-nav-open-btn aria-label="Open Menu" aria-expanded="false">
            <ion-icon name="menu-outline"></ion-icon>
            <span>Menu</span>
          </button>
        </div>
      </div>
    </div>
  </header>

  <div class="overlay" data-overlay aria-hidden="true"></div>

  <main id="main-content">
    <!-- =============================================
         HERO SECTION - Cinematic Intro (preserved)
    ============================================= -->
    <section class="about-hero">
      <div class="hero-bg-pattern"></div>
      <div class="hero-glow"></div>
      <div class="container">
        <div class="hero-subtitle"><?= siteEscape((string) ($aboutHero['subtitle'] ?? 'EST. 2015')) ?></div>
        <h1 class="hero-title"><?= $aboutHero['title'] ?? 'Architects of <span class="gold-accent">Dreams</span>,<br>Builders of Trust' ?></h1>
        <p class="hero-description"><?= siteEscape((string) ($aboutHero['description'] ?? '')) ?></p>
        <div class="stats-grid">
          <?php foreach (($aboutHero['stats'] ?? []) as $stat): ?>
          <div class="stat-card"><div class="stat-number"><?= siteEscape((string) ($stat['num'] ?? '')) ?></div><div class="stat-label"><?= siteEscape((string) ($stat['label'] ?? '')) ?></div></div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- =============================================
         NARRATIVE - The Untold Story
    ============================================= -->
    <section class="narrative">
      <div class="container">
        <div class="narrative-grid">
          <div class="narrative-content reveal reveal-left">
            <div class="narrative-badge"><div class="line"></div><span><?= siteEscape((string) ($aboutNarrative['badge'] ?? 'The Untold Story')) ?></span></div>
            <h2 class="narrative-title"><?= siteEscape((string) ($aboutNarrative['title'] ?? '')) ?></h2>
            <p class="narrative-text"><?= siteEscape((string) ($aboutNarrative['paragraph1'] ?? '')) ?></p>
            <p class="narrative-text"><?= siteEscape((string) ($aboutNarrative['paragraph2'] ?? '')) ?></p>
            <div class="narrative-quote"><?= siteEscape((string) ($aboutNarrative['quote'] ?? '')) ?></div>
            <div class="signature"><?= siteEscape((string) ($aboutNarrative['signature'] ?? '')) ?></div>
          </div>
          <div class="visual-story reveal reveal-right">
            <img src="<?= siteEscape((string) ($aboutNarrative['mainImage'] ?? './assets/images/engineer1.png')) ?>" alt="Founder at work" class="main-image" loading="lazy">
            <img src="<?= siteEscape((string) ($aboutNarrative['floatImage'] ?? 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=500&auto=format&fit=crop&q=80')) ?>" alt="Early days" class="floating-image" loading="lazy">
            <div class="image-caption"><?= siteEscape((string) ($aboutNarrative['caption'] ?? 'The Journey Begins in Owerri, 2015')) ?></div>
          </div>
        </div>
      </div>
    </section>

    <section class="philosophy">
      <div class="container">
        <div class="philosophy-grid">
          <?php foreach ($aboutPhilosophy as $i => $pillar): ?>
          <div class="pillar-card reveal<?= $i > 0 ? ' reveal-delay-' . min($i, 2) : '' ?>">
            <div class="pillar-icon"><ion-icon name="<?= siteEscape((string) ($pillar['icon'] ?? 'star-outline')) ?>"></ion-icon></div>
            <h3 class="pillar-title"><?= siteEscape((string) ($pillar['title'] ?? '')) ?></h3>
            <p class="pillar-text"><?= siteEscape((string) ($pillar['text'] ?? '')) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="journey">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span><?= siteEscape((string) ($aboutJourney['eyebrow'] ?? 'Our Journey')) ?></span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1"><?= siteEscape((string) ($aboutJourney['title'] ?? '')) ?></h2>
        <div class="timeline">
          <?php foreach ($aboutJourneyItems as $i => $item): ?>
          <div class="timeline-item reveal <?= $i % 2 === 0 ? 'reveal-left' : 'reveal-right' ?>">
            <div class="timeline-year"><?= siteEscape((string) ($item['year'] ?? '')) ?></div>
            <div class="timeline-content">
              <div class="timeline-dot"></div>
              <h4 class="timeline-title"><?= siteEscape((string) ($item['title'] ?? '')) ?></h4>
              <p class="timeline-text"><?= siteEscape((string) ($item['text'] ?? '')) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="values">
      <div class="container">
        <div class="values-grid">
          <div class="values-list">
            <?php foreach ($aboutValueItems as $i => $value): ?>
            <div class="value-item reveal reveal-left<?= $i > 0 ? ' reveal-delay-' . min($i, 3) : '' ?>">
              <div class="value-icon"><ion-icon name="<?= siteEscape((string) ($value['icon'] ?? 'people-outline')) ?>"></ion-icon></div>
              <div class="value-text">
                <h4><?= siteEscape((string) ($value['title'] ?? '')) ?></h4>
                <p><?= siteEscape((string) ($value['text'] ?? '')) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="values-image reveal reveal-right">
            <img src="<?= siteEscape((string) ($aboutValues['image'] ?? 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=600&auto=format&fit=crop&q=80')) ?>" alt="Building community" class="values-main-img" loading="lazy">
          </div>
        </div>
      </div>
    </section>

    <section class="team-preview">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span><?= siteEscape((string) ($aboutTeam['eyebrow'] ?? 'The Heart Behind the Brand')) ?></span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1"><?= siteEscape((string) ($aboutTeam['title'] ?? '')) ?></h2>
        <div class="team-grid">
          <?php foreach ($aboutTeamMembers as $i => $member): ?>
          <div class="team-card reveal<?= $i > 0 ? ' reveal-delay-' . min($i, 3) : '' ?>">
            <img src="<?= siteEscape((string) ($member['image'] ?? '')) ?>" alt="<?= siteEscape((string) ($member['name'] ?? 'Team member')) ?>" class="team-image" loading="lazy">
            <h3 class="team-name"><?= siteEscape((string) ($member['name'] ?? '')) ?></h3>
            <p class="team-role"><?= siteEscape((string) ($member['role'] ?? '')) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="about-cta">
      <div class="container">
        <h2 class="reveal"><?= siteEscape((string) ($aboutCta['title'] ?? 'Ready to Write Your Story With Us?')) ?></h2>
        <p class="reveal reveal-delay-1"><?= siteEscape((string) ($aboutCta['text'] ?? '')) ?></p>
        <a href="<?= $ctaLink ?>" class="cta-btn reveal reveal-delay-2"><ion-icon name="chatbubble-outline"></ion-icon><?= siteEscape((string) ($aboutCta['label'] ?? 'Start Your Journey')) ?></a>
      </div>
    </section>
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

  <!-- Scroll to Top -->
  <button id="scrollToTop" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>

  <!-- Scripts -->
    <script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <script>
    'use strict';
    // Scroll Reveal Observer
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); revealObserver.unobserve(entry.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    // Stagger delays
    const delays = { 'reveal-delay-1': 100, 'reveal-delay-2': 200, 'reveal-delay-3': 300, 'reveal-delay-4': 400 };
    Object.keys(delays).forEach(cls => {
      document.querySelectorAll(`.${cls}`).forEach((el, i) => el.style.transitionDelay = `${delays[cls] + i * 50}ms`);
    });

    // Counter animation for stats
    function animateStats() {
      document.querySelectorAll('.stat-number').forEach(el => {
        const target = parseInt(el.innerText.replace(/[^0-9]/g, ''));
        if (!target) return;
        let current = 0;
        const increment = target / (2000 / 16);
        const update = () => {
          current += increment;
          if (current < target) { el.innerText = Math.floor(current).toLocaleString() + (el.innerText.includes('+') ? '+' : ''); requestAnimationFrame(update); }
          else el.innerText = el.innerText;
        };
        const obs = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { update(); obs.disconnect(); } }, { threshold: 0.5 });
        obs.observe(el);
      });
    }
    animateStats();

    // Parallax effect on hero (desktop only � transform on the hero caused mobile layout issues)
    function resetHeroParallax() {
      const hero = document.querySelector('.about-hero');
      if (!hero) return;
      if (window.innerWidth < 992) {
        hero.style.transform = '';
        hero.style.opacity = '';
      }
    }
    window.addEventListener('resize', resetHeroParallax, { passive: true });
    resetHeroParallax();

    window.addEventListener('scroll', () => {
      if (window.innerWidth < 992) return;
      const hero = document.querySelector('.about-hero');
      if (hero) {
        hero.style.transform = `translateY(${window.scrollY * 0.3}px)`;
        hero.style.opacity = String(1 - window.scrollY / 800);
      }
    }, { passive: true });
  </script>
  <?php require __DIR__ . '/assets/includes/site-end.php'; ?>
  <?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>