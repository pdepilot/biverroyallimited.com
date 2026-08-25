<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_guard.php';
require_once dirname(__DIR__) . '/includes/AdminPermissions.php';
require_once dirname(__DIR__) . '/includes/TransactionRepository.php';
require_once dirname(__DIR__) . '/includes/site_helpers.php';

AdminPermissions::require(AdminPermissions::PERM_TRANSACTIONS);

$id = (int) ($_GET['id'] ?? 0);
$tx = $id > 0 ? TransactionRepository::getById($id) : null;

if ($tx === null) {
    http_response_code(404);
    echo 'Transaction not found.';
    exit;
}

// Stream a downloadable PDF version when requested (?pdf=1).
if (($_GET['pdf'] ?? '') === '1') {
    require_once dirname(__DIR__) . '/includes/TransactionDocument.php';
    require_once dirname(__DIR__) . '/includes/PdfService.php';

    if (!PdfService::available()) {
        http_response_code(500);
        echo 'PDF engine not installed. Run "composer require dompdf/dompdf" and upload the vendor folder.';
        exit;
    }

    $meta = TransactionDocument::pdfMeta($tx, 'certificate');
    $pdf = PdfService::render(TransactionDocument::pdf($tx, 'certificate'), $meta['paper'], $meta['orientation']);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $meta['filename'] . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$title = TransactionRepository::certificateTitle($tx['transactionType']);
$currency = $tx['currency'] ?: 'NGN';
$symbol = $currency === 'NGN' ? '₦' : '';
$amount = (int) $tx['amount'];
$dateDisplay = $tx['transactionDate'] ? date('jS \d\a\y \o\f F, Y', (int) strtotime($tx['transactionDate'])) : date('jS \d\a\y \o\f F, Y');
$logoUrl = publicAssetUrl('assets/images/biver-logo.png') ?? '../assets/images/biver-logo.png';
$signatureUrl = publicAssetUrl('assets/images/signature.png') ?? '../assets/images/signature.png';

$property = $tx['propertyTitle'] !== '' ? $tx['propertyTitle'] : 'the property';
$location = $tx['propertyLocation'];

$body = match ($tx['transactionType']) {
    'purchase' => 'is hereby recognized as the rightful owner of the property described below, having successfully completed its purchase through ' . siteName() . '.',
    'rent'     => 'is hereby recognized as the authorized tenant of the property described below, under a tenancy agreement duly facilitated by ' . siteName() . '.',
    'sale'     => 'has successfully and lawfully transferred ownership of the property described below to ' . siteName() . '.',
    default    => 'has completed a verified property transaction with ' . siteName() . '.',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> — <?= e($tx['reference']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --gold: #C9A227; --gold-light: #E7C868; --gold-dark: #9e7e2c; --ink: #2b2415; --muted: #6f6552; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Outfit', sans-serif; background: #e9e2d2; padding: 26px 14px; color: var(--ink); }

    .toolbar { max-width: 1050px; margin: 0 auto 18px; display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap; }
    .tb-btn { border: none; border-radius: 40px; padding: 11px 24px; font-weight: 600; font-family: 'Outfit', sans-serif; cursor: pointer; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .tb-print { background: linear-gradient(90deg, #D4AF37, #B8860B); color: #2c2418; }
    .tb-back { background: #fff; border: 1px solid #ddd3bd; color: var(--muted); }

    /* Height follows content so footer stays inside the gold frame */
    .cert {
      max-width: 1050px;
      width: 100%;
      margin: 0 auto;
      background: radial-gradient(circle at 50% 30%, #fffdf7 0%, #fdf8ec 60%, #f8f0da 100%);
      position: relative;
      box-shadow: 0 22px 60px rgba(0,0,0,0.22);
      padding: 22px;
      overflow: hidden;
    }
    .cert-frame { position: absolute; inset: 16px; border: 3px solid var(--gold); pointer-events: none; z-index: 0; }
    .cert-frame::before { content: ''; position: absolute; inset: 6px; border: 1px solid var(--gold-light); }
    .cert-corner { position: absolute; width: 34px; height: 34px; border: 3px solid var(--gold-dark); z-index: 0; }
    .cc-tl { top: 6px; left: 6px; border-right: none; border-bottom: none; }
    .cc-tr { top: 6px; right: 6px; border-left: none; border-bottom: none; }
    .cc-bl { bottom: 6px; left: 6px; border-right: none; border-top: none; }
    .cc-br { bottom: 6px; right: 6px; border-left: none; border-top: none; }

    .cert-wm {
      position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
      width: 52%; max-width: 560px; height: auto; opacity: 0.05; pointer-events: none; z-index: 1;
    }
    .wm-text {
      position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
      font-family: 'Cormorant Garamond', serif; font-size: clamp(2.4rem, 7vw, 5.5rem); font-weight: 700;
      color: var(--gold); opacity: 0.045; transform: rotate(-24deg); letter-spacing: 6px;
      pointer-events: none; text-transform: uppercase; white-space: nowrap; z-index: 1;
    }

    .cert-inner {
      position: relative;
      z-index: 2;
      padding: 28px 42px 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .cert-logo { height: 68px; width: auto; max-width: 240px; object-fit: contain; display: block; margin: 0 auto 4px; }
    .cert-company { font-family: 'Cormorant Garamond', serif; font-size: 1.35rem; font-weight: 700; letter-spacing: 1px; }
    .cert-company-sub { font-size: 0.62rem; letter-spacing: 2.5px; text-transform: uppercase; color: var(--gold-dark); margin-top: 2px; }

    .cert-title { font-family: 'Cormorant Garamond', serif; font-size: clamp(1.55rem, 3.6vw, 2.45rem); font-weight: 700; color: var(--gold-dark); margin-top: 12px; letter-spacing: 2px; }
    .cert-rule { width: 150px; height: 2px; background: linear-gradient(90deg, transparent, var(--gold), transparent); margin: 8px auto 0; }
    .cert-pre { font-style: italic; color: var(--muted); margin-top: 14px; font-size: 0.9rem; }

    .cert-name { font-family: 'Cormorant Garamond', serif; font-size: clamp(1.45rem, 3.2vw, 2.2rem); font-weight: 700; color: var(--ink); margin-top: 6px; border-bottom: 2px dotted var(--gold); display: inline-block; padding: 0 18px 3px; }
    .cert-body { max-width: 720px; margin: 12px auto 0; font-size: 0.9rem; line-height: 1.75; color: #4a4230; }
    .cert-prop { font-weight: 600; color: var(--ink); }

    .cert-meta { display: flex; gap: 28px; justify-content: center; margin-top: 12px; font-size: 0.78rem; color: var(--muted); flex-wrap: wrap; }
    .cert-meta strong { color: var(--ink); }

    .cert-foot {
      margin-top: 22px;
      width: 100%;
      max-width: 860px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) 96px minmax(0, 1fr);
      align-items: end;
      gap: 12px 20px;
      padding: 8px 4px 4px;
    }
    .cert-sign { text-align: center; min-width: 0; width: 100%; }
    .cert-signature {
      height: 70px;
      width: auto;
      max-width: 190px;
      object-fit: contain;
      display: block;
      margin: 0 auto;
    }
    .cert-signature-md { height: 62px; max-width: 170px; }
    .cert-sign-line { border-top: 1.5px solid var(--ink); margin: 0 auto 5px; width: 150px; }
    .cert-sign small { color: var(--muted); font-size: 0.7rem; display: block; line-height: 1.3; }
    .cert-sign b { font-family: 'Cormorant Garamond', serif; font-size: 0.88rem; display: block; line-height: 1.25; }

    .cert-seal {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      border: 3px double var(--gold-dark);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: var(--gold-dark);
      text-align: center;
      transform: rotate(-8deg);
      background: rgba(201,162,39,0.08);
      box-shadow: inset 0 0 0 3px rgba(201,162,39,0.12);
      justify-self: center;
      align-self: center;
      margin-bottom: 18px;
      z-index: 3;
    }
    .cert-seal span { font-size: 0.45rem; letter-spacing: 0.6px; text-transform: uppercase; line-height: 1.2; max-width: 72px; }
    .cert-seal .seal-logo { width: 40px; height: auto; object-fit: contain; margin-bottom: 2px; display: block; }

    @media (max-width: 760px) {
      .cert { padding: 14px; }
      .cert-inner { padding: 22px 16px 18px; }
      .cert-foot {
        grid-template-columns: 1fr;
        justify-items: center;
        gap: 18px;
        max-width: 100%;
      }
      .cert-seal { margin-bottom: 0; order: 2; }
      .cert-sign:last-child { order: 3; }
    }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none !important; }
      .cert {
        box-shadow: none;
        max-width: 100%;
        width: 100%;
        padding: 10mm;
        page-break-inside: avoid;
      }
      .cert-frame { inset: 8mm; }
      .cert-inner { padding: 8mm 10mm 6mm; }
      .cert-signature { height: 58px; max-width: 160px; }
      .cert-signature-md { height: 52px; max-width: 140px; }
      .cert-seal { width: 78px; height: 78px; margin-bottom: 14px; }
      @page { size: A4 landscape; margin: 0; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a href="admin-transactions.php" class="tb-btn tb-back">&larr; Back</a>
    <a href="certificate.php?id=<?= (int) $tx['id'] ?>&pdf=1" class="tb-btn tb-print">⬇ Download PDF</a>
    <button class="tb-btn tb-print" onclick="window.print()">🖨 Print</button>
  </div>

  <div class="cert">
    <div class="cert-frame"></div>
    <span class="cert-corner cc-tl"></span><span class="cert-corner cc-tr"></span>
    <span class="cert-corner cc-bl"></span><span class="cert-corner cc-br"></span>
    <div class="wm-text"><?= e(siteName()) ?></div>
    <img class="cert-wm" src="<?= e($logoUrl) ?>" alt="">

    <div class="cert-inner">
      <img class="cert-logo" src="<?= e($logoUrl) ?>" alt="<?= e(siteName()) ?> logo">
      <div class="cert-company"><?= e(siteName()) ?></div>
      <div class="cert-company-sub">Luxury Real Estate</div>

      <div class="cert-title"><?= e($title) ?></div>
      <div class="cert-rule"></div>

      <div class="cert-pre">This is to certify that</div>
      <div class="cert-name"><?= e($tx['customerName']) ?></div>

      <div class="cert-body">
        <?= e($body) ?>
        <br>
        <span class="cert-prop"><?= e($property) ?></span><?= $location !== '' ? ', located at <span class="cert-prop">' . e($location) . '</span>' : '' ?>,
        for the consideration sum of <span class="cert-prop"><?= $symbol . number_format($amount) ?> (<?= e($currency) ?>)</span>,
        on this <span class="cert-prop"><?= e($dateDisplay) ?></span>.
      </div>

      <div class="cert-meta">
        <div>Certificate No: <strong><?= e($tx['reference']) ?></strong></div>
        <div>Date Issued: <strong><?= e(date('d M, Y')) ?></strong></div>
      </div>

      <div class="cert-foot">
        <div class="cert-sign">
          <img class="cert-signature" src="<?= e($signatureUrl) ?>" alt="Authorized signature">
          <div class="cert-sign-line"></div>
          <b><?= e(siteName()) ?> Administrator</b>
          <small>Authorized Signatory</small>
        </div>

        <div class="cert-seal">
          <img class="seal-logo" src="<?= e($logoUrl) ?>" alt="">
          <span><?= e(siteName()) ?><br>Official Seal</span>
        </div>

        <div class="cert-sign">
          <img class="cert-signature cert-signature-md" src="<?= e($signatureUrl) ?>" alt="Managing Director signature">
          <div class="cert-sign-line"></div>
          <b>Managing Director</b>
          <small><?= e(siteName()) ?></small>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
