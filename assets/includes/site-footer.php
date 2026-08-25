<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/site_paths.php';
?>
  <footer class="footer" role="contentinfo">
    <div class="footer-top">
      <div class="container">
        <div class="footer-brand">
          <a href="<?= siteEscape(pageUrl('index')) ?>" class="logo">
            <img src="./assets/images/biver-logo.png" alt="Biver Royalty Homes" width="150" height="auto" loading="lazy">
          </a>
          <p class="section-text">
            Biver Royalty Homes Ltd is a real estate agency in Owerri, Imo State, built on integrity. We help clients buy, rent, and sell verified homes within their budget.
          </p>
          <ul class="contact-list">
            <li>
              <a href="<?= siteEscape(pageUrl('contact')) ?>" class="contact-link">
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
            <li><a href="<?= siteEscape(pageUrl('about')) ?>" class="footer-link">About Us</a></li>
            <li><a href="<?= siteEscape(pageUrl('blog')) ?>" class="footer-link">Blog</a></li>
            <li><a href="<?= siteEscape(pageUrl('property')) ?>" class="footer-link">All Properties</a></li>
            <li><a href="<?= siteEscape(pageUrl('locations')) ?>" class="footer-link">Owerri Locations</a></li>
            <li><a href="<?= siteEscape(pageUrl('faqs')) ?>" class="footer-link">FAQ</a></li>
            <li><a href="<?= siteEscape(pageUrl('contact')) ?>" class="footer-link">Contact Us</a></li>
          </ul>
          <ul class="footer-list">
            <li><p class="footer-list-title">Services</p></li>
            <li><a href="<?= siteEscape(pageUrl('services')) ?>" class="footer-link">Our Services</a></li>
            <li><a href="<?= siteEscape(pageUrl('property')) ?>" class="footer-link">Buy a Home</a></li>
            <li><a href="<?= siteEscape(pageUrl('property')) ?>" class="footer-link">Rent a Home</a></li>
            <li><a href="<?= siteEscape(pageUrl('list-your-property')) ?>" class="footer-link">List Your Property</a></li>
            <li><a href="<?= siteEscape(pageUrl('services')) ?>" class="footer-link">Estate Management</a></li>
            <li><a href="<?= siteEscape(pageUrl('contact')) ?>" class="footer-link">Property Consultation</a></li>
          </ul>
          <ul class="footer-list">
            <li><p class="footer-list-title">Explore</p></li>
            <li><a href="<?= siteEscape(pageUrl('locations')) ?>" class="footer-link">Owerri &amp; Imo Areas</a></li>
            <li><a href="<?= siteEscape(pageUrl('property')) ?>" class="footer-link">Browse Properties</a></li>
            <li><a href="<?= siteEscape(pageUrl('list-your-property')) ?>" class="footer-link">Sell With Us</a></li>
            <li><a href="<?= siteEscape(pageUrl('faqs')) ?>" class="footer-link">FAQ</a></li>
            <li><a href="<?= siteEscape(pageUrl('blog')) ?>" class="footer-link">Blog</a></li>
            <li><a href="<?= siteEscape(pageUrl('contact')) ?>" class="footer-link">Contact Us</a></li>
            <li><a href="<?= siteEscape(pageUrl('terms')) ?>" class="footer-link">Terms &amp; Conditions</a></li>
            <li><a href="<?= siteEscape(pageUrl('privacy')) ?>" class="footer-link">Privacy Policy</a></li>
            <li><a href="<?= siteEscape(pageUrl('cookie-policy')) ?>" class="footer-link">Cookie Policy</a></li>
          </ul>
        </div>
      </div>
    </div>
    <?php require __DIR__ . '/newsletter-strip.php'; ?>
    <div class="footer-bottom">
      <div class="container">
        <p class="copyright">
          &copy; <?= date('Y') ?> <a href="<?= siteEscape(pageUrl('index')) ?>">Biver Royalty Homes</a>. All Rights Reserved | Designed by <a href="#">ERIBS Tech</a>
        </p>
        <div class="footer-legal-row">
          <a href="<?= siteEscape(pageUrl('privacy')) ?>">Privacy</a>
          <a href="<?= siteEscape(pageUrl('terms')) ?>">Terms</a>
          <a href="<?= siteEscape(pageUrl('cookie-policy')) ?>">Cookies</a>
          <button type="button" class="seo-cookie-link" onclick="window.BiverBanners && window.BiverBanners.reopenCookieSettings()">Cookie settings</button>
        </div>
      </div>
    </div>
  </footer>
<?php require __DIR__ . '/site-end.php'; ?>
