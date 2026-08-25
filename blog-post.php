<?php
require_once __DIR__ . '/includes/htaccess_redirect.php';
require_once __DIR__ . '/includes/BlogRepository.php';
require_once __DIR__ . '/includes/site_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = null;

try {
    BlogRepository::ensureSchema();
    if ($slug !== '') {
        $post = BlogRepository::getBySlug($slug, true);
        if ($post !== null) {
            BlogRepository::incrementViews((int) $post['id']);
            $post['viewCount'] = (int) $post['viewCount'] + 1;
        }
    }
} catch (Throwable $e) {
    error_log('Blog post load failed: ' . $e->getMessage());
}

if ($post === null) {
    http_response_code(404);
}

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

function blogDateLabel(string $date): string
{
    if ($date === '') {
        return '';
    }
    $ts = strtotime($date);

    return $ts ? date('j F Y', $ts) : $date;
}

require_once __DIR__ . '/includes/SeoService.php';

$pageTitle = $post ? ((string) $post['title'] . ' | Blog') : 'Article not found | Blog';
$pageDesc = $post
    ? ((string) ($post['excerpt'] !== '' ? $post['excerpt'] : (function_exists('mb_substr') ? mb_substr(strip_tags((string) $post['content']), 0, 160) : substr(strip_tags((string) $post['content']), 0, 160))))
    : 'The requested blog article could not be found.';
$cover = $post ? trim((string) ($post['coverImage'] ?? '')) : '';
$crumbs = [
    ['name' => 'Home', 'url' => pageUrl('index')],
    ['name' => 'Blog', 'url' => pageUrl('blog')],
    ['name' => $post ? (string) $post['title'] : 'Article'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php
SeoService::renderHead([
    'title' => SeoService::limit($pageTitle, 60),
    'description' => SeoService::limit($pageDesc, 160),
    'keywords' => 'real estate blog Nigeria, ' . ($post['categoryLabel'] ?? 'Biver Royalty Homes'),
    'page' => 'blog-post',
    'query' => $slug !== '' ? ['slug' => $slug] : [],
    'ogType' => 'article',
    'ogImage' => $cover !== '' ? $cover : null,
    'robots' => $post ? 'index, follow' : 'noindex, follow',
    'stylesheets' => ['./assets/css/blog.css'],
    'breadcrumbs' => $crumbs,
    'article' => $post,
]);
?>
</head>
<body class="page-blog page-blog-post">

<?php require __DIR__ . '/assets/includes/site-chrome.php'; ?>

<main id="main-content">
  <?php if ($post === null): ?>
    <section class="blog-empty-page">
      <div class="container">
        <h1>Article not found</h1>
        <p>This post may have been unpublished or the link is incorrect.</p>
        <a href="<?= pageHref('blog') ?>">Back to blog</a>
      </div>
    </section>
  <?php else: ?>
    <article class="blog-article">
      <header class="blog-article-hero">
        <div class="blog-article-hero-bg" aria-hidden="true"></div>
        <div class="container blog-article-hero-inner">
          <a class="blog-back" href="<?= pageHref('blog') ?>"><ion-icon name="arrow-back-outline"></ion-icon> All posts</a>
          <span class="blog-tag"><?= siteEscape((string) $post['categoryLabel']) ?></span>
          <h1><?= siteEscape((string) $post['title']) ?></h1>
          <div class="blog-meta blog-meta--light">
            <span><?= siteEscape((string) $post['authorName']) ?></span>
            <span><?= siteEscape(blogDateLabel((string) ($post['publishedAt'] ?: $post['createdAt']))) ?></span>
            <span><?= (int) $post['viewCount'] ?> views</span>
          </div>
        </div>
      </header>

      <?php if (trim((string) $post['coverImage']) !== ''): ?>
        <div class="container blog-article-cover">
          <img src="<?= siteEscape(blogCoverSrc((string) $post['coverImage'])) ?>" alt="<?= siteEscape((string) $post['title']) ?>">
        </div>
      <?php endif; ?>

      <div class="container blog-article-body">
        <?php if (trim((string) $post['excerpt']) !== ''): ?>
          <p class="blog-article-dek"><?= siteEscape((string) $post['excerpt']) ?></p>
        <?php endif; ?>
        <div class="blog-article-content">
          <?= nl2br(siteEscape((string) $post['content'])) ?>
        </div>
        <?php SeoService::adSlot('article_end'); ?>
        <?php SeoService::shareButtons(pageUrl('blog-post', ['slug' => $slug]), (string) $post['title']); ?>
        <footer class="blog-article-footer">
          <a class="blog-empty-link" href="<?= pageHref('blog') ?>">More articles</a>
          <a class="blog-empty-link" href="<?= pageHref('contact') ?>">Talk to our team</a>
        </footer>
      </div>
    </article>
  <?php endif; ?>
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
