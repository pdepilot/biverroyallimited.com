<?php
/**
 * Chatbot helpers — Nigerian Naira formatting and budget parsing.
 */
declare(strict_types=1);

function chatbot_format_naira(int|float $amount): string
{
    return '₦' . number_format((float) $amount, 0, '.', ',');
}

/**
 * Parse budget phrases from user text (amounts in Naira).
 *
 * @return array{min: ?int, max: ?int}|null
 */
function chatbot_parse_budget(string $text): ?array
{
    $t = mb_strtolower($text);
    $million = 1_000_000;

    if (preg_match('/above\s*₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)) {
        $val = chatbot_parse_amount_token($m[1], $m[2] ?? '');
        return ['min' => $val, 'max' => null];
    }

    if (preg_match('/under\s*₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)
        || preg_match('/below\s*₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)
        || preg_match('/less\s+than\s*₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)
    ) {
        $val = chatbot_parse_amount_token($m[1], $m[2] ?? '');
        return ['min' => null, 'max' => $val];
    }

    if (preg_match('/between\s*₦?\s*([\d.,]+)\s*(m|million|mio)?\s*(?:and|to|-)\s*₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)) {
        return [
            'min' => chatbot_parse_amount_token($m[1], $m[2] ?? ''),
            'max' => chatbot_parse_amount_token($m[3], $m[4] ?? ''),
        ];
    }

    if (preg_match('/₦?\s*([\d.,]+)\s*(m|million|mio)\s*(?:to|-)\s*₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)) {
        return [
            'min' => chatbot_parse_amount_token($m[1], 'm'),
            'max' => chatbot_parse_amount_token($m[2], $m[3] ?? 'm'),
        ];
    }

    if (preg_match('/budget\s*(?:of\s*)?₦?\s*([\d.,]+)\s*(m|million|mio)?/u', $t, $m)) {
        $val = chatbot_parse_amount_token($m[1], $m[2] ?? '');
        return ['min' => null, 'max' => (int) round($val * 1.15)];
    }

    return null;
}

function chatbot_parse_amount_token(string $raw, string $suffix): int
{
    $n = (float) str_replace([',', ' '], '', $raw);
    $suffix = mb_strtolower(trim($suffix));
    if (in_array($suffix, ['m', 'million', 'mio', 'mil'], true) || preg_match('/\d\s*m\b/u', $raw)) {
        $n *= 1_000_000;
    } elseif ($n > 0 && $n < 1000) {
        $n *= 1_000_000;
    }

    return max(0, (int) round($n));
}

/**
 * @return list<string>
 */
function chatbot_extract_locations(string $text): array
{
    $places = [
        'port harcourt', 'rivers state', 'rivers', 'owerri', 'imo state', 'imo',
        'lagos', 'abuja', 'fct', 'enugu', 'ph', 'portharcourt',
    ];
    $found = [];
    $t = mb_strtolower($text);
    foreach ($places as $place) {
        if (str_contains($t, $place)) {
            $found[] = $place;
        }
    }
    return $found;
}

function chatbot_map_sender_to_support(string $senderType): string
{
    return match ($senderType) {
        'visitor' => 'user',
        'agent'   => 'admin',
        default   => 'bot',
    };
}
