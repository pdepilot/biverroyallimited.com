<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/site_paths.php';
?>
<section class="newsletter-strip" aria-labelledby="newsletterHeading">
  <div class="container newsletter-strip-inner">
    <div class="newsletter-copy">
      <p class="newsletter-eyebrow">Newsletter</p>
      <h2 id="newsletterHeading">Market updates in your inbox</h2>
      <p>New listings, buying tips, and Owerri property news from Biver Royalty Homes. Unsubscribe anytime.</p>
    </div>
    <form class="newsletter-form" data-newsletter-form novalidate>
      <label class="u-hidden" for="newsletterEmail">Email address</label>
      <input id="newsletterEmail" type="email" name="email" required autocomplete="email" placeholder="you@email.com" aria-label="Email address">
      <button type="submit">Subscribe</button>
      <p class="newsletter-status" data-newsletter-status role="status" aria-live="polite"></p>
    </form>
  </div>
</section>
