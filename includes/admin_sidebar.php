<?php
/**
 * Shared admin sidebar — same markup/classes as admin-dashboard.php
 *
 * @var string $activeNav dashboard|properties|analytics|testimonials|contacts|listings|email|smtp|subscribers|promo|locations|settings|admins|chatbot
 */
declare(strict_types=1);

require_once __DIR__ . '/AdminPermissions.php';

$activeNav = $activeNav ?? '';

$navItems = [
    'dashboard'    => ['href' => 'admin-dashboard.php', 'icon' => 'speedometer-outline', 'label' => 'Overview'],
    'properties'   => ['href' => 'admin-property.php',  'icon' => 'home-outline',        'label' => 'Properties'],
    'analytics'    => ['href' => 'admin-analytics.php', 'icon' => 'people-outline',      'label' => 'Analytics'],
    'testimonials' => ['href' => 'admin-testimonial.php','icon' => 'chatbubbles-outline','label' => 'Testimonials'],
    'locations'    => ['href' => 'admin-locations.php', 'icon' => 'map-outline',         'label' => 'Service Areas'],
    'listings'     => ['href' => 'admin-list-your-property.php', 'icon' => 'clipboard-outline', 'label' => 'List Submissions'],
    'contacts'     => ['href' => 'admin-contact.php',   'icon' => 'mail-outline',        'label' => 'Inquiries'],
    'email'        => ['href' => 'admin-email-center.php', 'icon' => 'paper-plane-outline', 'label' => 'Email Center'],
    'smtp'         => ['href' => 'admin-smtp-settings.php', 'icon' => 'server-outline', 'label' => 'SMTP Settings'],
    'subscribers'  => ['href' => 'admin-subscribers.php', 'icon' => 'people-circle-outline', 'label' => 'Subscribers'],
    'chatbot'      => ['href' => 'admin-live-chat.php', 'icon' => 'chatbox-ellipses-outline', 'label' => 'Live Chat & Leads'],
    'promo'        => ['href' => 'admin-promo-banner.php', 'icon' => 'megaphone-outline', 'label' => 'Promo Banner'],
    'settings'     => ['href' => 'admin-setting.php',   'icon' => 'settings-outline',    'label' => 'Settings'],
    'admins'       => ['href' => 'admin-users.php',   'icon' => 'shield-checkmark-outline', 'label' => 'Admin Users'],
];

function adminNavClass(string $page, string $active): string
{
    return $page === $active ? 'nav-link active' : 'nav-link';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="admin-dashboard.php" class="sidebar-logo">
      <img src="../assets/images/biver-logo.png" alt="Biver Royalty Homes" onerror="this.src='https://placehold.co/48x48?text=BR'">
      <span>Biver Royalty</span>
    </a>
  </div>
  <nav class="sidebar-nav" aria-label="Admin navigation">
    <?php foreach ($navItems as $key => $item): ?>
    <?php if (!AdminPermissions::canAccessNav($key)) { continue; } ?>
    <div class="nav-item">
      <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
         class="<?= adminNavClass($key, $activeNav) ?>">
        <ion-icon name="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></ion-icon>
        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    </div>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-btn" id="logoutBtn">
      <ion-icon name="log-out-outline"></ion-icon> Logout
    </a>
  </div>
</aside>
<script src="../assets/js/admin-common.js" defer></script>
