<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Biver Royalty Homes - Premium real estate services in Nigeria. Property sales, rentals, estate management, development, and consulting.">
  <meta name="keywords" content="real estate services Nigeria, property management, estate development, property sales, property rental, Biver Royalty">
  <meta name="author" content="Biver Royalty Homes Ltd">
  <title>Our Services | Biver Royalty Homes - Premium Real Estate Services</title>
  <link rel="shortcut icon" href="./assets/images/biver-logo.png" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/site-variables.css">
  <link rel="stylesheet" href="./assets/css/site-utilities.css">
  <link rel="stylesheet" href="./assets/css/services.css">
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
    <!-- Hero Section -->
    <section class="services-hero">
      <div class="hero-shape"></div>
      <div class="container">
        <div class="hero-badge"><div class="line"></div><span>Premium Services</span></div>
        <h1>Comprehensive <span class="gold-accent">Real Estate</span> Solutions</h1>
        <p class="hero-description">From property acquisition to estate management, we deliver excellence at every step. Your dream property journey starts here.</p>
        <div class="hero-stats">
          <div class="hero-stat"><div class="number">500+</div><div class="label">Properties Sold</div></div>
          <div class="hero-stat"><div class="number">1,200+</div><div class="label">Happy Clients</div></div>
          <div class="hero-stat"><div class="number">98%</div><div class="label">Satisfaction Rate</div></div>
        </div>
      </div>
    </section>

    <!-- Services Grid -->
    <section class="services-showcase">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span>What We Offer</span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1">Tailored Services for Every Need</h2>
        <div class="service-grid">
          <!-- Service 1 -->
          <div class="service-card-premium reveal reveal-left">
            <div class="service-icon-wrapper"><div class="service-icon"><ion-icon name="home-outline"></ion-icon></div></div>
            <div class="service-content">
              <h3 class="service-title">Property Sales</h3>
              <p class="service-description">Find your perfect home or investment property with our expert guidance and extensive network.</p>
              <ul class="service-features">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Verified Listings</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Market Valuation</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Legal Documentation</li>
              </ul>
              <a href="<?= pageHref('property') ?>" class="service-link">Explore Properties <ion-icon name="arrow-forward-outline"></ion-icon></a>
            </div>
          </div>
          <!-- Service 2 -->
          <div class="service-card-premium reveal reveal-up">
            <div class="service-icon-wrapper"><div class="service-icon"><ion-icon name="key-outline"></ion-icon></div></div>
            <div class="service-content">
              <h3 class="service-title">Property Rentals</h3>
              <p class="service-description">Flexible rental solutions for residential and commercial spaces with transparent terms.</p>
              <ul class="service-features">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Wide Selection</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Lease Negotiation</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Property Inspection</li>
              </ul>
              <a href="<?= pageHref('property') ?>" class="service-link">Find Rentals <ion-icon name="arrow-forward-outline"></ion-icon></a>
            </div>
          </div>
          <!-- Service 3 -->
          <div class="service-card-premium reveal reveal-right">
            <div class="service-icon-wrapper"><div class="service-icon"><ion-icon name="construct-outline"></ion-icon></div></div>
            <div class="service-content">
              <h3 class="service-title">Estate Management</h3>
              <p class="service-description">Professional management services to maintain and enhance your property's value.</p>
              <ul class="service-features">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Tenant Management</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Maintenance Services</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Rent Collection</li>
              </ul>
              <a href="<?= pageHref('contact') ?>" class="service-link">Learn More <ion-icon name="arrow-forward-outline"></ion-icon></a>
            </div>
          </div>
          <!-- Service 4 -->
          <div class="service-card-premium reveal reveal-left reveal-delay-1">
            <div class="service-icon-wrapper"><div class="service-icon"><ion-icon name="business-outline"></ion-icon></div></div>
            <div class="service-content">
              <h3 class="service-title">Property Development</h3>
              <p class="service-description">End-to-end development services from land acquisition to project completion.</p>
              <ul class="service-features">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Land Acquisition</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Project Management</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Quality Construction</li>
              </ul>
              <a href="<?= pageHref('contact') ?>" class="service-link">Start a Project <ion-icon name="arrow-forward-outline"></ion-icon></a>
            </div>
          </div>
          <!-- Service 5 -->
          <div class="service-card-premium reveal reveal-up reveal-delay-1">
            <div class="service-icon-wrapper"><div class="service-icon"><ion-icon name="document-text-outline"></ion-icon></div></div>
            <div class="service-content">
              <h3 class="service-title">Legal & Documentation</h3>
              <p class="service-description">Complete legal support for all real estate transactions with certified professionals.</p>
              <ul class="service-features">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Title Verification</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Contract Drafting</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Due Diligence</li>
              </ul>
              <a href="<?= pageHref('contact') ?>" class="service-link">Get Consultation <ion-icon name="arrow-forward-outline"></ion-icon></a>
            </div>
          </div>
          <!-- Service 6 -->
          <div class="service-card-premium reveal reveal-right reveal-delay-1">
            <div class="service-icon-wrapper"><div class="service-icon"><ion-icon name="map-outline"></ion-icon></div></div>
            <div class="service-content">
              <h3 class="service-title">Survey & Land Services</h3>
              <p class="service-description">Professional survey plans and land documentation for secure property ownership.</p>
              <ul class="service-features">
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Survey Plans</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Land Verification</li>
                <li><ion-icon name="checkmark-circle-outline"></ion-icon> Site Planning</li>
              </ul>
              <a href="<?= pageHref('contact') ?>" class="service-link">Request Survey <ion-icon name="arrow-forward-outline"></ion-icon></a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Process -->
    <section class="process-section">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span>Simple Process</span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1">How We Deliver Excellence</h2>
        <div class="process-steps">
          <div class="process-step reveal"><div class="step-number">01</div><h3 class="step-title">Consultation</h3><p class="step-text">Understanding your needs, goals, and budget to create a personalized strategy.</p></div>
          <div class="process-step reveal reveal-delay-1"><div class="step-number">02</div><h3 class="step-title">Property Search</h3><p class="step-text">Curated selection of properties matching your exact requirements.</p></div>
          <div class="process-step reveal reveal-delay-2"><div class="step-number">03</div><h3 class="step-title">Verification</h3><p class="step-text">Thorough due diligence, legal checks, and property inspection.</p></div>
          <div class="process-step reveal reveal-delay-3"><div class="step-number">04</div><h3 class="step-title">Closing</h3><p class="step-text">Seamless transaction with full documentation and support.</p></div>
        </div>
      </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose">
      <div class="container">
        <div class="section-eyebrow reveal"><div class="line"></div><span>Why Choose Us</span><div class="line"></div></div>
        <h2 class="section-title reveal reveal-delay-1">The Biver Royalty Advantage</h2>
        <div class="benefits-grid">
          <div class="benefit-card reveal"><div class="benefit-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div><h3 class="benefit-title">100% Integrity</h3><p class="benefit-text">Built on a foundation of transparency and honest dealings.</p></div>
          <div class="benefit-card reveal reveal-delay-1"><div class="benefit-icon"><ion-icon name="speedometer-outline"></ion-icon></div><h3 class="benefit-title">Fast & Efficient</h3><p class="benefit-text">Streamlined processes that save you time and stress.</p></div>
          <div class="benefit-card reveal reveal-delay-2"><div class="benefit-icon"><ion-icon name="people-outline"></ion-icon></div><h3 class="benefit-title">Expert Team</h3><p class="benefit-text">Seasoned professionals with deep local market knowledge.</p></div>
          <div class="benefit-card reveal reveal-delay-3"><div class="benefit-icon"><ion-icon name="headset-outline"></ion-icon></div><h3 class="benefit-title">24/7 Support</h3><p class="benefit-text">Always available to answer questions and provide guidance.</p></div>
        </div>
      </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
      <div class="container">
        <h2 class="reveal">Ready to Start Your Property Journey?</h2>
        <p class="reveal reveal-delay-1">Let's turn your real estate dreams into reality. Contact our expert team today.</p>
        <a href="<?= pageHref('contact') ?>" class="cta-button reveal reveal-delay-2"><ion-icon name="chatbubble-outline"></ion-icon>Schedule a Consultation</a>
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

  <button id="scrollToTop" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>

    <script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <script>
// Scroll to top
    // Scroll reveal
    const observer = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } }); }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    // Delay classes
    const delays = { 'reveal-delay-1': 100, 'reveal-delay-2': 200, 'reveal-delay-3': 300 };
    Object.keys(delays).forEach(cls => { document.querySelectorAll(`.${cls}`).forEach((el, i) => el.style.transitionDelay = `${delays[cls] + i * 50}ms`); });
  </script>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>