<?php
/**
 * Shared admin <head> assets. Set $pageTitle before including.
 *
 * @var string $pageTitle
 * @var string $pageStylesheet Optional page-specific stylesheet path
 */
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Admin | Biver Royalty Homes';
?>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-common.css">
<?php if (!empty($pageStylesheet)): ?>
<link rel="stylesheet" href="<?= htmlspecialchars((string) $pageStylesheet, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
