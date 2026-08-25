<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/BlogRepository.php';
require_once __DIR__ . '/includes/site_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    BlogRepository::ensureSchema();
    $category = trim((string) ($_GET['category'] ?? ''));
    $search = trim((string) ($_GET['q'] ?? ''));
    $posts = BlogRepository::getPublic($category !== '' ? $category : null, $search !== '' ? $search : null);
} catch (Throwable $e) {
    error_log('Public blog load failed: ' . $e->getMessage());
    $posts = [];
    $category = '';
    $search = '';
}

$categories = BlogRepository::CATEGORIES;
$featured = $posts[0] ?? null;
$listPosts = $featured ? array_slice($posts, 1) : $posts;

function blogCoverSrc(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return './assets/images/biver-logo.png';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return './' . ltrim(preg_replace('#^\./#', '', $path) ?? $path, '/');
}

function blogExcerpt(string $excerpt, string $content, int $len = 160): string
{
    if (trim($excerpt) !== '') {
        return $excerpt;
    }
    $plain = trim($content);
    if (function_exists('mb_substr')) {
        return mb_strlen($plain) > $len ? mb_substr($plain, 0, $len) . '…' : $plain;
    }

    return strlen($plain) > $len ? substr($plain, 0, $len) . '…' : $plain;
}

function blogDateLabel(string $date): string
{
    if ($date === '') {
        return '';
    }
    $ts = strtotime($date);

    return $ts ? date('j M Y', $ts) : $date;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
require_once __DIR__ . '/includes/SeoService.php';
SeoService::renderHead([
    'title' => 'Owerri Property Blog | Biver Royalty Homes Ltd',
    'description' => 'Guides on buying, selling, and investing in Owerri and Imo State property from Biver Royalty Homes Ltd, your local real estate agency.',
    'keywords' => 'Owerri property blog, Imo housing market, buy house Owerri tips, Biver Royalty Homes Ltd',
    'page' => 'blog',
    'query' => array_filter(['category' => $category ?: null, 'q' => $search ?: null]),
    'stylesheets' => ['./assets/css/blog.css'],
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => pageUrl('index')],
        ['name' => 'Blog'],
    ],
]);
?>
</head>
<body class="page-blog">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <section class="blog-hero" aria-labelledby="blogHeroTitle">
    <div class="blog-hero-bg" aria-hidden="true"></div>
    <div class="container blog-hero-inner">
      <p class="blog-brand">Biver Royalty Homes</p>
      <h1 id="blogHeroTitle">Stories from the <em>estate</em></h1>
      <p class="blog-hero-lead">Market insight, buying guidance, and practical advice for families and investors across Nigeria.</p>
      <form class="blog-search" method="get" action="<?= pageHref('blog') ?>" role="search">
        <?php if ($category !== ''): ?>
          <input type="hidden" name="category" value="<?= siteEscape($category) ?>">
        <?php endif; ?>
        <ion-icon name="search-outline" aria-hidden="true"></ion-icon>
        <input type="search" name="q" value="<?= siteEscape($search) ?>" placeholder="Search articles…" aria-label="Search blog">
        <button type="submit">Search</button>
      </form>
    </div>
  </section>

  <section class="blog-filters">
    <div class="container">
      <div class="blog-chips" role="list">
        <a role="listitem" class="blog-chip<?= $category === '' ? ' is-active' : '' ?>" href="<?= pageHref('blog', $search !== '' ? ['q' => $search] : []) ?>">All</a>
        <?php foreach ($categories as $key => $label): ?>
          <a role="listitem" class="blog-chip<?= $category === $key ? ' is-active' : '' ?>" href="<?= pageHref('blog', array_filter(['category' => $key, 'q' => $search !== '' ? $search : null])) ?>"><?= siteEscape($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="blog-listing">
    <div class="container">
      <?php if (!$posts): ?>
        <div class="blog-empty">
          <h2>No articles found</h2>
          <p>Try another category or search term, or check back soon for new insights.</p>
          <a class="blog-empty-link" href="<?= pageHref('blog') ?>">View all posts</a>
        </div>
      <?php else: ?>
        <?php if ($featured && $category === '' && $search === ''): ?>
          <a class="blog-featured" href="<?= pageHref('blog-post', ['slug' => $featured['slug']]) ?>">
            <div class="blog-featured-media">
              <img src="<?= siteEscape(blogCoverSrc((string) $featured['coverImage'])) ?>" alt="" loading="eager">
            </div>
            <div class="blog-featured-copy">
              <span class="blog-tag"><?= siteEscape((string) $featured['categoryLabel']) ?></span>
              <h2><?= siteEscape((string) $featured['title']) ?></h2>
              <p><?= siteEscape(blogExcerpt((string) $featured['excerpt'], (string) $featured['content'], 160)) ?></p>
              <div class="blog-meta">
                <span><?= siteEscape((string) $featured['authorName']) ?></span>
                <span><?= siteEscape(blogDateLabel((string) ($featured['publishedAt'] ?: $featured['createdAt']))) ?></span>
              </div>
            </div>
          </a>
        <?php endif; ?>

        <div class="blog-grid">
          <?php foreach (($featured && $category === '' && $search === '' ? $listPosts : $posts) as $post): ?>
            <article class="blog-card">
              <a class="blog-card-media" href="<?= pageHref('blog-post', ['slug' => $post['slug']]) ?>">
                <img src="<?= siteEscape(blogCoverSrc((string) $post['coverImage'])) ?>" alt="" loading="lazy">
              </a>
              <div class="blog-card-body">
                <span class="blog-tag"><?= siteEscape((string) $post['categoryLabel']) ?></span>
                <h3><a href="<?= pageHref('blog-post', ['slug' => $post['slug']]) ?>"><?= siteEscape((string) $post['title']) ?></a></h3>
                <p><?= siteEscape(blogExcerpt((string) $post['excerpt'], (string) $post['content'], 120)) ?></p>
                <div class="blog-meta">
                  <span><?= siteEscape(blogDateLabel((string) ($post['publishedAt'] ?: $post['createdAt']))) ?></span>
                  <a href="<?= pageHref('blog-post', ['slug' => $post['slug']]) ?>">Read</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
