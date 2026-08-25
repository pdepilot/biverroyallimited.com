<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/FaqRepository.php';
require_once __DIR__ . '/includes/site_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    FaqRepository::ensureSchema();
    $faqs = FaqRepository::getPublic();
} catch (Throwable $e) {
    error_log('Public FAQs load failed: ' . $e->getMessage());
    $faqs = [];
}

$categories = [];
foreach ($faqs as $faq) {
    $cat = (string) ($faq['category'] ?? 'general');
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
// Keep filter chip order aligned with admin category list, then any extras
$orderedCategories = [];
foreach (FaqRepository::CATEGORIES as $key => $_label) {
    if (isset($categories[$key])) {
        $orderedCategories[$key] = $categories[$key];
    }
}
foreach ($categories as $key => $count) {
    if (!isset($orderedCategories[$key])) {
        $orderedCategories[$key] = $count;
    }
}
$categories = $orderedCategories;
$categoryLabels = FaqRepository::CATEGORIES;
require_once __DIR__ . '/includes/SeoService.php';
$faqSchema = SeoService::faqPageSchema($faqs);
?>
<!DOCTYPE html>
<html lang="en-NG">
<head>
<?php
SeoService::renderHead([
    'title' => 'Owerri Real Estate FAQs | Biver Royalty Homes Ltd',
    'description' => 'Answers about buying, renting, and listing property with Biver Royalty Homes Ltd in Owerri, Imo State — fees, inspections, titles, and office hours.',
    'keywords' => 'Owerri real estate FAQ, Biver Royalty Homes questions, buy house Owerri help, rent property Imo',
    'page' => 'faqs',
    'stylesheets' => ['./assets/css/faqs.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'FAQs'],
    ],
    'jsonLd' => array_values(array_filter([$faqSchema])),
]);
?>
</head>
<body class="page-faqs">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <section class="faqs-hero" aria-labelledby="faqsHeroTitle">
    <div class="faqs-hero-bg" aria-hidden="true"></div>
    <div class="faqs-hero-glow" aria-hidden="true"></div>
    <div class="container faqs-hero-inner">
      <p class="faqs-brand reveal-up">Biver Royalty Homes</p>
      <h1 id="faqsHeroTitle" class="reveal-up reveal-delay-1">Answers, <em>clarified</em></h1>
      <p class="faqs-hero-lead reveal-up reveal-delay-2">
        Everything you need to know about buying, renting, and listing with Nigeria’s trusted luxury estate partner.
      </p>
      <div class="faqs-search reveal-up reveal-delay-3">
        <ion-icon name="search-outline" aria-hidden="true"></ion-icon>
        <input type="search" id="faqSearch" placeholder="Search questions…" autocomplete="off" aria-label="Search FAQs">
        <button type="button" class="faqs-search-clear" id="faqSearchClear" hidden aria-label="Clear search">
          <ion-icon name="close-outline"></ion-icon>
        </button>
      </div>
    </div>
  </section>

  <section class="faqs-body" aria-label="Frequently asked questions">
    <div class="container">
      <?php if ($faqs === []): ?>
        <div class="faqs-empty">
          <ion-icon name="help-circle-outline"></ion-icon>
          <h2>FAQs coming soon</h2>
          <p>Our team is preparing helpful answers. Meanwhile, reach out and we’ll guide you personally.</p>
          <a href="<?= pageHref('contact') ?>" class="faqs-cta-btn">Contact Us</a>
        </div>
      <?php else: ?>
        <div class="faqs-toolbar">
          <div class="faqs-cats" role="tablist" aria-label="Filter by category">
            <button type="button" class="faqs-cat is-active" data-cat="all" role="tab" aria-selected="true">
              All <span><?= count($faqs) ?></span>
            </button>
            <?php foreach ($categories as $catKey => $count): ?>
              <?php $label = $categoryLabels[$catKey] ?? FaqRepository::categoryLabel($catKey); ?>
              <button type="button" class="faqs-cat" data-cat="<?= siteEscape($catKey) ?>" role="tab" aria-selected="false">
                <?= siteEscape($label) ?> <span><?= (int) $count ?></span>
              </button>
            <?php endforeach; ?>
          </div>
          <p class="faqs-count" id="faqVisibleCount" aria-live="polite"><?= count($faqs) ?> questions</p>
        </div>

        <div class="faqs-list" id="faqList">
          <?php foreach ($faqs as $index => $faq): ?>
            <?php
              $cat = (string) ($faq['category'] ?? 'general');
              $label = (string) ($faq['categoryLabel'] ?? FaqRepository::categoryLabel($cat));
              $qid = 'faq-q-' . (int) $faq['id'];
              $aid = 'faq-a-' . (int) $faq['id'];
            ?>
            <article
              class="faq-item reveal-up"
              style="--i: <?= (int) ($index % 8) ?>"
              data-cat="<?= siteEscape($cat) ?>"
              data-search="<?= siteEscape(mb_strtolower((string) $faq['question'] . ' ' . (string) $faq['answer'] . ' ' . $cat . ' ' . implode(' ', $faq['keywords'] ?? []))) ?>"
            >
              <button
                type="button"
                class="faq-trigger"
                id="<?= siteEscape($qid) ?>"
                aria-expanded="false"
                aria-controls="<?= siteEscape($aid) ?>"
              >
                <span class="faq-cat-pill"><?= siteEscape($label) ?></span>
                <span class="faq-question-text"><?= siteEscape((string) $faq['question']) ?></span>
                <span class="faq-icon" aria-hidden="true">
                  <ion-icon name="add-outline" class="icon-plus"></ion-icon>
                  <ion-icon name="remove-outline" class="icon-minus"></ion-icon>
                </span>
              </button>
              <div class="faq-panel" id="<?= siteEscape($aid) ?>" role="region" aria-labelledby="<?= siteEscape($qid) ?>" hidden>
                <div class="faq-answer"><?= nl2br(siteEscape((string) $faq['answer'])) ?></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="faqs-no-match" id="faqNoMatch" hidden>
          <ion-icon name="search-outline"></ion-icon>
          <p>No questions match your search. Try another phrase or contact our team.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="faqs-cta" aria-labelledby="faqsCtaTitle">
    <div class="container faqs-cta-inner">
      <p class="faqs-cta-eyebrow">Still curious?</p>
      <h2 id="faqsCtaTitle">Speak with our estate advisors</h2>
      <p>If your question isn’t listed, we’re one message away — ready to help you buy, rent, or list with confidence.</p>
      <div class="faqs-cta-actions">
        <a href="<?= pageHref('contact') ?>" class="faqs-cta-btn">Get in Touch</a>
        <a href="tel:<?= siteEscape(siteContactPhoneTel()) ?>" class="faqs-cta-btn faqs-cta-btn--ghost">
          <ion-icon name="call-outline"></ion-icon>
          <?= siteEscape(siteContactPhone()) ?>
        </a>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/assets/includes/site-footer.php'; ?>

<button id="scrollToTop" type="button" aria-label="Scroll to top"><ion-icon name="chevron-up-outline"></ion-icon></button>

<script src="./assets/js/site-header.js" defer></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<script>
(function () {
  'use strict';

  const list = document.getElementById('faqList');
  if (!list) return;

  const items = Array.from(list.querySelectorAll('.faq-item'));
  const cats = document.querySelectorAll('.faqs-cat');
  const search = document.getElementById('faqSearch');
  const clearBtn = document.getElementById('faqSearchClear');
  const countEl = document.getElementById('faqVisibleCount');
  const noMatch = document.getElementById('faqNoMatch');
  let activeCat = 'all';

  function setExpanded(item, open) {
    const trigger = item.querySelector('.faq-trigger');
    const panel = item.querySelector('.faq-panel');
    if (!trigger || !panel) return;
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    item.classList.toggle('is-open', open);
    if (open) {
      panel.hidden = false;
      panel.style.maxHeight = panel.scrollHeight + 'px';
    } else {
      panel.style.maxHeight = '0px';
      window.setTimeout(() => { if (!item.classList.contains('is-open')) panel.hidden = true; }, 320);
    }
  }

  items.forEach((item) => {
    const trigger = item.querySelector('.faq-trigger');
    trigger?.addEventListener('click', () => {
      const isOpen = item.classList.contains('is-open');
      items.forEach((other) => { if (other !== item) setExpanded(other, false); });
      setExpanded(item, !isOpen);
    });
  });

  function applyFilters() {
    const q = (search?.value || '').trim().toLowerCase();
    if (clearBtn) clearBtn.hidden = q === '';
    let visible = 0;

    items.forEach((item) => {
      const catOk = activeCat === 'all' || item.dataset.cat === activeCat;
      const text = item.dataset.search || '';
      const searchOk = q === '' || text.includes(q);
      const show = catOk && searchOk;
      item.hidden = !show;
      if (!show && item.classList.contains('is-open')) setExpanded(item, false);
      if (show) visible += 1;
    });

    if (countEl) {
      countEl.textContent = visible + (visible === 1 ? ' question' : ' questions');
    }
    if (noMatch) noMatch.hidden = visible > 0;
  }

  cats.forEach((btn) => {
    btn.addEventListener('click', () => {
      activeCat = btn.dataset.cat || 'all';
      cats.forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      applyFilters();
    });
  });

  search?.addEventListener('input', applyFilters);
  clearBtn?.addEventListener('click', () => {
    if (search) search.value = '';
    applyFilters();
    search?.focus();
  });

  // Subtle entrance
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduce) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal-up').forEach((el) => io.observe(el));
  } else {
    document.querySelectorAll('.reveal-up').forEach((el) => el.classList.add('is-visible'));
  }
})();
</script>
<?php require __DIR__ . '/assets/includes/whatsapp-float.php'; ?>
<?php require __DIR__ . '/chatbot/chatbot.php'; ?>
</body>
</html>
