<?php
/**
 * Renders email-safe (inline-styled) HTML for transaction receipts and certificates.
 * Used when an admin shares a document with a customer via the email pipeline.
 */

declare(strict_types=1);

require_once __DIR__ . '/TransactionRepository.php';
require_once __DIR__ . '/site_helpers.php';

final class TransactionDocument
{
    /**
     * Build a full branded HTML email for the given document type.
     *
     * @param array<string, mixed> $tx
     * @param 'receipt'|'certificate' $type
     */
    public static function email(array $tx, string $type, string $intro = ''): string
    {
        $inner = $type === 'certificate' ? self::certificateBody($tx) : self::receiptBody($tx);

        return self::shell($inner, $intro);
    }

    /**
     * Full standalone HTML document optimized for PDF rendering (Dompdf).
     *
     * @param array<string, mixed> $tx
     * @param 'receipt'|'certificate' $type
     */
    public static function pdf(array $tx, string $type): string
    {
        $inner = $type === 'certificate' ? self::certificateBody($tx) : self::receiptBody($tx);

        return self::pdfShell($inner);
    }

    /**
     * @return array{paper:string,orientation:string,filename:string}
     */
    public static function pdfMeta(array $tx, string $type): array
    {
        $ref = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($tx['reference'] ?? 'document')) ?: 'document';

        return [
            'paper'       => 'A4',
            'orientation' => $type === 'certificate' ? 'landscape' : 'portrait',
            'filename'    => ($type === 'certificate' ? 'Certificate-' : 'Receipt-') . $ref . '.pdf',
        ];
    }

    /**
     * Short branded email body used when the full document is attached as PDF.
     *
     * @param array<string, mixed> $tx
     * @param 'receipt'|'certificate' $type
     */
    public static function coverNote(array $tx, string $type, string $intro = ''): string
    {
        $docName = $type === 'certificate'
            ? TransactionRepository::certificateTitle((string) $tx['transactionType'])
            : 'Official Receipt';
        $ref = self::e((string) $tx['reference']);
        $customer = self::e((string) $tx['customerName']);
        $property = $tx['propertyTitle'] !== '' ? ' for <b>' . self::e((string) $tx['propertyTitle']) . '</b>' : '';

        $inner =
            '<div style="font-family:Georgia,serif;font-size:20px;color:#9e7e2c;margin-bottom:12px;">Dear ' . $customer . ',</div>'
            . ($intro !== '' ? '<div style="font-size:15px;color:#4a4230;line-height:1.7;margin-bottom:14px;">' . nl2br(self::e($intro)) . '</div>' : '')
            . '<div style="font-size:15px;color:#4a4230;line-height:1.8;">Please find attached your <b>' . self::e($docName) . '</b> (Ref: <b>' . $ref . '</b>)' . $property . ' from ' . self::e(siteName()) . '.</div>'
            . '<div style="margin-top:16px;background:#faf6ea;border-left:4px solid #D4AF37;padding:12px 16px;font-size:14px;color:#4a4230;">Amount: <b style="color:#9e7e2c;">' . self::e(self::money($tx)) . '</b> &nbsp;•&nbsp; Date: <b>' . self::e(self::dateDisplay((string) $tx['transactionDate'])) . '</b></div>'
            . '<div style="margin-top:20px;font-size:14px;color:#4a4230;line-height:1.7;">Kindly keep this document for your records. For any enquiries, reply to this email or contact us on ' . self::e(siteContactPhone()) . '.</div>'
            . '<div style="margin-top:18px;font-family:Georgia,serif;font-size:16px;color:#9e7e2c;">Warm regards,<br>' . self::e(siteName()) . '</div>';

        return self::shell($inner, '');
    }

    public static function defaultSubject(array $tx, string $type): string
    {
        $ref = (string) ($tx['reference'] ?? '');
        if ($type === 'certificate') {
            return TransactionRepository::certificateTitle((string) $tx['transactionType']) . ' — ' . $ref;
        }

        return 'Your Receipt from ' . siteName() . ' — ' . $ref;
    }

    /**
     * Plain-text fallback.
     *
     * @param array<string, mixed> $tx
     */
    public static function plain(array $tx, string $type): string
    {
        $lines = [
            siteName(),
            $type === 'certificate'
                ? TransactionRepository::certificateTitle((string) $tx['transactionType'])
                : 'Official Receipt',
            'Reference: ' . ($tx['reference'] ?? ''),
            'Customer: ' . ($tx['customerName'] ?? ''),
            'Property: ' . ($tx['propertyTitle'] ?: 'N/A'),
            'Amount: ' . self::money($tx),
            'Date: ' . self::dateDisplay((string) $tx['transactionDate']),
            '',
            'For enquiries: ' . siteContactEmail() . ' / ' . siteContactPhone(),
        ];

        return implode("\n", $lines);
    }

    private static function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    private static function money(array $tx): string
    {
        $currency = (string) ($tx['currency'] ?: 'NGN');
        $symbol = $currency === 'NGN' ? '₦' : '';
        return $symbol . number_format((int) $tx['amount']);
    }

    private static function dateDisplay(string $date): string
    {
        return $date !== '' ? date('d F, Y', (int) strtotime($date)) : date('d F, Y');
    }

    /**
     * Outer branded email shell (inline styles, table-based for email clients).
     */
    private static function shell(string $inner, string $intro): string
    {
        $name = self::e(siteName());
        $email = self::e(siteContactEmail());
        $phone = self::e(siteContactPhone());
        $address = self::e(siteAddress());
        $year = date('Y');
        $introHtml = $intro !== ''
            ? '<tr><td style="padding:0 32px 8px;font-size:15px;color:#4a4230;line-height:1.7;">' . nl2br(self::e($intro)) . '</td></tr>'
            : '';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#efe9dc;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#efe9dc;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:96%;background:#ffffff;border-radius:12px;overflow:hidden;border-top:6px solid #D4AF37;">'
            // Brand header
            . '<tr><td style="padding:28px 32px 18px;border-bottom:2px solid #ece3cd;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="vertical-align:middle;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="width:52px;height:52px;background:#B8860B;border-radius:50%;color:#2c2418;font-weight:bold;font-size:20px;text-align:center;vertical-align:middle;font-family:Georgia,serif;">BR</td>'
            . '<td style="padding-left:12px;vertical-align:middle;"><div style="font-family:Georgia,serif;font-size:20px;font-weight:bold;color:#2c2418;">' . $name . '</div>'
            . '<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#9e7e2c;">Luxury Real Estate</div></td>'
            . '</tr></table></td>'
            . '<td style="text-align:right;vertical-align:middle;font-size:12px;color:#7a6f5e;line-height:1.6;">' . $phone . '<br>' . $email . '</td>'
            . '</tr></table></td></tr>'
            . $introHtml
            // Document body
            . '<tr><td style="padding:16px 32px 8px;">' . $inner . '</td></tr>'
            // Footer
            . '<tr><td style="padding:20px 32px 26px;border-top:1px solid #ece3cd;font-size:11px;color:#9a917f;line-height:1.6;text-align:center;">'
            . $address . '<br>&copy; ' . $year . ' ' . $name . '. This document was issued electronically.'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /**
     * Brand logo as a base64 data URI (works inside Dompdf-generated PDFs).
     */
    private static function logoDataUri(): ?string
    {
        // Dompdf renders PNGs through the GD extension; skip the image (fall back
        // to the text mark) when GD is unavailable so PDF generation never fails.
        if (!extension_loaded('gd')) {
            return null;
        }

        $path = dirname(__DIR__) . '/assets/images/biver-logo.png';
        if (!is_file($path)) {
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * Authorized signatory image as a base64 data URI for PDF/email output.
     */
    private static function signatureDataUri(): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $path = dirname(__DIR__) . '/assets/images/signature.png';
        if (!is_file($path)) {
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($data);
    }

    private static function signatureImgHtml(bool $compact = false): string
    {
        $sig = self::signatureDataUri();
        if ($sig === null) {
            return '';
        }

        $height = $compact ? 90 : 100;
        $maxWidth = $compact ? 240 : 280;

        return '<img src="' . $sig . '" alt="Authorized signature" style="height:' . $height . 'px;width:auto;max-width:' . $maxWidth . 'px;object-fit:contain;display:block;margin:0 auto 4px;">';
    }

    private static function sealHtml(): string
    {
        $logo = self::logoDataUri();
        $mark = $logo !== null
            ? '<img src="' . $logo . '" alt="" style="width:42px;height:auto;display:block;margin:0 auto 3px;">'
            : '<div style="font-family:Georgia,serif;font-size:20px;font-weight:bold;line-height:1;margin-bottom:2px;">BR</div>';

        return '<div style="width:96px;height:96px;border:3px double #9e7e2c;border-radius:50%;margin:0 auto;display:table;background:rgba(201,162,39,0.08);">'
            . '<div style="display:table-cell;vertical-align:middle;text-align:center;color:#9e7e2c;padding:8px;">'
            . $mark
            . '<div style="font-size:7px;letter-spacing:0.6px;text-transform:uppercase;line-height:1.25;">' . self::e(siteName()) . '<br>Official Seal</div>'
            . '</div></div>';
    }

    /**
     * White, print-friendly shell for PDF output (Dompdf-compatible markup).
     */
    private static function pdfShell(string $inner): string
    {
        $name = self::e(siteName());
        $email = self::e(siteContactEmail());
        $phone = self::e(siteContactPhone());
        $address = self::e(siteAddress());
        $year = date('Y');
        $logo = self::logoDataUri();

        $brandCell = $logo !== null
            ? '<td style="vertical-align:middle;"><img src="' . $logo . '" alt="' . $name . '" style="height:52px;"></td>'
            : '<td style="width:48px;height:48px;background:#B8860B;border-radius:24px;color:#2c2418;font-weight:bold;font-size:18px;text-align:center;vertical-align:middle;">BR</td>'
                . '<td style="padding-left:10px;vertical-align:middle;"><div style="font-size:19px;font-weight:bold;color:#2c2418;">' . $name . '</div>'
                . '<div style="font-size:10px;letter-spacing:2px;color:#9e7e2c;">LUXURY REAL ESTATE</div></td>';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,Arial,sans-serif;color:#2c2418;margin:0;}'
            . 'table{border-collapse:collapse;}'
            . '</style></head><body>'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:2px solid #D4AF37;padding-bottom:10px;margin-bottom:16px;"><tr>'
            . '<td style="vertical-align:middle;"><table cellpadding="0" cellspacing="0"><tr>'
            . $brandCell
            . '</tr></table></td>'
            . '<td style="text-align:right;vertical-align:middle;font-size:11px;color:#7a6f5e;">' . $phone . '<br>' . $email . '</td>'
            . '</tr></table>'
            . $inner
            . '<div style="margin-top:22px;padding-top:10px;border-top:1px solid #ece3cd;font-size:10px;color:#9a917f;text-align:center;">'
            . $address . ' &nbsp;&bull;&nbsp; &copy; ' . $year . ' ' . $name . '</div>'
            . '</body></html>';
    }

    /**
     * @param array<string, mixed> $tx
     */
    private static function receiptBody(array $tx): string
    {
        $currency = (string) ($tx['currency'] ?: 'NGN');
        $symbol = $currency === 'NGN' ? '₦' : '';
        $amount = (int) $tx['amount'];
        $paid = (int) $tx['amountPaid'];
        $balance = max(0, $amount - $paid);
        $status = strtoupper((string) $tx['paymentStatus']);
        $statusColor = match ($tx['paymentStatus']) {
            'paid' => '#1f8a4c',
            'part' => '#b8860b',
            default => '#c0392b',
        };
        $typeLabel = self::e(TransactionRepository::typeLabel((string) $tx['transactionType']));
        $desc = trim(
            $typeLabel
            . ($tx['propertyTitle'] !== '' ? ' — ' . self::e((string) $tx['propertyTitle']) : '')
        );
        $sub = [];
        if ($tx['propertyLocation'] !== '') {
            $sub[] = 'Location: ' . self::e((string) $tx['propertyLocation']);
        }
        if ($tx['description'] !== '') {
            $sub[] = nl2br(self::e((string) $tx['description']));
        }
        $subHtml = $sub !== [] ? '<div style="color:#7a6f5e;font-size:12px;margin-top:4px;">' . implode('<br>', $sub) . '</div>' : '';

        $rows = self::e((string) $tx['reference']);
        $customer = self::customerBlock($tx);

        return
            '<div style="font-family:Georgia,serif;font-size:22px;font-weight:bold;letter-spacing:1px;color:#9e7e2c;">OFFICIAL RECEIPT</div>'
            . '<div style="font-size:13px;color:#7a6f5e;margin:4px 0 16px;">No. <b style="color:#2c2418;">' . $rows . '</b> &nbsp;•&nbsp; Date: <b style="color:#2c2418;">' . self::e(self::dateDisplay((string) $tx['transactionDate'])) . '</b></div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="vertical-align:top;">' . $customer . '</td>'
            . '<td style="vertical-align:top;text-align:right;"><span style="display:inline-block;border:2px solid ' . $statusColor . ';color:' . $statusColor . ';font-weight:bold;letter-spacing:1px;padding:6px 14px;border-radius:6px;font-size:13px;">' . self::e($status) . '</span></td>'
            . '</tr></table>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;border-collapse:collapse;">'
            . '<tr><th align="left" style="background:#2c2418;color:#f5efe1;padding:11px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Description</th>'
            . '<th align="right" style="background:#2c2418;color:#f5efe1;padding:11px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Amount (' . self::e($currency) . ')</th></tr>'
            . '<tr><td style="padding:14px;border-bottom:1px solid #ece3cd;font-size:14px;"><b>' . $desc . '</b>' . $subHtml . '</td>'
            . '<td align="right" style="padding:14px;border-bottom:1px solid #ece3cd;font-size:14px;font-weight:bold;white-space:nowrap;">' . $symbol . number_format($amount) . '</td></tr>'
            . '</table>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;"><tr><td></td><td style="width:280px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
            . '<tr><td style="padding:6px 4px;color:#7a6f5e;font-size:13px;">Total Amount</td><td align="right" style="padding:6px 4px;font-weight:bold;font-size:13px;">' . $symbol . number_format($amount) . '</td></tr>'
            . '<tr><td style="padding:6px 4px;color:#7a6f5e;font-size:13px;">Amount Paid</td><td align="right" style="padding:6px 4px;font-weight:bold;font-size:13px;">' . $symbol . number_format($paid) . '</td></tr>'
            . '<tr><td style="padding:10px 4px 0;border-top:2px solid #D4AF37;color:#9e7e2c;font-weight:bold;font-size:15px;">Balance Due</td><td align="right" style="padding:10px 4px 0;border-top:2px solid #D4AF37;color:#9e7e2c;font-weight:bold;font-size:15px;">' . $symbol . number_format($balance) . '</td></tr>'
            . '</table></td></tr></table>'
            . '<div style="margin-top:16px;background:#faf6ea;border-left:4px solid #D4AF37;padding:10px 14px;font-size:13px;color:#4a4230;">'
            . 'Amount in words: <b style="color:#9e7e2c;">' . self::e(self::amountToWords($paid > 0 ? $paid : $amount)) . ' ' . ($currency === 'NGN' ? 'Naira' : self::e($currency)) . ' Only</b>'
            . ($tx['paymentMethod'] !== '' ? '<br>Payment method: <b>' . self::e((string) $tx['paymentMethod']) . '</b>' : '')
            . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;"><tr>'
            . '<td style="font-family:Georgia,serif;font-size:16px;color:#9e7e2c;vertical-align:bottom;">Thank you for your patronage.</td>'
            . '<td style="text-align:center;font-size:12px;color:#6f6552;vertical-align:bottom;">'
            . self::signatureImgHtml()
            . '<div style="border-top:1.5px solid #2b2415;width:180px;margin:0 auto 6px;"></div>'
            . 'Authorized Signatory'
            . ($tx['issuedBy'] !== '' ? '<br><b style="color:#2b2415;">' . self::e((string) $tx['issuedBy']) . '</b>' : '')
            . '</td></tr></table>';
    }

    /**
     * @param array<string, mixed> $tx
     */
    private static function certificateBody(array $tx): string
    {
        $title = self::e(TransactionRepository::certificateTitle((string) $tx['transactionType']));
        $currency = (string) ($tx['currency'] ?: 'NGN');
        $symbol = $currency === 'NGN' ? '₦' : '';
        $amount = (int) $tx['amount'];
        $property = $tx['propertyTitle'] !== '' ? self::e((string) $tx['propertyTitle']) : 'the property';
        $location = self::e((string) $tx['propertyLocation']);
        $dateDisplay = $tx['transactionDate'] !== ''
            ? date('jS \d\a\y \o\f F, Y', (int) strtotime((string) $tx['transactionDate']))
            : date('jS \d\a\y \o\f F, Y');

        $body = match ($tx['transactionType']) {
            'purchase' => 'is hereby recognized as the rightful owner of the property described below, having successfully completed its purchase through ' . siteName() . '.',
            'rent'     => 'is hereby recognized as the authorized tenant of the property described below, under a tenancy agreement duly facilitated by ' . siteName() . '.',
            'sale'     => 'has successfully and lawfully transferred ownership of the property described below to ' . siteName() . '.',
            default    => 'has completed a verified property transaction with ' . siteName() . '.',
        };

        return
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:3px solid #C9A227;">'
            . '<tr><td style="border:1px solid #E7C868;padding:28px 26px;text-align:center;">'
            . '<div style="font-family:Georgia,serif;font-size:26px;font-weight:bold;letter-spacing:2px;color:#9e7e2c;">' . $title . '</div>'
            . '<div style="width:150px;height:2px;background:#D4AF37;margin:10px auto 0;"></div>'
            . '<div style="font-style:italic;color:#6f6552;margin-top:18px;font-size:14px;">This is to certify that</div>'
            . '<div style="font-family:Georgia,serif;font-size:26px;font-weight:bold;color:#2b2415;margin-top:6px;border-bottom:2px dotted #C9A227;display:inline-block;padding:0 20px 4px;">' . self::e((string) $tx['customerName']) . '</div>'
            . '<div style="max-width:520px;margin:16px auto 0;font-size:14px;line-height:1.9;color:#4a4230;">' . self::e($body)
            . '<br><b style="color:#2b2415;">' . $property . '</b>' . ($location !== '' ? ', located at <b style="color:#2b2415;">' . $location . '</b>' : '')
            . ', for the consideration sum of <b style="color:#2b2415;">' . $symbol . number_format($amount) . ' (' . self::e($currency) . ')</b>, on this <b style="color:#2b2415;">' . self::e($dateDisplay) . '</b>.</div>'
            . '<div style="margin-top:18px;font-size:12px;color:#6f6552;">Certificate No: <b style="color:#2b2415;">' . self::e((string) $tx['reference']) . '</b> &nbsp;•&nbsp; Date Issued: <b style="color:#2b2415;">' . self::e(date('d M, Y')) . '</b></div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;"><tr>'
            . '<td width="38%" style="text-align:center;font-size:12px;color:#6f6552;vertical-align:bottom;">'
            . self::signatureImgHtml()
            . '<div style="border-top:1.5px solid #2b2415;width:160px;margin:0 auto 6px;"></div>'
            . '<b style="font-family:Georgia,serif;color:#2b2415;">' . self::e(siteName()) . ' Administrator</b><br>Authorized Signatory</td>'
            . '<td width="24%" style="text-align:center;vertical-align:middle;padding:0 8px;">'
            . self::sealHtml()
            . '</td>'
            . '<td width="38%" style="text-align:center;font-size:12px;color:#6f6552;vertical-align:bottom;">'
            . self::signatureImgHtml(true)
            . '<div style="border-top:1.5px solid #2b2415;width:160px;margin:0 auto 6px;"></div><b style="font-family:Georgia,serif;color:#2b2415;">Managing Director</b><br>' . self::e(siteName()) . '</td>'
            . '</tr></table>'
            . '</td></tr></table>';
    }

    /**
     * @param array<string, mixed> $tx
     */
    private static function customerBlock(array $tx): string
    {
        $out = '<div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9e7e2c;margin-bottom:6px;">Received From</div>'
            . '<div style="font-size:15px;font-weight:bold;color:#2c2418;">' . self::e((string) $tx['customerName']) . '</div>';
        foreach (['customerAddress', 'customerPhone', 'customerEmail'] as $field) {
            if (($tx[$field] ?? '') !== '') {
                $out .= '<div style="font-size:12px;color:#7a6f5e;">' . self::e((string) $tx[$field]) . '</div>';
            }
        }

        return $out;
    }

    private static function amountToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $chunk = static function (int $n) use ($ones, $tens, &$chunk): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 100) {
                return trim($tens[intdiv($n, 10)] . ' ' . $ones[$n % 10]);
            }
            return trim($ones[intdiv($n, 100)] . ' Hundred ' . $chunk($n % 100));
        };

        $scales = [1000000000 => 'Billion', 1000000 => 'Million', 1000 => 'Thousand', 1 => ''];
        $words = '';
        foreach ($scales as $value => $label) {
            if ($number >= $value) {
                $words .= $chunk(intdiv($number, $value)) . ($label !== '' ? ' ' . $label . ' ' : ' ');
                $number %= $value;
            }
        }

        return trim(preg_replace('/\s+/', ' ', $words) ?? $words);
    }
}
