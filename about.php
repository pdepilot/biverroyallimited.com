<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Biver Royalty Homes - The untold story of integrity, vision, and architectural excellence in Nigerian real estate.">
  <meta name="keywords" content="about Biver Royalty, real estate story, Nigerian real estate, Owerri property, luxury homes">
  <meta name="author" content="Biver Royalty Homes Ltd">
  <title>About Us | The Biver Royalty Story</title>
  <link rel="shortcut icon" href="./assets/images/biver-logo.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/site-variables.css">
  <link rel="stylesheet" href="./assets/css/site-utilities.css">
  <link rel="stylesheet" href="./assets/css/about.css">
  <?php require_once __DIR__ . '/includes/site_paths.php'; ?>
  <link rel="stylesheet" href="./assets/css/site-header.css">
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
        <a href="tel:+2349033137432" class="topbar-link" aria-label="Call us">
          <ion-icon name="call-outline"></ion-icon>
          <span>+234 903 313 7432</span>
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
       SEARCH MODAL — Feature #2
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
                <li role="none"><a href="<?= pageHref('contact') ?>" class="navbar-link" data-nav-link role="menuitem">Contact</a></li>
              </ul>
            </div>

            <div class="navbar-footer">
              <p class="navbar-footer-title">Get in Touch</p>
              <a href="tel:+2349033137432" class="navbar-footer-link">
                <ion-icon name="call-outline"></ion-icon>
                <span>+234 903 313 7432</span>
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
        <div class="hero-subtitle">EST. 2015</div>
        <h1 class="hero-title">Architects of <span class="gold-accent">Dreams</span>,<br>Builders of Trust</h1>
        <p class="hero-description">Biver Royalty Homes wasn't built on transactions â€” it was built on relationships. In a world of empty promises, we chose integrity as our foundation.</p>
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-number">1,200+</div><div class="stat-label">Families Served</div></div>
          <div class="stat-card"><div class="stat-number">500+</div><div class="stat-label">Properties Sold</div></div>
          <div class="stat-card"><div class="stat-number">100%</div><div class="stat-label">Client Trust</div></div>
          <div class="stat-card"><div class="stat-number">10+</div><div class="stat-label">Years of Excellence</div></div>
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
            <div class="narrative-badge"><div class="line"></div><span>The Untold Story</span></div>
            <h2 class="narrative-title">From a Bold Vision to Nigeria's Most Trusted Real Estate Name</h2>
            <p class="narrative-text">In 2015, Oliva Guiffo saw a gap in Nigeria's real estate market â€” not a gap in properties, but a gap in integrity. While others prioritized commissions over clients, he envisioned something radical: a real estate company where transparency wasn't a buzzword, but a sacred promise.</p>
            <p class="narrative-text">What started as a one-man mission in Owerri has blossomed into a movement. Today, Biver Royalty Homes stands as a testament to what happens when you put people before profit, relationships before revenue, and dreams before documents.</p>
            <div class="narrative-quote">We don't sell houses. We hand over the keys to futures. Every client who walks through our doors becomes family â€” and family deserves nothing less than excellence.</div>
            <div class="signature">Mr” Oliva Guiffo, Founder</div>
          </div>
          <div class="visual-story reveal reveal-right">
            <img src="./assets/images/engineer1.png" alt="Founder at work" class="main-image" loading="lazy">
            <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=500&auto=format&fit=crop&q=80" alt="Early days" class="floating-image" loading="lazy">
            <div class="image-caption">The Journey Begins in Owerri, 2015</div>
          </div>
        </div>
      </div>
    </section>

    <!-- =============================================
         PHILOSOPHY - Three Pillars
    ============================================= -->
    <section class="philosophy">
      <div class="container">
        <div class="philosophy-grid">
          <div class="pillar-card reveal"><div class="pillar-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div><h3 class="pillar-title">Radical Integrity</h3><p class="pillar-text">We speak truth even when it costs us a sale. No hidden fees, no misleading listings, no fine print surprises. Just honest guidance.</p></div>
          <div class="pillar-card reveal reveal-delay-1"><div class="pillar-icon"><ion-icon name="heart-outline"></ion-icon></div><h3 class="pillar-title">Obsessive Care</h3><p class="pillar-text">Your dream becomes our mission. We lose sleep so you can rest easy, handling every detail with white-glove precision.</p></div>
          <div class="pillar-card reveal reveal-delay-2"><div class="pillar-icon"><ion-icon name="star-outline"></ion-icon></div><h3 class="pillar-title">Unwavering Excellence</h3><p class="pillar-text">From property sourcing to legal documentation, we obsess over quality. Mediocrity has no place in our vocabulary.</p></div>
        </div>
      </div>
    </section>

    <!-- =============================================
         JOURNEY - Timeline
    ============================================= -->
    <section class="journey">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span>Our Journey</span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1">The Road to Redefining Real Estate</h2>
        <div class="timeline">
          <div class="timeline-item reveal reveal-left"><div class="timeline-year">2015</div><div class="timeline-content"><div class="timeline-dot"></div><h4 class="timeline-title">The Seed is Planted</h4><p class="timeline-text">Biver Royalty Homes opens its doors in a small office on Wetheral Road, Owerri. First property sold within 3 months.</p></div></div>
          <div class="timeline-item reveal reveal-right"><div class="timeline-year">2017</div><div class="timeline-content"><div class="timeline-dot"></div><h4 class="timeline-title">Expansion & Recognition</h4><p class="timeline-text">Named "Most Trusted Real Estate Agency" in Imo State. Team grows to 12 dedicated agents.</p></div></div>
          <div class="timeline-item reveal reveal-left"><div class="timeline-year">2019</div><div class="timeline-content"><div class="timeline-dot"></div><h4 class="timeline-title">Digital Transformation</h4><p class="timeline-text">Launch of comprehensive online platform, making property search accessible to thousands across Nigeria.</p></div></div>
          <div class="timeline-item reveal reveal-right"><div class="timeline-year">2022</div><div class="timeline-content"><div class="timeline-dot"></div><h4 class="timeline-title">1,000+ Families</h4><p class="timeline-text">Milestone achievement: 1,000 happy families find their dream homes through Biver Royalty.</p></div></div>
          <div class="timeline-item reveal reveal-left"><div class="timeline-year">2024</div><div class="timeline-content"><div class="timeline-dot"></div><h4 class="timeline-title">Industry Leadership</h4><p class="timeline-text">Recognized as a leading force in Nigerian real estate, setting new standards for integrity and client care.</p></div></div>
        </div>
      </div>
    </section>

    <!-- =============================================
         VALUES - Core Values Deep Dive
    ============================================= -->
    <section class="values">
      <div class="container">
        <div class="values-grid">
          <div class="values-list">
            <div class="value-item reveal reveal-left"><div class="value-icon"><ion-icon name="people-outline"></ion-icon></div><div class="value-text"><h4>People First, Always</h4><p>Behind every transaction is a family, a dream, a future. We never forget that.</p></div></div>
            <div class="value-item reveal reveal-left reveal-delay-1"><div class="value-icon"><ion-icon name="eye-outline"></ion-icon></div><div class="value-text"><h4>Radical Transparency</h4><p>Every document shared, every fee explained, every process visible. No secrets. Ever.</p></div></div>
            <div class="value-item reveal reveal-left reveal-delay-2"><div class="value-icon"><ion-icon name="infinite-outline"></ion-icon></div><div class="value-text"><h4>Lifelong Relationships</h4><p>We don't close doors â€” we open them. Many clients return for their second, third, and fourth homes.</p></div></div>
            <div class="value-item reveal reveal-left reveal-delay-3"><div class="value-icon"><ion-icon name="cube-outline"></ion-icon></div><div class="value-text"><h4>Community Builders</h4><p>We're not just selling properties; we're shaping neighborhoods, strengthening communities.</p></div></div>
          </div>
          <div class="values-image reveal reveal-right"><img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=600&auto=format&fit=crop&q=80" alt="Building community" class="values-main-img" loading="lazy"></div>
        </div>
      </div>
    </section>

    <!-- =============================================
         TEAM PREVIEW
    ============================================= -->
    <section class="team-preview">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span>The Heart Behind the Brand</span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1">Meet the Dream Weavers</h2>
        <div class="team-grid">
          <div class="team-card reveal"><img src="./assets/images/engineer1.png" alt="Founder" class="team-image" loading="lazy"><h3 class="team-name">Oliva Guiffo</h3><p class="team-role">Founder & CEO</p></div>
          <div class="team-card reveal reveal-delay-1"><img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80" alt="Operations Director" class="team-image" loading="lazy"><h3 class="team-name">Amara Okafor</h3><p class="team-role">Operations Director</p></div>
          <div class="team-card reveal reveal-delay-2"><img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400&auto=format&fit=crop&q=80" alt="Head of Sales" class="team-image" loading="lazy"><h3 class="team-name">Emeka Obi</h3><p class="team-role">Head of Sales</p></div>
          <div class="team-card reveal reveal-delay-3"><img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80" alt="Client Relations" class="team-image" loading="lazy"><h3 class="team-name">Chioma Eze</h3><p class="team-role">Client Relations</p></div>
        </div>
      </div>
    </section>

    <!-- =============================================
         CTA SECTION
    ============================================= -->
    <section class="about-cta">
      <div class="container">
        <h2 class="reveal">Ready to Write Your Story With Us?</h2>
        <p class="reveal reveal-delay-1">Every great home begins with a conversation. Let's start yours.</p>
        <a href="<?= pageHref('contact') ?>" class="cta-btn reveal reveal-delay-2"><ion-icon name="chatbubble-outline"></ion-icon>Start Your Journey</a>
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
              <a href="tel:+2349033137432" class="contact-link">
                <ion-icon name="call-outline"></ion-icon>
                <span>+234 903 313 7432</span>
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
            <li><a href="https://blog.biverroyaltyhomesltd.com/" class="footer-link" target="_blank" rel="noopener noreferrer">Blog</a></li>
            <li><a href="<?= pageHref('property') ?>" class="footer-link">All Properties</a></li>
            <li><a href="#" class="footer-link">Locations Map</a></li>
            <li><a href="#" class="footer-link">FAQ</a></li>
            <li><a href="<?= pageHref('contact') ?>" class="footer-link">Contact Us</a></li>
          </ul>
          <ul class="footer-list">
            <li><p class="footer-list-title">Services</p></li>
            <li><a href="<?= pageHref('addCart') ?>" class="footer-link">Order Tracking</a></li>
            <li><a href="<?= pageHref('favorites') ?>" class="footer-link">Wish List</a></li>
            <li><a href="<?= pageHref('login') ?>" class="footer-link">Login</a></li>
            <li><a href="<?= pageHref('userDashboard') ?>" class="footer-link">My Account</a></li>
            <li><a href="#" class="footer-link">Terms &amp; Conditions</a></li>
            <li><a href="<?= pageHref('property') ?>" class="footer-link">Promotions</a></li>
          </ul>
          <ul class="footer-list">
            <li><p class="footer-list-title">Customer Care</p></li>
            <li><a href="<?= pageHref('login') ?>" class="footer-link">Login</a></li>
            <li><a href="<?= pageHref('userDashboard') ?>" class="footer-link">My Account</a></li>
            <li><a href="<?= pageHref('favorites') ?>" class="footer-link">Wish List</a></li>
            <li><a href="<?= pageHref('addCart') ?>" class="footer-link">Order Tracking</a></li>
            <li><a href="#" class="footer-link">FAQ</a></li>
            <li><a href="<?= pageHref('contact') ?>" class="footer-link">Contact Us</a></li>
          </ul>
        </div>
      </div>
    </div>
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

    // Parallax effect on hero
    window.addEventListener('scroll', () => {
      const hero = document.querySelector('.about-hero');
      if (hero) { hero.style.transform = `translateY(${window.scrollY * 0.3}px)`; hero.style.opacity = 1 - window.scrollY / 800; }
    });
  </script>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>