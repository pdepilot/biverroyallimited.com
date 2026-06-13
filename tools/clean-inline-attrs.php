<?php
/**
 * Replace static inline style="" attributes with CSS classes across PHP files.
 */
declare(strict_types=1);

$root = dirname(__DIR__);

$replacements = [
    '<em style="color:var(--gold-light);font-style:italic;">' => '<em>',
    'class="sidebar-logo" style="text-decoration:none;color:inherit;"' => 'class="sidebar-logo"',
    ' style="margin-left:280px;"' => '',
    '<div class="main" >' => '<div class="main">',
    '<div style="padding: 32px 28px;">' => '<div class="admin-content-pad">',
    '<div style="padding: 28px;">' => '<div class="admin-content-pad--sm">',
    '<div style="display: flex; gap: 12px; align-items: center;">' => '<div class="admin-header-actions">',
    '<div style="display: flex; align-items: center; gap: 16px;">' => '<div class="admin-header-actions--lg">',
    '<p id="mailStatusText" style="margin-bottom:1rem;font-size:0.85rem;color:var(--text-muted);">' => '<p id="mailStatusText" class="admin-mail-status">',
    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">' => '<div class="admin-form-grid-2">',
    '<small id="mailPasswordHint" style="display:block;margin-top:6px;color:var(--text-muted);"></small>' => '<small id="mailPasswordHint" class="admin-hint-block"></small>',
    '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:8px;">' => '<div class="admin-btn-row">',
    '<div class="form-group" style="margin-top:14px;">' => '<div class="form-group admin-form-group-spaced">',
    '<p style="margin-top:12px;font-size:0.78rem;color:var(--text-muted);">' => '<p class="admin-muted-note">',
    '<p style="margin-bottom: 1rem; font-size: 0.85rem;">' => '<p class="admin-danger-zone">',
    '<button id="clearCacheBtn" class="btn-outline" style="margin-right: 1rem;">' => '<button id="clearCacheBtn" class="btn-outline admin-btn-spaced">',
    '<i class="fas fa-crown" style="font-size: 1.8rem;"></i>' => '<i class="fas fa-crown admin-page-title-icon"></i>',
    '<i class="fas fa-search" style="color: var(--gold);"></i>' => '<i class="fas fa-search admin-search-icon"></i>',
    '<h4 style="font-family:var(--ff-display);margin:16px 0 8px;">' => '<h4 class="admin-reply-heading">',
    '<ion-icon name="mail-open-outline" style="font-size:48px;"></ion-icon>' => '<ion-icon name="mail-open-outline" class="admin-empty-icon"></ion-icon>',
    '<span id="updatedAt" style="font-size:0.82rem;color:var(--text-muted);"></span>' => '<span id="updatedAt" class="admin-updated-at"></span>',
    '<hr style="margin:28px 0;border:none;border-top:1px solid var(--border-light);">' => '<hr class="admin-divider">',
    '<h2 style="margin-bottom:8px;">' => '<h2 class="admin-section-title">',
    '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">' => '<div class="admin-property-grid-form">',
    '<div class="content-area" style="padding:24px 32px;">' => '<div class="content-area live-content-area">',
    '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">' => '<div class="live-toolbar">',
    '<p style="color:#6c5e4e;margin:0;">' => '<p class="live-intro">',
    '<div class="live-grid" style="margin-bottom:28px;">' => '<div class="live-grid live-grid-spaced">',
    'placeholder="Search…" style="padding:6px 10px;border:1px solid #e9e5dc;border-radius:8px;">' => 'placeholder="Search…" class="conv-search">',
    '<p style="padding:20px;color:#6c5e4e;">' => '<p class="live-placeholder">',
    '<p style="color:#6c5e4e;">' => '<p class="live-msg-hint">',
    '<div style="overflow-x:auto;">' => '<div class="live-table-wrap">',
    '<td colspan="7" style="padding:20px;">' => '<td colspan="7" class="live-loading-cell">',
    '<small style="color:#6c5e4e;">' => '<small class="live-email-hint">',
    '<div style="font-size:10px;color:#6c5e4e;margin-top:4px;">' => '<div class="live-msg-time">',
    '<div class="container" style="padding: 0;">' => '<div class="container">',
    '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-top: 40px;">' => '<div class="properties-toolbar">',
    '<div id="propertiesGrid" class="properties-grid" style="display: none;"></div>' => '<div id="propertiesGrid" class="properties-grid u-hidden"></div>',
    '<div id="errorState" class="error-state" style="display: none;">' => '<div id="errorState" class="error-state u-hidden">',
    '<p style="margin-top: 10px; font-size: 12px;">' => '<p class="info-card-hours">',
    '<h2 style="color: var(--white);">' => '<h2>',
    '<p style="color: rgba(255,255,255,0.68);">' => '<p>',
    ' style="display:none;"' => ' class="u-hidden"',
    ' style="margin-top:20px;"' => ' class="u-mt-20"',
    'class="bg-slide active" style="background-image:url(\'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1920&auto=format&fit=crop&q=80\');"' => 'class="bg-slide active bg-slide--1"',
    'class="properties-error" style="display:none;"' => 'class="properties-error u-hidden"',
    'class="properties-empty" style="display:none;"' => 'class="properties-empty u-hidden"',
    'class="property-list has-scrollbar" style="display:none;"' => 'class="property-list has-scrollbar u-hidden"',
    'class="testimonials-error" style="display:none;"' => 'class="testimonials-error u-hidden"',
    'class="testimonials-empty" style="display:none;"' => 'class="testimonials-empty u-hidden"',
    'class="testimonial-slider" style="display:none;"' => 'class="testimonial-slider u-hidden"',
    'style="--text-align:left; margin-inline:0;"' => 'class="section-subtitle--left"',
    'style="text-align:left; margin-bottom:16px;"' => 'class="section-title--left"',
    '<ion-icon name="home-outline" style="font-size: 52px; color: var(--gold);"></ion-icon>' => '<ion-icon name="home-outline"></ion-icon>',
    '<ion-icon name="key-outline" style="font-size: 52px; color: var(--gold);"></ion-icon>' => '<ion-icon name="key-outline"></ion-icon>',
    '<ion-icon name="construct-outline" style="font-size: 52px; color: var(--gold);"></ion-icon>' => '<ion-icon name="construct-outline"></ion-icon>',
];

$publicPages = [
    'index.php', 'about.php', 'services.php', 'contact.php',
    'property.php', 'property-detail.php', 'list-your-property.php',
    'assets/includes/site-chrome.php',
];

$adminPages = glob($root . '/admin/*.php') ?: [];

$files = array_merge(
    array_map(fn ($p) => $root . '/' . $p, $publicPages),
    $adminPages,
    [$root . '/includes/admin_sidebar.php']
);

$utilitiesLink = '  <link rel="stylesheet" href="./assets/css/site-utilities.css">' . "\n";

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $original = $content;

    foreach ($replacements as $from => $to) {
        $content = str_replace($from, $to, $content);
    }

    // JS template replacements (admin)
    $content = preg_replace(
        '/<div style="grid-column:1\/-1;\s*text-align:center;\s*padding:3rem;">/',
        '<div class="admin-empty-wide">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div class="stat-number" style="font-size:1\.4rem;">/',
        '<div class="stat-number admin-stat-number--sm">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div style="display:flex;flex-wrap:wrap;gap:12px;">/',
        '<div class="admin-quick-actions">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div class="meta" style="margin-top:6px;">/',
        '<div class="meta admin-meta-spaced">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<p style="color:var\(--text-muted\);">/',
        '<p class="admin-text-muted">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<small style="color:#dc3545;">/',
        '<small class="admin-hidden-badge">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/controls style="width:100%;max-height:180px;border-radius:12px;background:#000;margin-top:8px;"/',
        'controls class="admin-video-preview admin-video-preview--spaced"',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/controls style="width:100%;max-height:180px;border-radius:12px;background:#000;"/',
        'controls class="admin-video-preview"',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/class="media-upload-row" style="margin-top:0;"/',
        'class="media-upload-row admin-video-preview--flat"',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<span style="color:var\(--danger\);font-size:0\.85rem;">/',
        '<span class="admin-remove-video-note">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div class="thumb" style="background-image:url\(\'\$\{mediaUrl\(images\[0\] \|\| item\.imageUrl\)\}\'\)"/',
        '<div class="thumb admin-card-thumb" style="--card-img:url(\'${mediaUrl(images[0] || item.imageUrl)}\')"',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div class="card-img" style="background-image: url\(\'\$\{escapeHtml\(prop\.imageUrl/',
        '<div class="card-img admin-card-img" style="--card-img:url(\'${escapeHtml(prop.imageUrl',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div style="font-size:0\.8rem; opacity:0\.8;">/',
        '<div class="admin-property-meta">',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/class="btn" style="margin-top:20px;"/',
        'class="btn u-mt-20"',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/class="gallery-side-item" style="background:linear-gradient\(135deg,var\(--prussian-blue\),#2a1204\);display:grid;place-items:center;color:rgba\(255,255,255,0\.5\);font-size:0\.9rem;"/',
        'class="gallery-side-item gallery-side-placeholder"',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<ion-icon name="videocam-outline" style="font-size:42px;display:block;margin:0 auto 10px;color:var\(--gold\);"><\/ion-icon>/',
        '<ion-icon name="videocam-outline"></ion-icon>',
        $content
    ) ?? $content;
    $content = preg_replace(
        '/<div class="chat-compose" id="chatCompose" style="display:none;">/',
        '<div class="chat-compose u-hidden" id="chatCompose">',
        $content
    ) ?? $content;

    // Add site-utilities.css to public pages
    if (in_array(basename($file), ['index.php', 'about.php', 'services.php', 'contact.php', 'property.php', 'property-detail.php', 'list-your-property.php'], true)) {
        if (strpos($content, 'site-utilities.css') === false && strpos($content, 'site-variables.css') !== false) {
            $content = str_replace(
                '<link rel="stylesheet" href="./assets/css/site-variables.css">',
                '<link rel="stylesheet" href="./assets/css/site-variables.css">' . "\n" . $utilitiesLink,
                $content
            );
        }
        $content = str_replace(
            "  <?php require_once __DIR__ . '/includes/site_paths.php'; ?>\n<link rel=\"stylesheet\" href=\"./assets/css/site-header.css\">",
            "  <?php require_once __DIR__ . '/includes/site_paths.php'; ?>\n  <link rel=\"stylesheet\" href=\"./assets/css/site-header.css\">",
            $content
        );
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo 'Updated: ' . str_replace($root . DIRECTORY_SEPARATOR, '', $file) . PHP_EOL;
    }
}

echo "Done.\n";
