<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="List your property with Biver Royalty Homes. Submit property details, images, and videos for expert real estate marketing in Nigeria.">
  <meta name="keywords" content="list property Nigeria, sell property Owerri, property listing, upload property images, upload property video">
  <meta name="author" content="Biver Royalty Homes Ltd">
  <title>List Your Property | Biver Royalty Homes</title>
  <link rel="shortcut icon" href="./assets/images/biver-logo.png" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/site-variables.css">
  <link rel="stylesheet" href="./assets/css/site-utilities.css">
  <link rel="stylesheet" href="./assets/css/list-your-property.css">
  <?php require_once __DIR__ . '/includes/site_paths.php'; ?>
  <link rel="stylesheet" href="./assets/css/site-header.css">
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>

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
    <section class="listing-hero" aria-label="List your property hero">
      <div class="container">
        <div class="hero-content">
          <p class="hero-eyebrow">Sell or rent with confidence</p>
          <h1>List Your <span>Property</span> With Royal Exposure</h1>
          <p class="hero-copy">
            Give your property the presentation it deserves. Submit verified details, professional media, and your preferred selling terms. Our team reviews every listing before promoting it to qualified buyers and tenants.
          </p>
          <div class="hero-actions">
            <a href="#listingForm" class="hero-btn">
              Start Listing <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
            <a href="tel:+2349033137432" class="hero-btn-outline" aria-label="Call Biver Royalty Homes agent at +234 903 313 7432">
              Speak With an Agent <ion-icon name="call-outline"></ion-icon>
            </a>
          </div>
        </div>
        <aside class="seller-card" aria-label="Seller benefits">
          <h2 class="seller-card-title">Built for serious sellers</h2>
          <ul class="seller-card-list">
            <li><ion-icon name="shield-checkmark-outline"></ion-icon><span>Every submission is reviewed before going live to protect seller trust and buyer confidence.</span></li>
            <li><ion-icon name="videocam-outline"></ion-icon><span>Add property walkthrough videos and multiple image angles for stronger enquiries.</span></li>
            <li><ion-icon name="people-outline"></ion-icon><span>Our team helps position your property for buyers, renters, and investors.</span></li>
          </ul>
        </aside>
      </div>
    </section>

    <section class="trust-strip" aria-label="Listing trust metrics">
      <div class="container">
        <div class="trust-panel">
          <div class="trust-item">
            <div class="trust-number">01</div>
            <div class="trust-label">Submit details</div>
          </div>
          <div class="trust-item">
            <div class="trust-number">02</div>
            <div class="trust-label">Upload media</div>
          </div>
          <div class="trust-item">
            <div class="trust-number">03</div>
            <div class="trust-label">Agent review</div>
          </div>
          <div class="trust-item">
            <div class="trust-number">04</div>
            <div class="trust-label">Qualified leads</div>
          </div>
        </div>
      </div>
    </section>

    <section class="listing-section" aria-label="Property listing form">
      <div class="container">
        <div class="section-heading">
          <p class="section-kicker">Property submission</p>
          <h2>Tell Us What You Want to Sell, Rent, or Lease</h2>
          <p>Complete the form below with accurate ownership, pricing, location, and media details. Quality images and videos help your property stand out and attract faster enquiries.</p>
        </div>

        <div class="listing-grid">
          <aside class="process-card">
            <h3>How it works</h3>
            <ul class="process-list">
              <li>
                <div class="process-step">1</div>
                <div><strong>Submit property details</strong><span>Share location, type, price, features, and seller contact details.</span></div>
              </li>
              <li>
                <div class="process-step">2</div>
                <div><strong>Upload photos and videos</strong><span>Add clear images and walkthrough videos so buyers can understand the property quickly.</span></div>
              </li>
              <li>
                <div class="process-step">3</div>
                <div><strong>Verification call</strong><span>A Biver Royalty Homes agent will review the listing and contact you before publication.</span></div>
              </li>
              <li>
                <div class="process-step">4</div>
                <div><strong>Market professionally</strong><span>Your property is positioned for interested clients through our listing channels.</span></div>
              </li>
            </ul>
            <div class="agent-note">
              <strong>Need help preparing media?</strong>
              <p>Our team can guide you on the best photos, video angles, and documents to make your property listing stronger.</p>
              <a href="tel:+2349033137432"><ion-icon name="call-outline"></ion-icon> +234 903 313 7432</a>
            </div>
          </aside>

          <section class="listing-form-card" id="listingForm">
            <h3>List Your Property</h3>
            <p class="form-intro">Fields marked with <span class="required">*</span> are required. After submission, your listing will be reviewed by our team before it appears on the website.</p>

            <form id="propertyListingForm" enctype="multipart/form-data" novalidate>
              <div class="form-section">
                <div class="form-section-title"><ion-icon name="person-outline"></ion-icon> Owner information</div>
                <div class="form-grid">
                  <div class="form-field">
                    <label for="ownerName">Full name <span class="required">*</span></label>
                    <input type="text" id="ownerName" name="ownerName" placeholder="Your full name" required>
                  </div>
                  <div class="form-field">
                    <label for="ownerPhone">Phone number <span class="required">*</span></label>
                    <input type="tel" id="ownerPhone" name="ownerPhone" placeholder="+234..." required>
                  </div>
                  <div class="form-field">
                    <label for="ownerEmail">Email address</label>
                    <input type="email" id="ownerEmail" name="ownerEmail" placeholder="you@example.com" required>
                  </div>
                  <div class="form-field">
                    <label for="contactMethod">Preferred contact</label>
                    <select id="contactMethod" name="contactMethod">
                      <option value="phone">Phone call</option>
                      <option value="whatsapp">WhatsApp</option>
                      <option value="email">Email</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-title"><ion-icon name="home-outline"></ion-icon> Property details</div>
                <div class="form-grid">
                  <div class="form-field full">
                    <label for="propertyTitle">Property title <span class="required">*</span></label>
                    <input type="text" id="propertyTitle" name="propertyTitle" placeholder="Example: Luxury 4 Bedroom Duplex in New Owerri" required>
                  </div>
                  <div class="form-field">
                    <label for="listingType">Listing purpose <span class="required">*</span></label>
                    <select id="listingType" name="listingType" required>
                      <option value="">Select purpose</option>
                      <option value="sale">For Sale</option>
                      <option value="rent">For Rent</option>
                      <option value="shortlet">Shortlet</option>
                      <option value="lease">Lease</option>
                    </select>
                  </div>
                  <div class="form-field">
                    <label for="propertyType">Property type <span class="required">*</span></label>
                    <select id="propertyType" name="propertyType" required>
                      <option value="">Select type</option>
                      <option value="duplex">Duplex</option>
                      <option value="bungalow">Bungalow</option>
                      <option value="apartment">Apartment</option>
                      <option value="land">Land</option>
                      <option value="commercial">Commercial property</option>
                      <option value="estate">Estate development</option>
                    </select>
                  </div>
                  <div class="form-field">
                    <label for="propertyLocation">Location / area <span class="required">*</span></label>
                    <input type="text" id="propertyLocation" name="propertyLocation" placeholder="Example: New Owerri, Imo State" required>
                  </div>
                  <div class="form-field">
                    <label for="propertyPrice">Asking price <span class="required">*</span></label>
                    <input type="text" id="propertyPrice" name="propertyPrice" placeholder="Example: NGN 85,000,000" required>
                  </div>
                  <div class="form-field">
                    <label for="bedrooms">Bedrooms</label>
                    <input type="number" id="bedrooms" name="bedrooms" min="0" placeholder="4">
                  </div>
                  <div class="form-field">
                    <label for="bathrooms">Bathrooms</label>
                    <input type="number" id="bathrooms" name="bathrooms" min="0" placeholder="5">
                  </div>
                  <div class="form-field">
                    <label for="propertySize">Size / land measurement</label>
                    <input type="text" id="propertySize" name="propertySize" placeholder="Example: 500 sqm or 1 plot">
                  </div>
                  <div class="form-field">
                    <label for="ownershipStatus">Ownership status</label>
                    <select id="ownershipStatus" name="ownershipStatus">
                      <option value="">Select status</option>
                      <option value="owner">I am the owner</option>
                      <option value="agent">I am an authorized agent</option>
                      <option value="family">Family property</option>
                      <option value="company">Company-owned property</option>
                    </select>
                  </div>
                  <div class="form-field full">
                    <label for="propertyAddress">Full property address <span class="required">*</span></label>
                    <input type="text" id="propertyAddress" name="propertyAddress" placeholder="Street, landmark, area, city, state" required>
                  </div>
                  <div class="form-field full">
                    <label for="propertyFeatures">Key features</label>
                    <input type="text" id="propertyFeatures" name="propertyFeatures" placeholder="Example: POP ceiling, CCTV, borehole, fitted kitchen, parking">
                    <span class="hint">Separate features with commas.</span>
                  </div>
                  <div class="form-field full">
                    <label for="propertyDescription">Property description <span class="required">*</span></label>
                    <textarea id="propertyDescription" name="propertyDescription" placeholder="Describe the property, access road, neighborhood, title documents, and current condition." required></textarea>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-title"><ion-icon name="images-outline"></ion-icon> Media upload</div>
                <div class="upload-grid">
                  <div>
                    <label class="upload-box" for="propertyImages" data-upload-box>
                      <input type="file" id="propertyImages" name="propertyImages[]" accept="image/*" multiple required>
                      <span class="upload-content">
                        <ion-icon name="image-outline"></ion-icon>
                        <strong>Upload property images <span class="required">*</span></strong>
                        <span>Upload clear exterior, interior, kitchen, bathroom, compound, and street-view photos.</span>
                      </span>
                    </label>
                    <div class="preview-list" id="imagePreview" aria-live="polite"></div>
                  </div>
                  <div>
                    <label class="upload-box" for="propertyVideos" data-upload-box>
                      <input type="file" id="propertyVideos" name="propertyVideos[]" accept="video/*" multiple>
                      <span class="upload-content">
                        <ion-icon name="videocam-outline"></ion-icon>
                        <strong>Upload property videos <span class="required">*</span></strong>
                        <span>Add walkthrough videos, compound views, street access, and room-by-room clips.</span>
                      </span>
                    </label>
                    <div class="preview-list" id="videoPreview" aria-live="polite"></div>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <div class="form-section-title"><ion-icon name="document-text-outline"></ion-icon> Documents and notes</div>
                <div class="form-grid">
                  <div class="form-field">
                    <label for="titleDocument">Title document type</label>
                    <select id="titleDocument" name="titleDocument">
                      <option value="">Select document</option>
                      <option value="c-of-o">Certificate of Occupancy</option>
                      <option value="deed">Deed of Assignment</option>
                      <option value="survey">Survey Plan</option>
                      <option value="allocation">Allocation Paper</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                  <div class="form-field">
                    <label for="inspectionAvailability">Inspection availability</label>
                    <input type="text" id="inspectionAvailability" name="inspectionAvailability" placeholder="Example: Weekdays after 2pm">
                  </div>
                  <div class="form-field full">
                    <label for="sellerNotes">Additional notes</label>
                    <textarea id="sellerNotes" name="sellerNotes" placeholder="Share anything our listing team should know before contacting you."></textarea>
                  </div>
                </div>
              </div>

              <div class="submit-row">
                <p class="privacy-note">By submitting, you confirm that the property information is accurate and that Biver Royalty Homes may contact you to verify the listing.</p>
                <button type="submit" class="submit-btn">
                  Submit Property <ion-icon name="send-outline"></ion-icon>
                </button>
              </div>
              <div class="form-message" id="formMessage" role="status"></div>
            </form>
          </section>
        </div>
      </div>
    </section>

    <section class="marketing-section" aria-label="How Biver Royalty Homes markets properties">
      <div class="container">
        <div class="section-heading">
          <p class="section-kicker">World-class presentation</p>
          <h2>We Position Your Property to Attract Serious Buyers</h2>
          <p>From media review to qualified enquiry handling, our listing process is designed to make your property look credible, premium, and easy to inspect.</p>
        </div>
        <div class="marketing-grid">
          <article class="marketing-card">
            <ion-icon name="camera-outline"></ion-icon>
            <h3>Media-led listings</h3>
            <p>Quality images and videos help clients understand the value of your property before they request inspection.</p>
          </article>
          <article class="marketing-card">
            <ion-icon name="shield-checkmark-outline"></ion-icon>
            <h3>Verified confidence</h3>
            <p>We review property details, contact information, and ownership context before moving a listing forward.</p>
          </article>
          <article class="marketing-card">
            <ion-icon name="megaphone-outline"></ion-icon>
            <h3>Premium promotion</h3>
            <p>Your property can be presented across listing channels and matched with buyers or tenants seeking that location.</p>
          </article>
        </div>
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

  <button id="scrollToTop" aria-label="Scroll to top">
    <ion-icon name="chevron-up-outline"></ion-icon>
  </button>

  <script src="./assets/js/site-header.js" defer></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <script>
    'use strict';

    const form = document.getElementById('propertyListingForm');
    const imageInput = document.getElementById('propertyImages');
    const videoInput = document.getElementById('propertyVideos');
    const imagePreview = document.getElementById('imagePreview');
    const videoPreview = document.getElementById('videoPreview');
    const formMessage = document.getElementById('formMessage');

    if (!form || !imageInput || !formMessage) {
      console.error('List Your Property form could not initialize.');
    } else {

    function showFormMessage(text, isError) {
      formMessage.textContent = text;
      formMessage.className = 'form-message show';
      if (isError) {
        formMessage.style.background = 'rgba(249, 90, 90, 0.1)';
        formMessage.style.borderColor = 'rgba(249, 90, 90, 0.18)';
        formMessage.style.color = '#a12727';
      } else {
        formMessage.style.background = 'rgba(16,183,89,0.1)';
        formMessage.style.borderColor = 'rgba(16,183,89,0.18)';
        formMessage.style.color = '#087a3d';
      }
      formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function formatFileSize(bytes) {
      if (!bytes) return '0 KB';
      const units = ['B', 'KB', 'MB', 'GB'];
      const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
      return `${(bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
    }

    function removeFileAtIndex(fileList, index) {
      const next = new DataTransfer();
      Array.from(fileList.files || []).forEach((file, i) => {
        if (i !== index) next.items.add(file);
      });
      return next;
    }

    function renderPreviews(fileList, target, type, onRemove) {
      target.innerHTML = '';
      Array.from(fileList.files || []).forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'preview-item';

        if (type === 'image') {
          const img = document.createElement('img');
          img.className = 'preview-thumb';
          img.alt = file.name;
          img.src = URL.createObjectURL(file);
          item.appendChild(img);
        } else {
          const video = document.createElement('video');
          video.className = 'preview-thumb';
          video.controls = true;
          video.src = URL.createObjectURL(file);
          video.style.width = '120px';
          video.style.height = '80px';
          video.style.objectFit = 'cover';
          item.appendChild(video);
        }

        const text = document.createElement('span');
        text.textContent = `${file.name} - ${formatFileSize(file.size)}`;
        item.appendChild(text);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'preview-remove';
        removeBtn.setAttribute('aria-label', `Remove ${file.name}`);
        removeBtn.textContent = '\u00d7';
        removeBtn.addEventListener('click', () => onRemove(index));
        item.appendChild(removeBtn);

        target.appendChild(item);
      });
    }

    let imageFileList = new DataTransfer();
    let videoFileList = new DataTransfer();

    function syncImageUpload() {
      imageInput.files = imageFileList.files;
      renderPreviews(imageFileList, imagePreview, 'image', (index) => {
        imageFileList = removeFileAtIndex(imageFileList, index);
        syncImageUpload();
      });
    }

    function syncVideoUpload() {
      if (!videoInput || !videoPreview) return;
      videoInput.files = videoFileList.files;
      renderPreviews(videoFileList, videoPreview, 'video', (index) => {
        videoFileList = removeFileAtIndex(videoFileList, index);
        syncVideoUpload();
      });
    }

    function addFilesToList(fileList, incoming, filterFn) {
      Array.from(incoming || []).forEach((file) => {
        if (!filterFn || filterFn(file)) {
          fileList.items.add(file);
        }
      });
    }

    function resetUploadLists() {
      imageFileList = new DataTransfer();
      videoFileList = new DataTransfer();
      imagePreview.innerHTML = '';
      if (videoPreview) videoPreview.innerHTML = '';
    }

    function buildSubmitFormData() {
      const formData = new FormData(form);
      formData.delete('propertyImages[]');
      formData.delete('propertyImages');
      formData.delete('propertyVideos[]');
      formData.delete('propertyVideos');
      Array.from(imageInput.files || []).forEach((file) => formData.append('propertyImages[]', file));
      if (videoInput) {
        Array.from(videoInput.files || []).forEach((file) => formData.append('propertyVideos[]', file));
      }
      return formData;
    }

    document.querySelectorAll('[data-upload-box]').forEach((box) => {
      const input = box.querySelector('input[type="file"]');
      const isImageBox = input && input.id === 'propertyImages';
      const isVideoBox = input && input.id === 'propertyVideos';

      ['dragenter', 'dragover'].forEach((eventName) => {
        box.addEventListener(eventName, (event) => {
          event.preventDefault();
          box.classList.add('dragover');
        });
      });
      ['dragleave', 'drop'].forEach((eventName) => {
        box.addEventListener(eventName, () => box.classList.remove('dragover'));
      });
      box.addEventListener('drop', (event) => {
        event.preventDefault();
        if (!event.dataTransfer.files.length) return;

        if (isImageBox) {
          addFilesToList(imageFileList, event.dataTransfer.files, (file) => file.type.startsWith('image/'));
          syncImageUpload();
        } else if (isVideoBox) {
          addFilesToList(videoFileList, event.dataTransfer.files, (file) => file.type.startsWith('video/'));
          syncVideoUpload();
        }
      });
    });

    imageInput.addEventListener('change', () => {
      addFilesToList(imageFileList, imageInput.files, (file) => file.type.startsWith('image/'));
      syncImageUpload();
    });

    if (videoInput && videoPreview) {
      videoInput.addEventListener('change', () => {
        addFilesToList(videoFileList, videoInput.files, (file) => file.type.startsWith('video/'));
        syncVideoUpload();
      });
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      if (!imageInput.files.length) {
        showFormMessage('Please upload at least one property image before submitting.', true);
        return;
      }

      const submitBtn = form.querySelector('.submit-btn');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Submitting...';

      try {
        const response = await fetch('api/property-submit.php', {
          method: 'POST',
          body: buildSubmitFormData()
        });
        const raw = await response.text();
        let data = {};
        try {
          data = raw ? JSON.parse(raw) : {};
        } catch (parseError) {
          throw new Error('Server returned an invalid response. Please try again.');
        }

        if (!response.ok || data.success === false) {
          throw new Error(data.message || 'Submission failed.');
        }

        showFormMessage(data.message || 'Listing sent successfully. Waiting for admin approval.', false);
        form.reset();
        resetUploadLists();
      } catch (error) {
        showFormMessage(error.message || 'Unable to submit your listing. Please try again.', true);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });

    }
  </script>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>
