<?php require_once __DIR__ . '/includes/htaccess_redirect.php'; ?>
<?php
require_once __DIR__ . '/includes/FaqRepository.php';
require_once __DIR__ . '/includes/site_helpers.php';
require_once __DIR__ . '/includes/SeoService.php';

try {
    FaqRepository::ensureSchema();
    $contactFaqs = FaqRepository::getPublic();
} catch (Throwable $e) {
    $contactFaqs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'Contact Biver Royalty Homes Ltd | Wetheral Road, Owerri',
    'description' => 'Visit Biver Royalty Homes Ltd at 31 Wetheral Road, Angelina Plaza, Owerri, or call +234 903 685 1168 for buying, renting, and selling property in Imo State.',
    'keywords' => 'contact Biver Royalty Homes Owerri, real estate Wetheral Road, estate agent Owerri phone, property consultation Imo',
    'page' => 'contact',
    'stylesheets' => ['./assets/css/contact.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'Contact'],
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
    <!-- Hero Section -->
    <section class="contact-hero">
      <div class="container">
        <h1>Let's <span class="gold-accent">Connect</span></h1>
        <p>Whether you're looking to buy, sell, rent, or just explore � we're here to help you every step of the way.</p>
      </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
      <div class="container">
        <div class="contact-grid">
          <!-- Left Side - Contact Info -->
          <div>
            <div class="info-card">
              <div class="info-icon"><ion-icon name="call-outline"></ion-icon></div>
              <h3>Phone & WhatsApp</h3>
              <p><a href="tel:+2349036851168">+234 903 685 1168</a></p>
              <p><a href="https://wa.me/2348142523251" target="_blank" rel="noopener noreferrer"><ion-icon name="logo-whatsapp"></ion-icon> +234 814 252 3251</a></p>
              <p class="info-card-hours">Mon - Sat: 8am - 6pm</p>
            </div>
            
            <div class="info-card">
              <div class="info-icon"><ion-icon name="mail-outline"></ion-icon></div>
              <h3>Email Address</h3>
              <p><a href="mailto:biverroyaltyhomes01@gmail.com">biverroyaltyhomes01@gmail.com</a></p>
              <p><a href="mailto:info@biverroyaltyhomesltd.com">info@biverroyaltyhomesltd.com</a></p>
            </div>
            
            <div class="info-card">
              <div class="info-icon"><ion-icon name="location-outline"></ion-icon></div>
              <h3>Visit Our Office</h3>
              <p>No. 31 Wetheral Road, Angelina Plaza</p>
              <p>Opposite Reem Fuel Station, Owerri, Imo State</p>
            </div>
            
            <div class="social-links-section">
              <h3>Connect With Us</h3>
              <div class="social-icons">
                <a href="https://www.facebook.com/share/1B8mwpRi5L/" class="social-icon-link" target="_blank"><ion-icon name="logo-facebook"></ion-icon></a>
                <a href="https://www.instagram.com/biverroyaltyhomes.ng" class="social-icon-link" target="_blank"><ion-icon name="logo-instagram"></ion-icon></a>
                <a href="#" class="social-icon-link" target="_blank"><ion-icon name="logo-twitter"></ion-icon></a>
                <a href="https://www.tiktok.com/@biverroyaltyhomesltd" class="social-icon-link" target="_blank"><ion-icon name="logo-youtube"></ion-icon></a>
                <a href="#" class="social-icon-link" target="_blank"><ion-icon name="logo-linkedin"></ion-icon></a>
              </div>
            </div>
          </div>

          <!-- Right Side - Contact Form -->
          <div class="form-container">
            <h2>Send Us a Message</h2>
            <p class="form-subtitle">Fill out the form below and we'll get back to you within 24 hours.</p>
            
            <form id="contactForm">
              <div class="form-group">
                <input type="text" id="fullName" placeholder="Your Full Name" required>
              </div>
              <div class="form-group">
                <input type="email" id="email" placeholder="Email Address" required>
              </div>
              <div class="form-group">
                <input type="tel" id="phone" placeholder="Phone Number">
              </div>
              <div class="form-group">
                <select id="inquiryType">
                  <option value="general">General Inquiry</option>
                  <option value="buying">Interested in Buying</option>
                  <option value="renting">Interested in Renting</option>
                  <option value="selling">Selling a Property</option>
                  <option value="partnership">Partnership Opportunity</option>
                </select>
              </div>
              <div class="form-group">
                <textarea id="message" placeholder="Tell us about your real estate needs..." required></textarea>
              </div>
              <button type="submit" class="submit-btn">
                <ion-icon name="send-outline"></ion-icon>
                Send Message
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
      <div class="container">
        <div class="map-container">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.965429684286!2d7.0082!3d5.4839!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1042d2a9b4b8b8b9%3A0x8b8b8b8b8b8b8b8b!2sOwerri%2C%20Imo%20State%2C%20Nigeria!5e0!3m2!1sen!2sng!4v1700000000000!5m2!1sen!2sng" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
      <div class="container">
        <div class="faq-header">
          <div class="faq-eyebrow">
            <div class="faq-eyebrow-line"></div>
            <span class="faq-eyebrow-label">FAQ</span>
            <div class="faq-eyebrow-line"></div>
          </div>
          <h2 class="faq-heading">Frequently Asked Questions</h2>
          <p class="faq-sub"><a href="<?= pageHref('faqs') ?>">View all FAQs</a></p>
        </div>
        
        <div class="faq-grid" id="faqGrid">
          <?php if ($contactFaqs === []): ?>
          <div class="faq-item">
            <div class="faq-question">How can I schedule a property viewing? <ion-icon name="chevron-down-outline"></ion-icon></div>
            <div class="faq-answer">Call us at <?= siteEscape(siteContactPhone()) ?> or fill out our contact form. Our team will arrange a convenient viewing time.</div>
          </div>
          <?php else: ?>
          <?php foreach (array_slice($contactFaqs, 0, 6) as $faq): ?>
          <div class="faq-item">
            <div class="faq-question"><?= siteEscape((string) $faq['question']) ?> <ion-icon name="chevron-down-outline"></ion-icon></div>
            <div class="faq-answer"><?= siteEscape((string) $faq['answer']) ?></div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
      <div class="container">
        <h2>Ready to Find Your Dream Home?</h2>
        <p>Let our expert team guide you through the journey of finding the perfect property.</p>
        <a href="<?= pageHref('property') ?>" class="cta-button"><ion-icon name="home-outline"></ion-icon> Explore Properties</a>
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

  <button id="scrollToTop"><ion-icon name="chevron-up-outline"></ion-icon></button>

    <script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <script>
    // FAQ Accordion
    document.querySelectorAll('.faq-item').forEach(item => {
      item.addEventListener('click', () => {
        item.classList.toggle('active');
      });
    });

    // Form submission
    const contactForm = document.getElementById('contactForm');
    
    function showToast(message) {
      const existing = document.querySelector('.toast-message');
      if (existing) existing.remove();
      const toast = document.createElement('div');
      toast.className = 'toast-message';
      toast.innerHTML = `<ion-icon name="checkmark-circle-outline"></ion-icon><span>${message}</span>`;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 4000);
    }

    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = {
        name: document.getElementById('fullName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        inquiryType: document.getElementById('inquiryType').value,
        message: document.getElementById('message').value,
        date: new Date().toISOString()
      };
      
      // Validate
      if (!formData.name || !formData.email || !formData.message) {
        showToast('Please fill in all required fields');
        return;
      }
      
      const submitBtn = document.querySelector('.submit-btn');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon>Sending...';
      submitBtn.disabled = true;
      
      try {
        const response = await fetch('api/contact-submit.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to send message. Please try again.');
        }

        showToast(data.message || 'Message sent successfully! We\'ll get back to you soon.');
        contactForm.reset();

      } catch (error) {
        showToast(error.message || 'Unable to send message. Please call us at +234 903 685 1168.');
      } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    });
  </script>
  <?php require __DIR__ . '/assets/includes/site-end.php'; ?>
  <?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
  <?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>