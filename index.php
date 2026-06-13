<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Biver Royalty Homes - Your trusted real estate partner in Nigeria. Find your dream home with our extensive property listings in Owerri, Imo State.">
  <meta name="keywords" content="real estate, Nigeria, Owerri, property, homes, rent, buy, Biver Royalty">
  <meta name="author" content="Biver Royalty Homes Ltd">
  <meta name="robots" content="index, follow">
  <meta property="og:title" content="Biver Royalty Homes - Find Your Dream House">
  <meta property="og:description" content="Leading real estate marketplace in Nigeria. Buy, rent, or sell properties with integrity.">
  <meta property="og:image" content="./assets/images/biver-logo.png">
  <meta property="og:url" content="https://biverroyaltyhomesltd.com">
  <meta property="og:type" content="website">
  <title>Biver Royalty Homes - Find Your Dream House | Real Estate Nigeria</title>
  <link rel="shortcut icon" href="./assets/images/biver-logo.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/site-variables.css">
  <link rel="stylesheet" href="./assets/css/site-utilities.css">
  <link rel="stylesheet" href="./assets/css/index.css">
  <?php require __DIR__ . '/includes/site_bootstrap.php'; ?>
  <link rel="stylesheet" href="./assets/css/site-header.css">
  <link rel="stylesheet" href="./assets/css/site-banners.css">
</head>

<body>

  <!-- =============================================
       PRELOADER
  ============================================= -->
  <div id="preloader" role="dialog" aria-modal="true" aria-label="Loading Biver Royalty Homes" aria-busy="true">
    <div class="preloader-grid" aria-hidden="true"></div>
    <div class="preloader-glow" aria-hidden="true"></div>
    <div class="preloader-particles" id="preloaderParticles" aria-hidden="true"></div>

    <div class="preloader-logo-wrap">
      <div class="preloader-crest" aria-hidden="true">
        <svg class="crest-svg" viewBox="0 0 90 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path class="crest-corner" d="M 18 18 L 18 34 M 18 18 L 34 18"/>
          <path class="crest-corner" d="M 72 18 L 56 18 M 72 18 L 72 34"/>
          <path class="crest-corner" d="M 72 72 L 72 56 M 72 72 L 56 72"/>
          <path class="crest-corner" d="M 18 72 L 18 56 M 18 72 L 34 72"/>
          <circle class="crest-ring-outer" cx="45" cy="45" r="42"/>
          <circle class="crest-ring-inner" cx="45" cy="45" r="32"/>
          <polygon class="crest-diamond" points="45,22 58,45 45,68 32,45"/>
          <text class="crest-letter" x="45" y="52" text-anchor="middle">B</text>
        </svg>
      </div>
      <div class="preloader-brand">
        <p class="preloader-brand-name">Biver <span class="gold">Royalty</span> Homes</p>
        <p class="preloader-brand-sub">Luxury Real Estate · Nigeria</p>
      </div>
      <div class="preloader-line" aria-hidden="true"></div>
    </div>

    <div class="preloader-progress-wrap">
      <div class="preloader-progress-label">
        <span>Loading Experience</span>
        <span id="preloaderPercent">0%</span>
      </div>
      <div class="preloader-progress-track">
        <div class="preloader-progress-bar" id="preloaderBar">
          <div class="preloader-scan" aria-hidden="true"></div>
        </div>
      </div>
    </div>

    <div class="preloader-curtain" aria-hidden="true"></div>
  </div>

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
    <article>

      <?php require __DIR__ . '/assets/includes/promo-banner.php'; ?>

      <!-- =============================================
           HERO
      ============================================= -->
      <section class="hero" id="home" aria-label="Hero section">
        <div class="hero-bg-slideshow" aria-hidden="true">
          <!-- Slide 1: Eager-loaded — visible immediately -->
          <div class="bg-slide active bg-slide--1"></div>
          <!-- Slides 2-5: Lazy-loaded via JS after slide 1 is shown (Feature #4) -->
          <div class="bg-slide" data-bg="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1920&auto=format&fit=crop&q=80"></div>
          <div class="bg-slide" data-bg="https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=1920&auto=format&fit=crop&q=80"></div>
          <div class="bg-slide" data-bg="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1920&auto=format&fit=crop&q=80"></div>
          <div class="bg-slide" data-bg="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1920&auto=format&fit=crop&q=80"></div>
          <div class="bg-overlay"></div>
        </div>

        <div class="container">
          <div class="hero-content" id="heroContent">
            <p class="hero-eyebrow" id="heroEyebrow">
              <span class="line"></span>
              <span id="heroEyebrowText">Premium Real Estate Agency</span>
            </p>
            <h1 class="hero-title" id="heroTitle">
              <span id="heroTitleText">Biver <span class="accent">Royalty</span> Homes</span>
            </h1>
            <p class="hero-tagline" id="heroTagline">Where your dreams come true — find premium properties in Owerri, Imo State, built on a foundation of integrity and excellence.</p>
            <div class="hero-actions" id="heroActions">
              <a href="<?= pageHref('property') ?>" class="hero-btn-primary">
                <ion-icon name="home-outline"></ion-icon> Explore Properties
              </a>
              <a href="<?= pageHref('contact') ?>" class="hero-btn-secondary">
                <ion-icon name="call-outline"></ion-icon> Contact Us
              </a>
            </div>
          </div>
        </div>

        <!-- Stats Bar -->
        <div class="hero-stats" aria-label="Company statistics">
          <div class="container">
            <div class="stat-item">
              <div class="stat-icon"><ion-icon name="home-outline"></ion-icon></div>
              <div class="stat-info">
                <div class="num">500+</div>
                <div class="label">Properties Listed</div>
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-icon"><ion-icon name="people-outline"></ion-icon></div>
              <div class="stat-info">
                <div class="num">1,200+</div>
                <div class="label">Happy Clients</div>
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-icon"><ion-icon name="star-outline"></ion-icon></div>
              <div class="stat-info">
                <div class="num">5★</div>
                <div class="label">Service Rating</div>
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-icon"><ion-icon name="ribbon-outline"></ion-icon></div>
              <div class="stat-info">
                <div class="num">10+</div>
                <div class="label">Years Experience</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Slide dots -->
        <div class="slide-dots" role="tablist" aria-label="Slideshow navigation">
          <span class="dot active" data-slide="0" role="tab" aria-selected="true" aria-label="Slide 1" tabindex="0"></span>
          <span class="dot" data-slide="1" role="tab" aria-selected="false" aria-label="Slide 2" tabindex="0"></span>
          <span class="dot" data-slide="2" role="tab" aria-selected="false" aria-label="Slide 3" tabindex="0"></span>
          <span class="dot" data-slide="3" role="tab" aria-selected="false" aria-label="Slide 4" tabindex="0"></span>
          <span class="dot" data-slide="4" role="tab" aria-selected="false" aria-label="Slide 5" tabindex="0"></span>
        </div>
      </section>

      <!-- =============================================
           ABOUT
      ============================================= -->
      <section class="about" id="about" aria-label="About us">
        <div class="container">
          <figure class="about-banner reveal reveal-left">
            <img src="./assets/images/image1.webp" alt="Modern house interior" loading="lazy">
            <img src="./assets/images/image2.webp" alt="Luxury house interior" class="abs-img" loading="lazy">
          </figure>
          <div class="about-content">
            <p class="section-subtitle reveal">About Us</p>
            <h2 class="h2 section-title reveal reveal-delay-1">The Leading Real Estate Rental Marketplace.</h2>
            <p class="about-text reveal reveal-delay-2">
              We are a real estate company that is built on Integrity. We ensure that our clients are able to bring their dream homes to reality all on the budget mapped. We ensure our services are high class and meet our 5 star standard.
            </p>
            <ul class="about-list reveal reveal-delay-2">
              <li class="about-item">
                <div class="about-item-icon"><ion-icon name="home-outline"></ion-icon></div>
                <p class="about-item-text">Lease and Rent of Properties</p>
              </li>
              <li class="about-item">
                <div class="about-item-icon"><ion-icon name="leaf-outline"></ion-icon></div>
                <p class="about-item-text">Estate Management &amp; Property Development</p>
              </li>
              <li class="about-item">
                <div class="about-item-icon"><ion-icon name="wine-outline"></ion-icon></div>
                <p class="about-item-text">Survey Plan</p>
              </li>
              <li class="about-item">
                <div class="about-item-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
                <p class="about-item-text">Civil Construction</p>
              </li>
            </ul>
            <p class="callout reveal reveal-delay-3">
              A Place Where Your Dreams Come True. We are a real estate company built on integrity. Our mission is to help you bring your dream home to life — within your vision, within your budget.
            </p>
            <a href="<?= pageHref('about') ?>" class="btn reveal reveal-delay-4">Meet Our Team</a>
          </div>
        </div>
      </section>

      <!-- =============================================
           SERVICE
      ============================================= -->
      <section class="service" id="service" aria-label="Our services">
        <div class="container">
          <p class="section-subtitle reveal">Our Services</p>
          <h2 class="h2 section-title reveal reveal-delay-1">Our Main Focus</h2>
          <ul class="service-list">
            <li class="reveal reveal-delay-1">
              <div class="service-card">
                <div class="card-icon"><ion-icon name="home-outline"></ion-icon></div>
                <h3 class="h3 card-title"><a href="<?= pageHref('property') ?>">Buy a Home</a></h3>
                <p class="card-text">Over 1 million+ homes for sale available on the website, we can match you with a house you will want to call home.</p>
                <a href="<?= pageHref('property') ?>" class="card-link">
                  <span>Find A Home</span>
                  <ion-icon name="arrow-forward-outline"></ion-icon>
                </a>
              </div>
            </li>
            <li class="reveal reveal-delay-2">
              <div class="service-card">
                <div class="card-icon"><ion-icon name="key-outline"></ion-icon></div>
                <h3 class="h3 card-title"><a href="<?= pageHref('property') ?>">Rent a Home</a></h3>
                <p class="card-text">Over 1 million+ homes for rent available on the website, we can match you with a house you will want to call home.</p>
                <a href="<?= pageHref('property') ?>" class="card-link">
                  <span>Find A Home</span>
                  <ion-icon name="arrow-forward-outline"></ion-icon>
                </a>
              </div>
            </li>
            <li class="reveal reveal-delay-3">
              <div class="service-card">
                <div class="card-icon"><ion-icon name="construct-outline"></ion-icon></div>
                <h3 class="h3 card-title"><a href="<?= pageHref('contact') ?>">Sell a Home</a></h3>
                <p class="card-text">List your property with us and reach thousands of verified buyers. We ensure a smooth, profitable transaction.</p>
                <a href="<?= pageHref('contact') ?>" class="card-link">
                  <span>List Property</span>
                  <ion-icon name="arrow-forward-outline"></ion-icon>
                </a>
              </div>
            </li>
          </ul>
        </div>
      </section>

      <!-- =============================================
           FEATURED PROPERTIES
      ============================================= -->
      <section class="property" id="property" aria-label="Featured properties">
        <div class="container">
          <p class="section-subtitle reveal">Properties</p>
          <h2 class="h2 section-title reveal reveal-delay-1">Featured Listings</h2>
          <p class="scroll-row-hint reveal reveal-delay-2" aria-hidden="true">
            <ion-icon name="arrow-forward-outline"></ion-icon> Scroll horizontally to see more listings
          </p>

          <div id="propertiesSkeleton" class="properties-skeleton">
            <div class="skeleton-grid">
              <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-long"></div></div></div>
              <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-long"></div></div></div>
              <div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-content"><div class="skeleton-line skeleton-line-short"></div><div class="skeleton-line"></div><div class="skeleton-line skeleton-line-long"></div></div></div>
            </div>
          </div>

          <div id="propertiesError" class="properties-error u-hidden" role="alert">
            <ion-icon name="alert-circle-outline"></ion-icon>
            <h3>Failed to load properties</h3>
            <p>Unable to fetch featured properties. Please check your connection and try again.</p>
            <button onclick="loadFeaturedProperties()" class="btn u-mt-20">Try Again</button>
          </div>
          <div id="propertiesEmpty" class="properties-empty u-hidden"></div>
          <ul id="propertiesList" class="property-list has-scrollbar u-hidden"></ul>
        </div>
      </section>

      <!-- =============================================
           WHY CHOOSE US
      ============================================= -->
      <section class="why-us" aria-label="Why choose us">
        <div class="container">
          <div class="why-us-grid">
            <div class="why-us-visual reveal reveal-left">
              <img src="./assets/images/engineer.png" alt="Biver Royalty Homes professional engineer" class="why-us-img-main" loading="lazy">
              <img src="https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=500&auto=format&fit=crop&q=80" alt="Modern kitchen" class="why-us-img-accent" loading="lazy">
              <div class="why-us-badge">
                <div class="num">10+</div>
                <div class="txt">Years of Excellence</div>
              </div>
            </div>
            <div class="why-us-content">
              <p class="section-subtitle reveal section-subtitle--left">Why Choose Us</p>
              <h2 class="h2 section-title reveal reveal-delay-1 section-title--left">We Make Buying a Home<br><em>Simple &amp; Secure</em></h2>
              <p class="why-us-text reveal reveal-delay-2">With over a decade of experience in the Nigerian real estate market, Biver Royalty Homes stands apart through unwavering integrity, personalized service, and deep market knowledge that helps you make confident property decisions.</p>
              <div class="why-grid reveal reveal-delay-3">
                <div class="why-item">
                  <div class="why-item-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
                  <div>
                    <p class="why-item-title">Verified Listings</p>
                    <p class="why-item-text">Every property is thoroughly verified before listing.</p>
                  </div>
                </div>
                <div class="why-item">
                  <div class="why-item-icon"><ion-icon name="ribbon-outline"></ion-icon></div>
                  <div>
                    <p class="why-item-title">Award-Winning</p>
                    <p class="why-item-text">Recognized excellence in Nigerian real estate.</p>
                  </div>
                </div>
                <div class="why-item">
                  <div class="why-item-icon"><ion-icon name="people-outline"></ion-icon></div>
                  <div>
                    <p class="why-item-title">Expert Agents</p>
                    <p class="why-item-text">Seasoned professionals ready to guide you.</p>
                  </div>
                </div>
                <div class="why-item">
                  <div class="why-item-icon"><ion-icon name="headset-outline"></ion-icon></div>
                  <div>
                    <p class="why-item-title">24/7 Support</p>
                    <p class="why-item-text">Always available when you need us most.</p>
                  </div>
                </div>
              </div>
              <a href="<?= pageHref('about') ?>" class="btn reveal reveal-delay-4">Learn More About Us</a>
            </div>
          </div>
        </div>
      </section>

      <!-- =============================================
           PROCESS
      ============================================= -->
      <section class="process" aria-label="How it works">
        <div class="container">
          <p class="section-subtitle reveal">How It Works</p>
          <h2 class="h2 section-title reveal reveal-delay-1">Find Your Dream Home in 4 Simple Steps</h2>
          <div class="process-list">
            <div class="process-item reveal reveal-delay-1">
              <div class="process-icon"><ion-icon name="search-outline"></ion-icon></div>
              <h3 class="process-title">Search Property</h3>
              <p class="process-text">Browse our extensive listings of verified properties across Owerri and Imo State.</p>
            </div>
            <div class="process-item reveal reveal-delay-2">
              <div class="process-icon"><ion-icon name="calendar-outline"></ion-icon></div>
              <h3 class="process-title">Book a Visit</h3>
              <p class="process-text">Schedule a site inspection at your convenience with our professional agents.</p>
            </div>
            <div class="process-item reveal reveal-delay-3">
              <div class="process-icon"><ion-icon name="document-text-outline"></ion-icon></div>
              <h3 class="process-title">Legal Process</h3>
              <p class="process-text">We handle all documentation and legal verifications to ensure a smooth transaction.</p>
            </div>
            <div class="process-item reveal reveal-delay-4">
              <div class="process-icon"><ion-icon name="key-outline"></ion-icon></div>
              <h3 class="process-title">Get the Keys</h3>
              <p class="process-text">Complete the transaction and move into your dream home with full peace of mind.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- =============================================
           FEATURES / AMENITIES
      ============================================= -->
      <section class="features" aria-label="Building amenities">
        <div class="container">
          <p class="section-subtitle reveal">Our Amenities</p>
          <h2 class="h2 section-title reveal reveal-delay-1">Building Amenities</h2>
          <ul class="features-list">
            <li class="reveal reveal-delay-1">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="car-sport-outline"></ion-icon></div>
                <h3 class="card-title">Parking Space</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-2">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="water-outline"></ion-icon></div>
                <h3 class="card-title">Swimming Pool</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-3">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
                <h3 class="card-title">Private Security</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-4">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="fitness-outline"></ion-icon></div>
                <h3 class="card-title">Medical Center</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-1">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="library-outline"></ion-icon></div>
                <h3 class="card-title">Library Area</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-2">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="bed-outline"></ion-icon></div>
                <h3 class="card-title">King Size Beds</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-3">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="home-outline"></ion-icon></div>
                <h3 class="card-title">Smart Homes</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
            <li class="reveal reveal-delay-4">
              <a href="#" class="features-card">
                <div class="card-icon"><ion-icon name="football-outline"></ion-icon></div>
                <h3 class="card-title">Kid's Playland</h3>
                <div class="card-btn"><ion-icon name="arrow-forward-outline"></ion-icon></div>
              </a>
            </li>
          </ul>
        </div>
      </section>

      <!-- =============================================
           TESTIMONIALS
      ============================================= -->
      <section class="testimonial" id="testimonial" aria-label="Client testimonials">
        <div class="container">
          <p class="section-subtitle reveal">Testimonials</p>
          <h2 class="h2 section-title reveal reveal-delay-1">What Our Clients Say</h2>

          <div id="testimonialsSkeleton" class="testimonials-skeleton">
            <div class="skeleton-grid">
              <div class="skeleton-testimonial"></div>
              <div class="skeleton-testimonial"></div>
              <div class="skeleton-testimonial"></div>
            </div>
          </div>
          <div id="testimonialsError" class="testimonials-error u-hidden" role="alert">
            <ion-icon name="alert-circle-outline"></ion-icon>
            <h3>Failed to load testimonials</h3>
            <p>Unable to fetch client testimonials. Please try again.</p>
            <button onclick="loadTestimonials()" class="btn u-mt-20">Try Again</button>
          </div>
          <div id="testimonialsEmpty" class="testimonials-empty u-hidden">
            <ion-icon name="chatbubbles-outline"></ion-icon>
            <h3>No Testimonials Yet</h3>
            <p>Check back soon for client experiences!</p>
          </div>

          <div id="testimonialsSlider" class="testimonial-slider u-hidden">
            <div class="testimonial-track">
              <ul id="testimonialsList" class="testimonial-list"></ul>
            </div>
            <div class="slider-nav">
              <button class="slider-prev" id="testimonialPrev" disabled aria-label="Previous">
                <ion-icon name="chevron-back-outline"></ion-icon>
              </button>
              <button class="slider-next" id="testimonialNext" aria-label="Next">
                <ion-icon name="chevron-forward-outline"></ion-icon>
              </button>
            </div>
            <div class="slider-dots" id="testimonialDots" role="tablist"></div>
          </div>
        </div>
      </section>

      <!-- =============================================
           AREAS WE SERVE
      ============================================= -->
      <section class="areas" id="areas" aria-label="Areas we serve">
        <div class="container">
          <p class="section-subtitle reveal">Local Expertise</p>
          <h2 class="h2 section-title reveal reveal-delay-1">Areas We Serve in Owerri &amp; Imo State</h2>
          <p class="areas-intro reveal reveal-delay-2" id="areasIntro">From family-friendly estates to executive layouts, we help buyers and renters find verified homes in Owerri's most sought-after neighborhoods.</p>
          <p class="scroll-row-hint reveal reveal-delay-2" aria-hidden="true">
            <ion-icon name="arrow-forward-outline"></ion-icon> Scroll horizontally to explore more areas
          </p>

          <ul class="areas-grid has-scrollbar" id="areasList" aria-label="Owerri neighborhoods we serve">
            <li class="reveal"><article class="area-card"><div class="area-card-body"><p class="area-card-text">Loading areas...</p></div></article></li>
          </ul>

          <div class="areas-cta reveal" id="areasCtaWrap">
            <p id="areasCtaText">Don't see your preferred area? Our agents cover all of Imo State.</p>
            <a href="<?= pageHref('contact') ?>" class="btn" id="areasCtaBtn">Request a Custom Search</a>
          </div>
        </div>
      </section>

      <!-- =============================================
           CTA
      ============================================= -->
      <section class="cta" aria-label="Call to action">
        <div class="cta-card">
          <div class="cta-inner">
            <div class="cta-content reveal reveal-left">
              <p class="eyebrow">Ready to Start?</p>
              <h2 class="card-title">Looking for Your Dream Home?</h2>
              <p class="card-text">We can help you realize your dream of a new home. Our expert team is ready to guide you every step of the way.</p>
            </div>
            <div class="cta-actions reveal reveal-right">
              <a href="<?= pageHref('property') ?>" class="cta-btn-primary">
                <ion-icon name="home-outline"></ion-icon> Explore Properties
              </a>
              <a href="<?= pageHref('contact') ?>" class="cta-btn-outline">
                <ion-icon name="call-outline"></ion-icon> Talk to an Agent
              </a>
            </div>
          </div>
        </div>
      </section>

    </article>
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
  <button id="scrollToTop" aria-label="Scroll to top">
    <ion-icon name="chevron-up-outline"></ion-icon>
  </button>

  <!-- Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "RealEstateAgent",
    "name": "Biver Royalty Homes Ltd",
    "description": "Leading real estate marketplace in Nigeria specializing in property sales, rentals, and estate management.",
    "url": "https://biverroyaltyhomesltd.com",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "No. 31 Wetheral Road, Angelina Plaza",
      "addressLocality": "Owerri",
      "addressRegion": "Imo State",
      "addressCountry": "NG"
    },
    "telephone": "+2349033137432",
    "email": "biverroyaltyhomes01@gmail.com"
  }
  </script>

  <!-- Ionicons -->
    <script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <script>
    'use strict';

    /* =============================================
       PRELOADER LOGIC
       Runs before page interaction is enabled.
       Uses requestAnimationFrame for smooth, GPU-
       efficient progress animation.
       Feature #5: Respects prefers-reduced-motion —
       if set, skips cinematic delay and exits fast.
    ============================================= */
    (function initPreloader() {
      const preloader  = document.getElementById('preloader');
      const bar        = document.getElementById('preloaderBar');
      const pct        = document.getElementById('preloaderPercent');
      const particles  = document.getElementById('preloaderParticles');

      if (!preloader) return;

      function notifySiteReady() {
        window.dispatchEvent(new CustomEvent('biver:preloader-done'));
      }

      function forceDismissPreloader() {
        preloader.classList.add('exiting', 'hidden');
        document.body.style.overflow = '';
        notifySiteReady();
      }

      // Always dismiss within 4 seconds so the site never stays blocked
      setTimeout(forceDismissPreloader, 4000);

      // Feature #5: If user prefers reduced motion, skip entirely
      const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReduced) {
        // Show briefly then fade out — no animations, no delay
        document.body.style.overflow = 'hidden';
        window.addEventListener('load', () => {
          setTimeout(() => {
            if (preloader) preloader.classList.add('hidden');
            document.body.style.overflow = '';
            notifySiteReady();
          }, 300);
        });
        // Absolute fallback
        setTimeout(() => {
          if (preloader) preloader.classList.add('hidden');
          document.body.style.overflow = '';
          notifySiteReady();
        }, 1500);
        return; // Exit — no RAF loop, no particles
      }

      if (!bar || !pct || !particles) {
        window.addEventListener('load', forceDismissPreloader, { once: true });
        return;
      }

      // Generate ambient gold particle dust
      const PARTICLE_COUNT = 18;
      for (let i = 0; i < PARTICLE_COUNT; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = 2 + Math.random() * 3;
        p.style.cssText = [
          `width:${size}px`,
          `height:${size}px`,
          `left:${10 + Math.random() * 80}%`,
          `bottom:${Math.random() * 40}%`,
          `--dur:${3 + Math.random() * 4}s`,
          `--delay:${Math.random() * 3}s`,
          `--op:${0.3 + Math.random() * 0.5}`
        ].join(';');
        particles.appendChild(p);
      }

      // Animate progress: fast at first, slows near 90%, then bursts to 100
      let progress = 0;
      let raf;
      const TICK_RATE = 16; // ~60fps
      let lastTime = 0;

      function animateBar(timestamp) {
        if (timestamp - lastTime < TICK_RATE) {
          raf = requestAnimationFrame(animateBar);
          return;
        }
        lastTime = timestamp;

        const speed = progress < 85 ? 0.6 : 0.15;
        progress = Math.min(progress + speed, 100);

        const display = Math.floor(progress);
        bar.style.width = display + '%';
        pct.textContent = display + '%';

        if (progress < 100) {
          raf = requestAnimationFrame(animateBar);
        }
      }

      // Start progress animation
      raf = requestAnimationFrame(animateBar);

      // Dismiss preloader after page load + min display time
      const MIN_DISPLAY = 2800; // ms — enough to feel cinematic, not drag
      const startTime = Date.now();

      function dismissPreloader() {
        cancelAnimationFrame(raf);

        // Snap to 100%
        bar.style.width = '100%';
        pct.textContent = '100%';

        // Brief pause at 100% before exiting
        setTimeout(() => {
          preloader.classList.add('exiting');

          // After curtain animation, fully hide
          setTimeout(() => {
            preloader.classList.add('hidden');
            document.body.style.overflow = ''; // restore scroll
            notifySiteReady();
          }, 700);
        }, 400);
      }

      // Prevent scroll during preload
      document.body.style.overflow = 'hidden';

      window.addEventListener('load', () => {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, MIN_DISPLAY - elapsed);
        setTimeout(dismissPreloader, remaining);
      });

      // Fallback: never block user longer than 5s total
      setTimeout(dismissPreloader, 5000);
    })();


    /* =============================================
       API CONFIG
    ============================================= */
    const API_BASE_URL = "https://api.biverroyaltyhomesltd.com/api";
    const PROPERTIES_API = window.BIVER_SITE?.propertiesApi || "api/properties.php";
    const TESTIMONIALS_API = window.BIVER_SITE?.testimonialsApi || "api/testimonials.php";
    const LOCATIONS_API = window.BIVER_SITE?.locationsApi || "api/locations.php";

    function fetchWithTimeout(url, options = {}, timeoutMs = 8000) {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), timeoutMs);
      return fetch(url, { ...options, signal: controller.signal }).finally(() => clearTimeout(timer));
    }

    const api = {
      getHeaders() {
        const headers = { "Content-Type": "application/json" };
        const token = localStorage.getItem('token');
        if (token) headers["Authorization"] = `Bearer ${token}`;
        return headers;
      },
      async get(endpoint) {
        const response = await fetchWithTimeout(`${API_BASE_URL}${endpoint}`, { headers: this.getHeaders() });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
      },
      async post(endpoint, data = {}) {
        const response = await fetchWithTimeout(`${API_BASE_URL}${endpoint}`, { method: 'POST', headers: this.getHeaders(), body: JSON.stringify(data) });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
      },
      async delete(endpoint) {
        const response = await fetchWithTimeout(`${API_BASE_URL}${endpoint}`, { method: 'DELETE', headers: this.getHeaders() });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
      },
      async getCurrentUser() { return await this.get('/users/me'); },
      async getProperties(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const response = await fetchWithTimeout(`${PROPERTIES_API}${queryString ? '?' + queryString : ''}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        if (data.success === false) throw new Error(data.message || 'Failed to load properties');
        return data;
      },
      async getFavorites() { return await this.get('/favorites'); },
      async addToFavorites(propertyId) { return await this.post(`/favorites/${propertyId}`); },
      async removeFromFavorites(propertyId) { return await this.delete(`/favorites/${propertyId}`); },
      async getCart() { return await this.get('/cart'); },
      async addToCart(propertyId) { return await this.post(`/cart/${propertyId}`); },
      async removeFromCart(propertyId) { return await this.delete(`/cart/${propertyId}`); },
      async getTestimonials() {
        const response = await fetchWithTimeout(TESTIMONIALS_API);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        if (data.success === false) throw new Error(data.message || 'Failed to load testimonials');
        return data;
      },
    };

    const utils = {
      formatNumber(num) {
        if (!num) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      },
      showToast(message, type = 'success') {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type === 'error' ? 'toast-error' : ''}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
          <div class="toast-content">
            <ion-icon name="${type === 'success' ? 'checkmark-circle' : 'alert-circle'}-outline"></ion-icon>
            <span>${message}</span>
          </div>
          <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">
            <ion-icon name="close-outline"></ion-icon>
          </button>`;
        document.body.appendChild(toast);
        setTimeout(() => { if (toast.parentElement) toast.remove(); }, 3500);
      },
      isAuthenticated() { return !!localStorage.getItem('token'); },
      redirectToLogin() { window.location.href = window.BIVER_SITE?.page('login') || 'login'; },
      truncate(text, length = 90) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
      },
      formatDate(dateString) {
        if (!dateString) return 'Recently';
        return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      },
      getInitials(name) {
        return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
      }
    };

    /* =============================================
       HERO SLIDESHOW WITH CONTENT TRANSITIONS
    ============================================= */
    const heroSlides = [
      {
        eyebrow: "Premium Real Estate Agency",
        title: "Biver <span class='accent'>Royalty</span> Homes",
        tagline: "Where your dreams come true — find premium properties in Owerri, Imo State, built on a foundation of integrity and excellence."
      },
      {
        eyebrow: "Luxury Properties",
        title: "Find Your <span class='accent'>Dream</span> Home",
        tagline: "Explore our curated selection of luxurious homes, from elegant apartments to expansive estates tailored to your lifestyle."
      },
      {
        eyebrow: "Trusted Since 2015",
        title: "Built on <span class='accent'>Integrity</span>",
        tagline: "Over 1,200 happy families have trusted us to guide them home. Experience real estate the way it should be — transparent, efficient, and personal."
      },
      {
        eyebrow: "Buy, Rent or Sell",
        title: "Your Property <span class='accent'>Journey</span> Starts Here",
        tagline: "Whether you're buying your first home, renting a space, or selling your property, our expert team walks every step with you."
      },
      {
        eyebrow: "Expert Market Knowledge",
        title: "Owerri's <span class='accent'>Finest</span> Real Estate",
        tagline: "With deep roots in Imo State, we know the best neighborhoods, the fairest prices, and the right time to make your move."
      }
    ];

    let currentSlide = 0;
    let slideTimer;

    function animateHeroContent() {
      const content = document.getElementById('heroContent');
      if (!content) return;
      content.classList.remove('slide-active');
      void content.offsetWidth;
      content.classList.add('slide-active');
    }

    function updateHeroText(index) {
      const slide = heroSlides[index];
      const titleEl   = document.getElementById('heroTitleText');
      const taglineEl = document.getElementById('heroTagline');
      const eyebrowEl = document.getElementById('heroEyebrowText');
      if (titleEl)   titleEl.innerHTML   = slide.title;
      if (taglineEl) taglineEl.textContent = slide.tagline;
      if (eyebrowEl) eyebrowEl.textContent = slide.eyebrow;
      animateHeroContent();
    }

    function showSlide(index) {
      const bgSlides = document.querySelectorAll('.bg-slide');
      const dots      = document.querySelectorAll('.slide-dots .dot');
      bgSlides.forEach(s => s.classList.remove('active'));
      dots.forEach(d => { d.classList.remove('active'); d.setAttribute('aria-selected', 'false'); });
      if (bgSlides[index]) bgSlides[index].classList.add('active');
      if (dots[index]) { dots[index].classList.add('active'); dots[index].setAttribute('aria-selected', 'true'); }
      updateHeroText(index);
      currentSlide = index;
    }

    function nextSlide() {
      const bgSlides = document.querySelectorAll('.bg-slide');
      showSlide((currentSlide + 1) % bgSlides.length);
    }

    function startSlideshow() {
      clearInterval(slideTimer);
      slideTimer = setInterval(nextSlide, 5500);
    }

    /* =============================================
       SCROLL REVEAL
    ============================================= */
    function initScrollReveal() {
      const revealEls = document.querySelectorAll('.reveal');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      revealEls.forEach(el => observer.observe(el));
    }
    /* =============================================
       LAZY HERO IMAGES — Feature #4
       Slide 1 background is already set in HTML (eager).
       Slides 2-5 use data-bg attributes and are loaded
       after a short delay so they don't compete with
       critical page resources on initial load.
    ============================================= */
    function initLazyHeroSlides() {
      // Load slides 2-5 after 1.5s — by then slide 1 is fully visible
      // and the browser has finished its critical rendering path
      setTimeout(() => {
        document.querySelectorAll('.bg-slide[data-bg]').forEach(slide => {
          const url = slide.getAttribute('data-bg');
          if (!url) return;
          // Preload image, then set background only after it's downloaded
          const img = new Image();
          img.onload = () => {
            slide.style.backgroundImage = `url('${url}')`;
            slide.removeAttribute('data-bg');
          };
          img.src = url;
        });
      }, 1500);
    }

    /* =============================================
       PRELOADER — prefers-reduced-motion awareness
       If user has reduced motion OS setting, skip the
       cinematic delay and dismiss immediately.
       (CSS already disables animations via media query.)
    ============================================= */
    function checkReducedMotionForPreloader() {
      const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReduced) {
        // Skip the cinematic delay entirely — just fade out fast
        const preloader = document.getElementById('preloader');
        if (preloader) {
          setTimeout(() => {
            preloader.classList.add('hidden');
            document.body.style.overflow = '';
          }, 400);
        }
      }
    }

    /* =============================================
       PROPERTIES MODULE
    ============================================= */
    /* =============================================
       PROPERTIES MODULE
    ============================================= */
    let featuredProperties = [];
    let userFavorites = [];
    let userCart = [];
    let currentUser = null;

    const propertiesSkeleton = document.getElementById('propertiesSkeleton');
    const propertiesList     = document.getElementById('propertiesList');
    const propertiesError    = document.getElementById('propertiesError');
    const propertiesEmpty    = document.getElementById('propertiesEmpty');
    const headerCartCount    = document.getElementById('headerCartCount');

    function hideEl(el) { if (el) el.classList.add('u-hidden'); }
    function showEl(el) { if (el) el.classList.remove('u-hidden'); }

    async function checkUserAuthentication() {
      if (!utils.isAuthenticated()) return;
      try {
        const data = await api.getCurrentUser();
        currentUser = data.user || data;
        await Promise.all([loadUserFavorites(), loadUserCart()]);
      } catch (error) {
        localStorage.removeItem('token');
      }
    }

    async function loadUserFavorites() {
      if (!currentUser) return;
      try {
        const data = await api.getFavorites();
        userFavorites = data.favorites || data || [];
      } catch (e) {}
    }

    async function loadUserCart() {
      if (!currentUser) return;
      try {
        const data = await api.getCart();
        userCart = data.cart || data || [];
        updateCartCount();
      } catch (e) {}
    }

    function updateCartCount() {
      if (!headerCartCount) return;
      headerCartCount.textContent = userCart.length;
      if (userCart.length > 0) showEl(headerCartCount); else hideEl(headerCartCount);
    }

    function isPropertyFavorite(id) {
      return userFavorites.some(f => f._id === id || f.property === id || f.property?._id === id || f.propertyId === id);
    }

    function isPropertyInCart(id) {
      return userCart.some(i => i._id === id || i.property === id || i.property?._id === id || i.propertyId === id);
    }

    async function loadFeaturedProperties() {
      try {
        showEl(propertiesSkeleton);
        hideEl(propertiesList);
        hideEl(propertiesError);
        hideEl(propertiesEmpty);

        const data = await api.getProperties({ limit: 20 });
        if (data.properties && data.properties.length > 0) {
          featuredProperties = data.properties;
          displayProperties(featuredProperties);
        } else {
          showPropertiesEmpty('No Properties Available', 'There are currently no approved properties. Check back later.', false);
        }
      } catch (error) {
        console.error('Failed to load properties:', error.message);
        showPropertiesEmpty('Unable to Load Properties', 'Please refresh the page or try again shortly.', true);
      }
    }

    function displayProperties(properties) {
      hideEl(propertiesSkeleton);
      hideEl(propertiesError);
      hideEl(propertiesEmpty);
      propertiesList.innerHTML         = '';
      properties.forEach(p => propertiesList.appendChild(createPropertyCard(p)));
      showEl(propertiesList);
    }

    function createPropertyCard(property) {
      const li = document.createElement('li');
      const priceDisplay = property.type === 'rent'
        ? `₦${utils.formatNumber(property.price)}/Month`
        : `₦${utils.formatNumber(property.price)}`;
      const imageUrl = property.imageUrl || (property.images && property.images.length > 0
        ? property.images[0]
        : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop');
      const hasVideo  = property.videoUrl || (property.videos && property.videos.length > 0);
      const videoUrl  = property.videoUrl || (hasVideo ? property.videos[0] : null);
      const badgeClass = property.type === 'rent' ? 'green' : 'orange';
      const badgeText  = property.type === 'rent' ? 'For Rent' : 'For Sale';
      const isNew      = property.createdAt && (new Date() - new Date(property.createdAt)) < 7 * 24 * 60 * 60 * 1000;
      const isFavorite = isPropertyFavorite(property._id);
      const isInCart   = isPropertyInCart(property._id);
      const agentName  = property.agent?.name || 'Biver Royalty Homes';
      const showVerification = property.approvalStatus === 'approved';

      const resolveMediaUrl = (url) => {
        if (!url) return '';
        if (url.startsWith('http') || url.startsWith('/')) return url;
        if (url.startsWith('assets/')) return './' + url;
        return url;
      };

      const finalImg = resolveMediaUrl(imageUrl);
      const finalVideo = videoUrl ? resolveMediaUrl(videoUrl) : null;

      let mediaHtml = hasVideo
        ? `<video src="${finalVideo}" poster="${finalImg}" controls preload="metadata" playsinline aria-label="Video tour of ${property.title}"></video>
           <div class="video-badge"><ion-icon name="videocam"></ion-icon><span>Video Tour</span></div>`
        : `<img src="${finalImg}" alt="${property.title}" class="w-100" loading="lazy">`;

      const detailUrl = window.BIVER_SITE?.propertyDetail
        ? window.BIVER_SITE.propertyDetail(property._id)
        : 'property-detail?id=' + encodeURIComponent(property._id);

      li.innerHTML = `
        <div class="property-card">
          <figure class="card-banner">
            <a href="${detailUrl}">${mediaHtml}</a>
            ${isNew ? '<div class="card-badge new-badge">New</div>' : ''}
            <div class="card-badge ${badgeClass}">${badgeText}</div>
            <div class="banner-actions">
              <button class="banner-actions-btn" aria-label="Location">
                <ion-icon name="location"></ion-icon>
                <span>${property.location || 'Location not specified'}</span>
              </button>
              ${property.images?.length ? `<button class="banner-actions-btn" aria-label="Photos"><ion-icon name="camera"></ion-icon><span>${property.images.length}</span></button>` : ''}
            </div>
          </figure>
          <div class="card-content">
            <div class="card-price"><strong>${priceDisplay}</strong></div>
            <h3 class="h3 card-title"><a href="${detailUrl}">${property.title}</a></h3>
            <p class="card-text">${utils.truncate(property.description)}</p>
            <ul class="card-list">
              <li class="card-item"><ion-icon name="bed-outline"></ion-icon> <strong>${property.bedrooms || 1}</strong><span>Beds</span></li>
              <li class="card-item"><ion-icon name="man-outline"></ion-icon> <strong>${property.bathrooms || 1}</strong><span>Baths</span></li>
              ${property.area ? `<li class="card-item"><ion-icon name="square-outline"></ion-icon> <strong>${utils.formatNumber(property.area)}</strong><span>ft²</span></li>` : ''}
            </ul>
          </div>
          <div class="card-footer">
            <div class="card-author">
              <figure class="author-avatar"><img src="./assets/images/author.jpg" alt="Agent" class="w-100" loading="lazy"></figure>
              <div>
                <p class="author-name">
                  ${showVerification
                    ? `<a href="#">${agentName} <span class="verified-badge" title="Verified"><ion-icon name="checkmark-circle"></ion-icon><span class="verified-text">Verified</span></span></a>`
                    : `<a href="#">${agentName}</a>`}
                </p>
                <p class="author-title">Estate Agent</p>
              </div>
            </div>
            <div class="card-footer-actions">
              <button class="card-footer-actions-btn ${isFavorite ? 'favorited' : ''}"
                      onclick="toggleFavorite('${property._id}', this)"
                      aria-label="${isFavorite ? 'Remove from favorites' : 'Add to favorites'}">
                <ion-icon name="${isFavorite ? 'heart' : 'heart-outline'}"></ion-icon>
              </button>
              <button class="card-footer-actions-btn ${isInCart ? 'in-cart' : ''}"
                      onclick="addToCart('${property._id}', this)"
                      aria-label="${isInCart ? 'Remove from cart' : 'Add to cart'}">
                <ion-icon name="${isInCart ? 'checkmark-circle' : 'add-circle-outline'}"></ion-icon>
              </button>
            </div>
          </div>
        </div>`;
      return li;
    }

    async function toggleFavorite(propertyId, button) {
      if (!utils.isAuthenticated()) {
        utils.showToast('Please login to save favorites', 'error');
        setTimeout(() => utils.redirectToLogin(), 1500);
        return;
      }
      try {
        const isFav = isPropertyFavorite(propertyId);
        if (isFav) {
          await api.removeFromFavorites(propertyId);
          button.querySelector('ion-icon').name = 'heart-outline';
          button.classList.remove('favorited');
          userFavorites = userFavorites.filter(f => f._id !== propertyId && f.property !== propertyId && f.propertyId !== propertyId);
          utils.showToast('Removed from favorites');
        } else {
          await api.addToFavorites(propertyId);
          button.querySelector('ion-icon').name = 'heart';
          button.classList.add('favorited');
          userFavorites.push({ propertyId });
          utils.showToast('Added to favorites');
        }
      } catch (e) { utils.showToast('Error updating favorites', 'error'); }
    }

    async function addToCart(propertyId, button) {
      if (!utils.isAuthenticated()) {
        utils.showToast('Please login to add to cart', 'error');
        setTimeout(() => utils.redirectToLogin(), 1500);
        return;
      }
      try {
        const inCart = isPropertyInCart(propertyId);
        if (inCart) {
          await api.removeFromCart(propertyId);
          button.querySelector('ion-icon').name = 'add-circle-outline';
          button.classList.remove('in-cart');
          userCart = userCart.filter(i => i._id !== propertyId && i.property !== propertyId && i.propertyId !== propertyId);
          updateCartCount();
          utils.showToast('Removed from cart');
        } else {
          await api.addToCart(propertyId);
          button.querySelector('ion-icon').name = 'checkmark-circle';
          button.classList.add('in-cart');
          userCart.push({ propertyId });
          updateCartCount();
          utils.showToast('Added to cart!');
        }
      } catch (e) { utils.showToast('Error updating cart', 'error'); }
    }

    function showPropertiesEmpty(title, message, isApproved = false) {
      hideEl(propertiesSkeleton);
      hideEl(propertiesError);
      showEl(propertiesEmpty);
      hideEl(propertiesList);
      propertiesEmpty.innerHTML = `
        <ion-icon name="${isApproved ? 'time-outline' : 'home-outline'}"></ion-icon>
        <h3>${title}</h3><p>${message}</p>
        <a href="${isApproved ? (window.BIVER_SITE?.page('property') || 'property') : (window.BIVER_SITE?.page('contact') || 'contact')}" class="btn u-mt-20">
          ${isApproved ? 'Browse All Properties' : 'Contact Us'}
        </a>`;
    }

    /* =============================================
       TESTIMONIALS MODULE
    ============================================= */
    let testimonials = [];
    let currentTestimonialSlide = 0;
    let testimonialsPerSlide = 3;

    async function loadTestimonials() {
      const skeleton = document.getElementById('testimonialsSkeleton');
      const error    = document.getElementById('testimonialsError');
      const empty    = document.getElementById('testimonialsEmpty');
      const slider   = document.getElementById('testimonialsSlider');
      try {
        showEl(skeleton);
        hideEl(error);
        hideEl(empty);
        hideEl(slider);
        const data = await api.getTestimonials();
        const items = data.data || [];
        if (items.length > 0) {
          testimonials = items;
          displayTestimonials(testimonials);
        } else {
          hideEl(skeleton);
          showEl(empty);
        }
      } catch (e) {
        console.warn('Testimonials load failed:', e.message);
        hideEl(skeleton);
        showEl(error);
      }
    }

    function displayTestimonials(testimonials) {
      const skeleton = document.getElementById('testimonialsSkeleton');
      const slider   = document.getElementById('testimonialsSlider');
      const listEl   = document.getElementById('testimonialsList');
      hideEl(skeleton);
      listEl.innerHTML = '';
      testimonials.forEach(t => listEl.appendChild(createTestimonialCard(t)));
      showEl(slider);
      generateTestimonialDots();
      updateTestimonialSlider();

      const prev = document.getElementById('testimonialPrev');
      const next = document.getElementById('testimonialNext');
      prev.onclick = () => {
        if (currentTestimonialSlide > 0) { currentTestimonialSlide--; updateTestimonialSlider(); }
      };
      next.onclick = () => {
        const max = Math.ceil(testimonials.length / testimonialsPerSlide) - 1;
        if (currentTestimonialSlide < max) { currentTestimonialSlide++; updateTestimonialSlider(); }
      };
    }

    function createTestimonialCard(testimonial) {
      const li = document.createElement('li');
      li.className = 'testimonial-item';
      const initials = testimonial.initials || utils.getInitials(testimonial.name);
      const rating = Math.max(1, Math.min(5, Number(testimonial.rating) || 5));
      const starsHtml = Array.from({ length: 5 }, (_, i) =>
        `<ion-icon name="${i < rating ? 'star' : 'star-outline'}"></ion-icon>`
      ).join('');
      const imageSrc = testimonial.image || '';
      li.innerHTML = `
        <div class="testimonial-card">
          <div class="testimonial-quote-icon"><ion-icon name="chatbubble-ellipses-outline"></ion-icon></div>
          <div class="testimonial-avatar ${!imageSrc ? 'no-image' : ''}">
            ${imageSrc
              ? `<img src="${imageSrc.startsWith('http') ? imageSrc : './' + imageSrc.replace(/^\.?\//, '')}" alt="${testimonial.name}" loading="lazy">`
              : `<span>${initials}</span>`}
          </div>
          <div class="testimonial-stars">${starsHtml}</div>
          <div class="testimonial-content">
            <p class="testimonial-text">${testimonial.message}</p>
          </div>
          <div class="testimonial-author">
            <h4>${testimonial.name}</h4>
            <p>${testimonial.roleLabel || 'Happy Client'}</p>
          </div>
        </div>`;
      return li;
    }

    function generateTestimonialDots() {
      const dotsEl = document.getElementById('testimonialDots');
      const total  = Math.ceil(testimonials.length / testimonialsPerSlide);
      dotsEl.innerHTML = '';
      for (let i = 0; i < total; i++) {
        const dot = document.createElement('button');
        dot.className = `slider-dot ${i === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Slide ${i + 1}`);
        dot.onclick = () => { currentTestimonialSlide = i; updateTestimonialSlider(); };
        dotsEl.appendChild(dot);
      }
    }

    function updateTestimonialSlider() {
      const listEl = document.getElementById('testimonialsList');
      const prev   = document.getElementById('testimonialPrev');
      const next   = document.getElementById('testimonialNext');
      const total  = Math.ceil(testimonials.length / testimonialsPerSlide);
      listEl.style.transform = `translateX(-${currentTestimonialSlide * 100}%)`;
      prev.disabled = currentTestimonialSlide === 0;
      next.disabled = currentTestimonialSlide === total - 1;
      document.querySelectorAll('.slider-dot').forEach((d, i) => {
        d.classList.toggle('active', i === currentTestimonialSlide);
      });
    }

    function updateTestimonialsPerSlide() {
      testimonialsPerSlide   = window.innerWidth >= 992 ? 3 : window.innerWidth >= 768 ? 2 : 1;
      currentTestimonialSlide = 0;
      if (testimonials.length > 0) { generateTestimonialDots(); updateTestimonialSlider(); }
    }

    /* =============================================
       SERVICE AREAS MODULE
    ============================================= */
    async function loadServiceAreas() {
      const listEl = document.getElementById('areasList');
      if (!listEl) return;
      try {
        const response = await fetchWithTimeout(LOCATIONS_API);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        const areas = data.areas || [];
        const section = data.section || {};

        if (section.intro) {
          const introEl = document.getElementById('areasIntro');
          if (introEl) introEl.textContent = section.intro;
        }
        if (section.ctaText) {
          const ctaTextEl = document.getElementById('areasCtaText');
          if (ctaTextEl) ctaTextEl.textContent = section.ctaText;
        }
        const ctaBtn = document.getElementById('areasCtaBtn');
        if (ctaBtn) {
          if (section.ctaLabel) ctaBtn.textContent = section.ctaLabel;
          if (section.ctaLink) ctaBtn.href = section.ctaLink;
        }

        if (!areas.length) {
          listEl.innerHTML = '<li><article class="area-card"><div class="area-card-body"><p class="area-card-text">Service areas coming soon.</p></div></article></li>';
          return;
        }

        listEl.innerHTML = areas.map((area, index) => {
          const delay = (index % 3) + 1;
          const meta1 = area.meta1Text ? `<span><ion-icon name="${area.meta1Icon || 'home-outline'}"></ion-icon> ${area.meta1Text}</span>` : '';
          const meta2 = area.meta2Text ? `<span><ion-icon name="${area.meta2Icon || 'star-outline'}"></ion-icon> ${area.meta2Text}</span>` : '';
          return `
            <li class="reveal reveal-delay-${delay}">
              <article class="area-card">
                <div class="area-card-banner">
                  <img src="${area.imageUrl}" alt="${area.title}" width="800" height="500" loading="lazy">
                  ${area.tag ? `<span class="area-card-tag">${area.tag}</span>` : ''}
                </div>
                <div class="area-card-body">
                  <h3 class="area-card-title">${area.title}</h3>
                  <p class="area-card-text">${area.description}</p>
                  <div class="area-card-meta">${meta1}${meta2}</div>
                  <a href="${area.linkUrl || (window.BIVER_SITE?.page('property') || 'property')}" class="area-card-link">View listings <ion-icon name="arrow-forward-outline"></ion-icon></a>
                </div>
              </article>
            </li>`;
        }).join('');
        initScrollReveal();
      } catch (e) {
        console.warn('Service areas load failed:', e.message);
        listEl.innerHTML = '<li><article class="area-card"><div class="area-card-body"><p class="area-card-text">Unable to load service areas right now.</p></div></article></li>';
      }
    }

    /* =============================================
       INIT — All features wired up here
    ============================================= */
    document.addEventListener('DOMContentLoaded', async () => {

      // Feature #5: Check reduced-motion FIRST — may fast-dismiss preloader
      checkReducedMotionForPreloader();

      // Hero slideshow dots
      const dots = document.querySelectorAll('.slide-dots .dot');
      dots.forEach((dot, i) => {
        dot.addEventListener('click', () => { showSlide(i); startSlideshow(); });
        dot.addEventListener('keydown', e => {
          if (e.key === 'Enter' || e.key === ' ') { showSlide(i); startSlideshow(); }
        });
      });
      showSlide(0);
      startSlideshow();

      // Feature #4: Lazy-load hero slides 2–5 after slide 1 is shown
      initLazyHeroSlides();

      initScrollReveal();

      // Responsive testimonials
      updateTestimonialsPerSlide();
      window.addEventListener('resize', updateTestimonialsPerSlide);

      // Data loading (do not block page render on slow external auth API)
      checkUserAuthentication();
      await Promise.all([
        loadFeaturedProperties(),
        loadTestimonials(),
        loadServiceAreas()
      ]);
    });
  </script>
  <?php require __DIR__ . '/assets/includes/cookie-banner.php'; ?>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
  <script src="./assets/js/site-banners.js" defer></script>
</body>
</html>