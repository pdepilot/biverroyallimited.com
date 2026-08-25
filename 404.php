<?php
http_response_code(404);
require_once __DIR__ . '/includes/SeoService.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => 'Page not found | Biver Royalty Homes',
    'description' => 'The page you requested is not available. Return home or browse properties, services, and contact Biver Royalty Homes in Owerri.',
    'robots' => 'noindex, follow',
    'page' => '404',
    'stylesheets' => ['./assets/css/terms.css'],
]);
?>
</head>
<body class="page-terms">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <section class="page-error">
    <div class="container">
      <p class="terms-eyebrow" style="letter-spacing:0.2em;text-transform:uppercase;color:#9e7a2c;font-weight:700;">Error 404</p>
      <h1>We cannot find that page</h1>
      <p>The link may be outdated, the property may have been removed, or the address was typed incorrectly. Use the menu above or one of the paths below to continue exploring Biver Royalty Homes.</p>
      <div class="page-error-actions">
        <a href="<?= pageHref('index') ?>">Go home</a>
        <a class="alt" href="<?= pageHref('property') ?>">Browse properties</a>
        <a class="alt" href="<?= pageHref('contact') ?>">Contact us</a>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/assets/includes/site-footer.php'; ?>
<script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
