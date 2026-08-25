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

    $meta = TransactionDocument::pdfMeta($tx, 'receipt');
    $pdf = PdfService::render(TransactionDocument::pdf($tx, 'receipt'), $meta['paper'], $meta['orientation']);

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

/** Convert a whole number into English words (for Naira amounts). */
function amountToWords(int $number): string
{
    if ($number === 0) {
        return 'Zero';
    }

    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $chunkToWords = static function (int $n) use ($ones, $tens, &$chunkToWords): string {
        if ($n < 20) {
            return $ones[$n];
        }
        if ($n < 100) {
            return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
        }
        return trim($ones[intdiv($n, 100)] . ' Hundred ' . $chunkToWords($n % 100));
    };

    $scales = [1000000000 => 'Billion', 1000000 => 'Million', 1000 => 'Thousand', 1 => ''];
    $words = '';
    foreach ($scales as $value => $name) {
        if ($number >= $value) {
            $count = intdiv($number, $value);
            $number %= $value;
            $words .= $chunkToWords($count) . ($name !== '' ? ' ' . $name . ' ' : ' ');
        }
    }

    return trim(preg_replace('/\s+/', ' ', $words) ?? $words);
}

$currency = $tx['currency'] ?: 'NGN';
$symbol = $currency === 'NGN' ? '₦' : '';
$amount = (int) $tx['amount'];
$amountPaid = (int) $tx['amountPaid'];
$balance = max(0, $amount - $amountPaid);
$statusLabel = strtoupper($tx['paymentStatus']);
$typeLabel = TransactionRepository::typeLabel($tx['transactionType']);

$logoUrl = '../assets/images/biver-logo.png';
$signatureUrl = publicAssetUrl('assets/images/signature.png') ?? '../assets/images/signature.png';
$dateDisplay = $tx['transactionDate'] ? date('d F, Y', (int) strtotime($tx['transactionDate'])) : date('d F, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt <?= e($tx['reference']) ?> | <?= e(siteName()) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #D4AF37; --gold-dark: #9e7e2c; --ink: #2c2418; --muted: #7a6f5e;
      --line: #e6ddc7; --paper: #ffffff;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Outfit', sans-serif; background: #efe9dc; color: var(--ink); padding: 30px 16px; }

    .toolbar { max-width: 800px; margin: 0 auto 20px; display: flex; gap: 12px; justify-content: flex-end; }
    .tb-btn { border: none; border-radius: 40px; padding: 11px 24px; font-weight: 600; font-family: 'Outfit', sans-serif; cursor: pointer; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .tb-print { background: linear-gradient(90deg, #D4AF37, #B8860B); color: #2c2418; }
    .tb-back { background: #fff; border: 1px solid var(--line); color: var(--muted); }

    .receipt {
      max-width: 800px; margin: 0 auto; background: var(--paper);
      box-shadow: 0 20px 50px rgba(0,0,0,0.15); position: relative; overflow: hidden;
      border-top: 6px solid var(--gold);
    }
    .receipt::after {
      content: ''; position: absolute; inset: 0;
      background: url('<?= e($logoUrl) ?>') center 45% no-repeat;
      background-size: 55%; opacity: 0.04; pointer-events: none;
    }
    .r-inner { position: relative; z-index: 1; padding: 42px 48px 36px; }

    .r-head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid var(--line); padding-bottom: 22px; }
    .r-brand { display: flex; gap: 16px; align-items: flex-start; }
    .r-logo { width: 74px; height: 74px; object-fit: contain; }
    .r-brand-fallback { width: 74px; height: 74px; border-radius: 12px; background: linear-gradient(135deg, #D4AF37, #B8860B); color: #2c2418; display: none; align-items: center; justify-content: center; font-family: 'Cormorant Garamond', serif; font-weight: 700; font-size: 1.6rem; }
    .r-name { font-family: 'Cormorant Garamond', serif; font-size: 1.7rem; font-weight: 700; color: var(--ink); line-height: 1.1; }
    .r-tag { color: var(--gold-dark); font-size: 0.78rem; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 3px; }
    .r-company-meta { font-size: 0.78rem; color: var(--muted); line-height: 1.7; margin-top: 8px; }
    .r-title-block { text-align: right; }
    .r-title { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 700; letter-spacing: 2px; color: var(--gold-dark); }
    .r-ref { font-size: 0.85rem; color: var(--muted); margin-top: 4px; }
    .r-ref strong { color: var(--ink); }

    .r-meta-row { display: flex; justify-content: space-between; gap: 24px; margin-top: 24px; }
    .r-party h4 { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--gold-dark); margin-bottom: 8px; }
    .r-party p { font-size: 0.9rem; line-height: 1.6; color: var(--ink); }
    .r-party .muted { color: var(--muted); font-size: 0.82rem; }

    .r-stamp {
      align-self: center; border: 3px solid; border-radius: 10px; padding: 8px 18px;
      font-weight: 800; letter-spacing: 2px; font-size: 1.1rem; transform: rotate(-8deg);
      opacity: 0.85;
    }
    .stamp-paid { color: #1f8a4c; border-color: #1f8a4c; }
    .stamp-part { color: #b8860b; border-color: #b8860b; }
    .stamp-pending { color: #c0392b; border-color: #c0392b; }

    table.r-items { width: 100%; border-collapse: collapse; margin-top: 28px; }
    table.r-items thead th { background: #2c2418; color: #f5efe1; text-align: left; padding: 13px 16px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; }
    table.r-items thead th.num { text-align: right; }
    table.r-items tbody td { padding: 16px; border-bottom: 1px solid var(--line); font-size: 0.92rem; vertical-align: top; }
    table.r-items tbody td.num { text-align: right; font-weight: 600; white-space: nowrap; }
    .item-title { font-weight: 600; }
    .item-sub { color: var(--muted); font-size: 0.82rem; margin-top: 3px; }

    .r-totals { margin-top: 20px; display: flex; justify-content: flex-end; }
    .r-totals table { min-width: 300px; }
    .r-totals td { padding: 8px 16px; font-size: 0.9rem; }
    .r-totals td.label { color: var(--muted); }
    .r-totals td.val { text-align: right; font-weight: 600; }
    .r-totals tr.grand td { border-top: 2px solid var(--gold); font-size: 1.05rem; font-weight: 800; color: var(--gold-dark); padding-top: 12px; }

    .r-words { margin-top: 18px; background: #faf6ea; border-left: 4px solid var(--gold); padding: 12px 16px; font-size: 0.86rem; }
    .r-words strong { color: var(--gold-dark); }

    .r-foot { margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; }
    .r-sign { text-align: center; }
    .r-signature { height: 130px; width: auto; max-width: 360px; object-fit: contain; display: block; margin: 0 auto 2px; }
    .r-sign-line { width: 200px; border-top: 1.5px solid var(--ink); margin: 0 auto 6px; }
    .r-sign small { color: var(--muted); font-size: 0.78rem; }
    .r-thanks { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; color: var(--gold-dark); }

    .r-note { margin-top: 26px; padding-top: 16px; border-top: 1px dashed var(--line); text-align: center; font-size: 0.72rem; color: var(--muted); line-height: 1.6; }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none; }
      .receipt { box-shadow: none; max-width: 100%; border-top-width: 6px; }
      @page { margin: 12mm; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a href="admin-transactions.php" class="tb-btn tb-back">&larr; Back</a>
    <a href="receipt.php?id=<?= (int) $tx['id'] ?>&pdf=1" class="tb-btn tb-print">⬇ Download PDF</a>
    <button class="tb-btn tb-print" onclick="window.print()">🖨 Print</button>
  </div>

  <div class="receipt">
    <div class="r-inner">
      <div class="r-head">
        <div class="r-brand">
          <img src="<?= e($logoUrl) ?>" alt="<?= e(siteName()) ?>" class="r-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="r-brand-fallback">BR</div>
          <div>
            <div class="r-name"><?= e(siteName()) ?></div>
            <div class="r-tag">Luxury Real Estate</div>
            <div class="r-company-meta">
              <?= e(siteAddress()) ?><br>
              <?= e(siteContactPhone()) ?> &nbsp;•&nbsp; <?= e(siteContactEmail()) ?>
            </div>
          </div>
        </div>
        <div class="r-title-block">
          <div class="r-title">RECEIPT</div>
          <div class="r-ref">No. <strong><?= e($tx['reference']) ?></strong></div>
          <div class="r-ref">Date: <strong><?= e($dateDisplay) ?></strong></div>
        </div>
      </div>

      <div class="r-meta-row">
        <div class="r-party">
          <h4>Received From</h4>
          <p><strong><?= e($tx['customerName']) ?></strong></p>
          <?php if ($tx['customerAddress'] !== ''): ?><p class="muted"><?= e($tx['customerAddress']) ?></p><?php endif; ?>
          <?php if ($tx['customerPhone'] !== ''): ?><p class="muted"><?= e($tx['customerPhone']) ?></p><?php endif; ?>
          <?php if ($tx['customerEmail'] !== ''): ?><p class="muted"><?= e($tx['customerEmail']) ?></p><?php endif; ?>
        </div>
        <div class="r-stamp stamp-<?= e($tx['paymentStatus']) ?>"><?= e($statusLabel) ?></div>
      </div>

      <table class="r-items">
        <thead>
          <tr>
            <th>Description</th>
            <th class="num">Amount (<?= e($currency) ?>)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="item-title"><?= e($typeLabel) ?><?= $tx['propertyTitle'] !== '' ? ' — ' . e($tx['propertyTitle']) : '' ?></div>
              <?php if ($tx['propertyLocation'] !== ''): ?><div class="item-sub">Location: <?= e($tx['propertyLocation']) ?></div><?php endif; ?>
              <?php if ($tx['description'] !== ''): ?><div class="item-sub"><?= nl2br(e($tx['description'])) ?></div><?php endif; ?>
            </td>
            <td class="num"><?= $symbol . number_format($amount) ?></td>
          </tr>
        </tbody>
      </table>

      <div class="r-totals">
        <table>
          <tr><td class="label">Total Amount</td><td class="val"><?= $symbol . number_format($amount) ?></td></tr>
          <tr><td class="label">Amount Paid</td><td class="val"><?= $symbol . number_format($amountPaid) ?></td></tr>
          <tr class="grand"><td class="label">Balance Due</td><td class="val"><?= $symbol . number_format($balance) ?></td></tr>
        </table>
      </div>

      <div class="r-words">
        Amount in words: <strong><?= e(amountToWords($amountPaid > 0 ? $amountPaid : $amount)) ?> <?= $currency === 'NGN' ? 'Naira' : e($currency) ?> Only</strong>
        <?php if ($tx['paymentMethod'] !== ''): ?><br>Payment method: <strong><?= e($tx['paymentMethod']) ?></strong><?php endif; ?>
      </div>

      <div class="r-foot">
        <div class="r-thanks">Thank you for your patronage.</div>
        <div class="r-sign">
          <img class="r-signature" src="<?= e($signatureUrl) ?>" alt="Authorized signature">
          <div class="r-sign-line"></div>
          <small>Authorized Signatory<?= $tx['issuedBy'] !== '' ? ' — ' . e($tx['issuedBy']) : '' ?></small>
        </div>
      </div>

      <div class="r-note">
        This is a computer-generated receipt issued by <?= e(siteName()) ?>. For enquiries contact <?= e(siteContactEmail()) ?> or <?= e(siteContactPhone()) ?>.
      </div>
    </div>
  </div>
</body>
</html>
