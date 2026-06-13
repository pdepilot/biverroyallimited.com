<?php
/**
 * Allowlisted HTML sanitizer for admin-composed email bodies.
 */
declare(strict_types=1);

final class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'a', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'blockquote', 'span', 'div', 'hr',
    ];

    public static function sanitizeEmailHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowed = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $clean = strip_tags($html, $allowed);

        return self::stripDangerousAttributes($clean);
    }

    public static function htmlToPlain(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private static function stripDangerousAttributes(string $html): string
    {
        $html = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $html) ?? $html;
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;

        return preg_replace_callback(
            '/<a\s+([^>]*href\s*=\s*(["\'])(.*?)\2[^>]*)>/i',
            static function (array $m): string {
                $href = $m[3];
                if (!preg_match('#^(https?://|mailto:)#i', $href)) {
                    return '<a>';
                }

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
            },
            $html
        ) ?? $html;
    }
}
