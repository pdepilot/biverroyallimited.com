<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/SeoService.php';

$crumbs = [
    ['name' => 'Home', 'url' => pageUrl('index')],
    ['name' => 'Privacy Policy'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'Privacy Policy | Biver Royalty Homes',
    'description' => 'How Biver Royalty Homes collects, uses, and protects personal data under Nigeria’s NDPA, with GDPR and CCPA-style rights for visitors.',
    'keywords' => 'privacy policy, NDPA, GDPR, CCPA, Biver Royalty Homes data protection',
    'page' => 'privacy',
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
      <h1>Privacy Policy</h1>
      <p class="terms-lead">We explain what data we collect, why we collect it, how long we keep it, and the choices you have — including advertising cookies used for Google AdSense.</p>
      <p class="terms-updated">Last updated: 13 August 2026</p>
    </div>
  </section>

  <section class="terms-body">
    <div class="container terms-layout">
      <aside class="terms-toc" aria-label="On this page">
        <p class="terms-toc-title">Contents</p>
        <ol>
          <li><a href="#who">Who we are</a></li>
          <li><a href="#data">Information we collect</a></li>
          <li><a href="#use">How we use data</a></li>
          <li><a href="#ads">Advertising &amp; AdSense</a></li>
          <li><a href="#cookies">Cookies</a></li>
          <li><a href="#sharing">Sharing &amp; processors</a></li>
          <li><a href="#rights">Your rights</a></li>
          <li><a href="#security">Security &amp; retention</a></li>
          <li><a href="#children">Children</a></li>
          <li><a href="#contact">Contact</a></li>
        </ol>
      </aside>

      <div class="terms-content">
        <p class="terms-intro">Biver Royalty Homes Ltd (“we”, “us”) operates biverroyaltyhomesltd.com and related property services from Owerri, Imo State, Nigeria. This policy is written for visitors, buyers, renters, and property owners who use our website or contact forms. It is designed to meet the Nigeria Data Protection Act 2023 (NDPA) and to give comparable transparency expected under the EU/UK GDPR and California CCPA/CPRA for international visitors.</p>

        <article class="terms-section" id="who">
          <h2>1. Who we are</h2>
          <div class="terms-section-body">
            We are a Nigerian real estate company helping clients buy, rent, sell, and manage residential and commercial property. Our office is at No. 31 Wetheral Road, Angelina Plaza, opposite Reem Fuel Station, Owerri, Imo State. You can reach our privacy contact at <?= siteEscape(siteContactEmail()) ?> or <?= siteEscape(siteContactPhone()) ?>. If we appoint a Data Protection Officer or NDPC-registered DPO in future, their details will be added here.
          </div>
        </article>

        <article class="terms-section" id="data">
          <h2>2. Information we collect</h2>
          <div class="terms-section-body">
            <p>We collect only what we need to run a professional estate agency online:</p>
            <p><strong>Identity and contact data</strong> you submit on forms: name, email, phone number, preferred inquiry type, property address when listing, and the message you write. If you list a property we may also store ownership notes you choose to share for verification.</p>
            <p><strong>Transaction and service data</strong> when you engage us to view, buy, rent, or list a home: viewing schedules, budget ranges, and documents you send for due diligence. Title documents and identification copies are used solely to complete a legitimate real-estate instruction and are not published on the public website.</p>
            <p><strong>Technical data</strong> such as IP address, browser type, device, approximate location derived from IP, referring URL, and pages visited. Site-visit analytics run only after you accept analytics cookies (or equivalent consent).</p>
            <p><strong>Communications</strong> if you email, call, or chat with us, including chatbot transcripts needed to answer your question and improve service quality.</p>
            We do not intentionally collect special-category data (health, religion, biometrics) and ask you not to include such details in public forms.
          </div>
        </article>

        <article class="terms-section" id="use">
          <h2>3. How we use your data</h2>
          <div class="terms-section-body">
            We use personal data to: respond to property enquiries; arrange inspections; market listings you asked us to publish; send a confirmation or auto-reply email after a contact submission; prevent spam and abuse; keep statutory records; and improve the website. Lawful bases under NDPA/GDPR typically include: performance of a request you initiate (enquiry or listing), legitimate interests in running a safe real-estate business, consent for non-essential cookies and marketing emails, and legal obligation where Nigerian law requires record keeping (for example anti-fraud checks on a sale).
            <p>We do not sell personal information for money. If a California resident requests a “Do Not Sell or Share” confirmation, we will confirm that we do not sell personal information as defined by the CCPA and will honour browser opt-out signals where technically feasible.</p>
          </div>
        </article>

        <article class="terms-section" id="ads">
          <h2>4. Advertising and Google AdSense</h2>
          <div class="terms-section-body">
            This website uses Google AdSense to display third-party advertisements. Google and its partners may use cookies or similar identifiers to serve ads based on your prior visits to this or other websites, measure ad performance, and limit how often you see the same advert. Personalized ads are enabled only when you accept marketing cookies in our banner. You can choose Essential Only to keep the site working without advertising storage, or visit <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener noreferrer">Google Ads Settings</a> and <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer">aboutads.info</a> to opt out of interest-based ads more broadly. Google’s use of advertising cookies is also described in the <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener noreferrer">Google Advertising Privacy Notice</a>.
            <p>AdSense verification requires Google’s adsbygoogle script in the page head. That script may load even before you choose cookies; personalized ad storage remains off until marketing consent is granted (Google Consent Mode v2).</p>
          </div>
        </article>

        <article class="terms-section" id="cookies">
          <h2>5. Cookies</h2>
          <div class="terms-section-body">
            Essential cookies or local storage keep the site secure and remember that you closed the consent banner. Analytics cookies (optional) help us understand which property pages are useful. Marketing cookies (optional) allow AdSense personalization and similar promotional measurement. Full details, durations, and how to change your mind are in our <a href="<?= pageHref('cookie-policy') ?>">Cookie Policy</a>. You can reopen preferences anytime via “Cookie settings” in the footer.
          </div>
        </article>

        <article class="terms-section" id="sharing">
          <h2>6. Sharing and processors</h2>
          <div class="terms-section-body">
            We share data only with: our staff and licensed agents handling your file; email delivery providers used to send enquiry confirmations; hosting and security vendors that store this website; Google (AdSense/Analytics) when you have consented or when strictly necessary for the service; and professional advisers (lawyers, surveyors) when a transaction requires it. We do not publish your phone number or email on property cards unless you asked us to list a public contact. If a transfer leaves Nigeria, we use contractual safeguards and choose reputable processors.
          </div>
        </article>

        <article class="terms-section" id="rights">
          <h2>7. Your rights</h2>
          <div class="terms-section-body">
            Subject to NDPA (and GDPR where it applies), you may request: access to the personal data we hold; correction of inaccurate data; deletion where we no longer have a lawful basis; restriction or objection to certain processing; withdrawal of cookie or marketing consent without affecting earlier lawful use; and data portability of information you provided, in a commonly used format. California residents may also request a summary of categories collected and the right to non-discrimination for exercising privacy rights. To use these rights, email <?= siteEscape(siteContactEmail()) ?> with “Privacy request” in the subject. We may need to verify your identity. You may also lodge a complaint with the Nigeria Data Protection Commission (NDPC) or your local supervisory authority.
          </div>
        </article>

        <article class="terms-section" id="security">
          <h2>8. Security and retention</h2>
          <div class="terms-section-body">
            We use HTTPS, access-controlled admin accounts, and least-privilege database access. No online service is perfectly secure; please do not send original title documents through unsecured channels if we have offered a safer alternative. Enquiry records are kept for as long as needed to complete your instruction and for a reasonable period afterwards (typically up to 24 months for unsuccessful leads, longer where a sale or tenancy creates a legal file). Analytics logs are aggregated or deleted according to the tool’s retention settings. You may ask us to delete a contact submission that has not become an active instruction.
          </div>
        </article>

        <article class="terms-section" id="children">
          <h2>9. Children</h2>
          <div class="terms-section-body">
            Our services are intended for adults seeking property in Nigeria. We do not knowingly collect data from children under 18. If you believe a minor has submitted a form, contact us and we will delete the record promptly.
          </div>
        </article>

        <article class="terms-section" id="contact">
          <h2>10. Contact and updates</h2>
          <div class="terms-section-body">
            Questions about this policy: <?= siteEscape(siteContactEmail()) ?>, <?= siteEscape(siteContactPhone()) ?>, or visit us at No. 31 Wetheral Road, Angelina Plaza, Owerri. We may update this page when our tools or the law change. The “Last updated” date at the top is the effective date. Continued use of the site after an update means you have had a chance to review the new terms. Related documents: <a href="<?= pageHref('terms') ?>">Terms &amp; Conditions</a> and <a href="<?= pageHref('cookie-policy') ?>">Cookie Policy</a>.
          </div>
        </article>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/assets/includes/site-footer.php'; ?>
<button id="scrollToTop" type="button" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>
<script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
<?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>
