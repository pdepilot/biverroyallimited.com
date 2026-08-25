<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/SeoService.php';

$crumbs = [
    ['name' => 'Home', 'url' => pageUrl('index')],
    ['name' => 'Cookie Policy'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'Cookie Policy | Biver Royalty Homes',
    'description' => 'Learn which cookies Biver Royalty Homes uses, why we use them, and how to accept, refuse, or change analytics and advertising consent.',
    'keywords' => 'cookie policy, cookie consent, AdSense cookies, analytics cookies Nigeria',
    'page' => 'cookie-policy',
    'stylesheets' => ['./assets/css/terms.css'],
    'breadcrumbs' => $crumbs,
]);
?>
</head>
<body class="page-terms">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <section class="terms-hero">
    <div class="terms-hero-bg" aria-hidden="true"></div>
    <div class="container terms-hero-inner">
      <?php SeoService::breadcrumbs($crumbs); ?>
      <p class="terms-brand">Biver Royalty Homes</p>
      <p class="terms-eyebrow">Legal</p>
      <h1>Cookie Policy</h1>
      <p class="terms-lead">This page explains cookies and similar storage on our website, including Google AdSense and analytics, and how your banner choices work.</p>
      <p class="terms-updated">Last updated: 13 August 2026</p>
    </div>
  </section>

  <section class="terms-body">
    <div class="container terms-layout">
      <aside class="terms-toc" aria-label="On this page">
        <p class="terms-toc-title">Contents</p>
        <ol>
          <li><a href="#what">What cookies are</a></li>
          <li><a href="#types">Types we use</a></li>
          <li><a href="#table">Cookie list</a></li>
          <li><a href="#adsense">Google AdSense</a></li>
          <li><a href="#choices">Your choices</a></li>
          <li><a href="#browser">Browser controls</a></li>
          <li><a href="#updates">Updates</a></li>
        </ol>
      </aside>

      <div class="terms-content">
        <p class="terms-intro">When you visit Biver Royalty Homes we may store small text files (“cookies”) or use browser local storage. Some are essential. Others help us understand traffic or show relevant ads. We ask for your permission before non-essential cookies, in line with the Nigeria Data Protection Act 2023 and international advertising rules that Google AdSense requires publishers to follow.</p>

        <article class="terms-section" id="what">
          <h2>1. What cookies are</h2>
          <div class="terms-section-body">
            A cookie is a small file placed on your device by a website. First-party cookies are set by biverroyaltyhomesltd.com. Third-party cookies are set by partners such as Google. We also use localStorage for consent records so we remember your choice on return visits. Session cookies expire when you close the browser. Persistent cookies last for a set period or until you delete them.
          </div>
        </article>

        <article class="terms-section" id="types">
          <h2>2. Types we use</h2>
          <div class="terms-section-body">
            <p><strong>Essential</strong> — required for security, load balancing, form spam resistance, and remembering that you already answered the cookie banner. These do not require opt-in.</p>
            <p><strong>Analytics</strong> — optional. Used only after you accept Analytics. They tell us which property and blog pages are read, approximate geography, and device type so we can improve content. We use Google Analytics 4 when a measurement ID is configured, with IP anonymization and Consent Mode.</p>
            <p><strong>Marketing / advertising</strong> — optional. Used after you accept Marketing. They allow Google AdSense and similar partners to personalize ads, measure conversions, and limit ad frequency. If you choose Essential Only, ads (when live) should be non-personalized or limited according to Google Consent Mode.</p>
          </div>
        </article>

        <article class="terms-section" id="table">
          <h2>3. Cookie and storage list</h2>
          <div class="terms-section-body">
            <p><strong>biver_cookie_consent_v1</strong> (localStorage, first-party, up to 12 months) — stores your Essential / Analytics / Marketing choices and the timestamp. Purpose: essential preference memory.</p>
            <p><strong>biver_promo_dismissed_v1</strong> (sessionStorage, first-party, session) — remembers that you closed the promotional spotlight on this visit. Purpose: essential UX.</p>
            <p><strong>Google Analytics cookies</strong> (e.g. _ga, _ga_*, third-party/first-party depending on configuration, typically up to 24 months) — audience measurement after analytics consent.</p>
            <p><strong>Google AdSense / DoubleClick cookies</strong> (e.g. IDE, ANID, DSID, or other Google advertising cookies, third-party, variable duration) — ad delivery, fraud prevention, and personalization after marketing consent. Google’s current list is published in their advertising privacy documentation and may change.</p>
            <p>Admin login sessions and CSRF-style tokens on staff pages are not used on the public marketing site in the same way and are outside this visitor-facing list.</p>
          </div>
        </article>

        <article class="terms-section" id="adsense">
          <h2>4. Google AdSense specifically</h2>
          <div class="terms-section-body">
            Google, as a third-party vendor, uses cookies to serve ads on this site. Google’s use of advertising cookies enables it and its partners to serve ads based on your visit to this site and/or other sites on the Internet. You may opt out of personalized advertising by visiting <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener noreferrer">Google Ads Settings</a>. Alternatively, you can opt out of some third-party vendors’ uses of cookies for personalized advertising at <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer">www.aboutads.info/choices</a>. Our publisher ID is included in the site head so Google can verify this domain. Empty ad units are not shown until we configure approved slot IDs after AdSense acceptance.
          </div>
        </article>

        <article class="terms-section" id="choices">
          <h2>5. Your choices</h2>
          <div class="terms-section-body">
            On your first visit a banner lets you Accept All, Essential Only, or Manage individual categories. Accept All turns analytics and marketing on. Essential Only keeps only what the site needs. Manage lets you tick Analytics and Marketing separately, then Save Preferences. You can change your mind later using <button type="button" class="seo-cookie-link" id="openCookieSettings">Cookie settings</button> in the footer. Withdrawing consent does not retroactively delete Google’s historical logs held on Google’s systems; it stops new optional storage from this browser profile.
          </div>
        </article>

        <article class="terms-section" id="browser">
          <h2>6. Browser and device controls</h2>
          <div class="terms-section-body">
            Most browsers let you block or delete cookies in Settings. Blocking all cookies may stop parts of this site from working (for example, keeping the banner dismissed). Mobile OS privacy settings and intelligent-tracking prevention in Safari may already limit third-party cookies. Using a private window starts without our stored consent, so the banner will appear again.
          </div>
        </article>

        <article class="terms-section" id="updates">
          <h2>7. Updates and contact</h2>
          <div class="terms-section-body">
            We update this policy when we add tools (for example a new analytics product) or when Google changes AdSense cookie names. The date at the top is authoritative. For privacy questions see our <a href="<?= pageHref('privacy') ?>">Privacy Policy</a> or email <?= siteEscape(siteContactEmail()) ?>. Office: No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State, Nigeria.
          </div>
        </article>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/assets/includes/site-footer.php'; ?>
<button id="scrollToTop" type="button" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>
<script src="./assets/js/site-header.js" defer></script>
<script>
  document.getElementById('openCookieSettings')?.addEventListener('click', function () {
    window.BiverBanners && window.BiverBanners.reopenCookieSettings();
  });
</script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
<?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>
