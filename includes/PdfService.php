<?php
/**
 * Thin wrapper around Dompdf for rendering HTML documents to PDF bytes.
 */

declare(strict_types=1);

final class PdfService
{
    public static function available(): bool
    {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_readable($autoload)) {
            require_once $autoload;
        }

        return class_exists(\Dompdf\Dompdf::class);
    }

    /**
     * Render HTML to PDF and return the raw bytes.
     *
     * @param 'portrait'|'landscape' $orientation
     */
    public static function render(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        if (!self::available()) {
            throw new RuntimeException('PDF engine not installed. Run "composer require dompdf/dompdf" and upload the vendor folder.');
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
