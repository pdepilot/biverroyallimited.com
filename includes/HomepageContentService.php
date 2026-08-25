<?php
declare(strict_types=1);

final class HomepageContentService
{
    private const CONFIG_FILE = 'config/homepage-content.php';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'slides' => [
                [
                    'eyebrow' => 'Trusted Real Estate Agency in Owerri',
                    'title'   => 'Biver <span class="accent">Royalty</span> Homes',
                    'tagline' => 'Find verified homes for sale and rent in Owerri, Imo State — integrity-first real estate from our office on Wetheral Road.',
                    'bgImage' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1920&auto=format&fit=crop&q=80',
                ],
                [
                    'eyebrow' => 'Luxury Properties',
                    'title'   => 'Find Your <span class="accent">Dream</span> Home',
                    'tagline' => 'Explore our curated selection of luxurious homes, from elegant apartments to expansive estates tailored to your lifestyle.',
                    'bgImage' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1920&auto=format&fit=crop&q=80',
                ],
                [
                    'eyebrow' => 'Trusted Since 2015',
                    'title'   => 'Built on <span class="accent">Integrity</span>',
                    'tagline' => 'Over 1,200 happy families have trusted us to guide them home. Experience real estate the way it should be — transparent, efficient, and personal.',
                    'bgImage' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=1920&auto=format&fit=crop&q=80',
                ],
                [
                    'eyebrow' => 'Buy, Rent or Sell',
                    'title'   => 'Your Property <span class="accent">Journey</span> Starts Here',
                    'tagline' => 'Whether you\'re buying your first home, renting a space, or selling your property, our expert team walks every step with you.',
                    'bgImage' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1920&auto=format&fit=crop&q=80',
                ],
                [
                    'eyebrow' => 'Expert Market Knowledge',
                    'title'   => 'Owerri\'s <span class="accent">Finest</span> Real Estate',
                    'tagline' => 'With deep roots in Imo State, we know the best neighborhoods, the fairest prices, and the right time to make your move.',
                    'bgImage' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1920&auto=format&fit=crop&q=80',
                ],
            ],
            'stats' => [
                ['icon' => 'home-outline', 'num' => '500+', 'label' => 'Properties Listed'],
                ['icon' => 'people-outline', 'num' => '1,200+', 'label' => 'Happy Clients'],
                ['icon' => 'star-outline', 'num' => '5★', 'label' => 'Service Rating'],
                ['icon' => 'ribbon-outline', 'num' => '10+', 'label' => 'Years Experience'],
            ],
            'sections' => [
                'about' => [
                    'subtitle' => 'About Us',
                    'title'    => "Owerri's Trusted Real Estate Agency",
                    'text'     => 'Biver Royalty Homes Ltd is a real estate agency in Owerri, Imo State, built on integrity. We help clients buy, rent, and sell verified homes within their budget.',
                ],
                'services' => [
                    'subtitle' => 'Our Services',
                    'title'    => 'Our Main Focus',
                ],
                'featured' => [
                    'subtitle' => 'Featured Properties',
                    'title'    => 'Featured Listings',
                ],
                'whyUs' => [
                    'title' => 'We Make Buying a Home<br><em>Simple &amp; Secure</em>',
                    'text'  => 'With over a decade of experience in the Nigerian real estate market, Biver Royalty Homes stands apart through unwavering integrity, personalized service, and deep market knowledge.',
                ],
                'process' => [
                    'subtitle' => 'How It Works',
                    'title'    => 'Find Your Dream Home in 4 Simple Steps',
                ],
                'testimonials' => [
                    'subtitle' => 'Testimonials',
                    'title'    => 'What Our Clients Say',
                ],
                'areas' => [
                    'subtitle' => 'Local Expertise',
                    'title'    => 'Areas We Serve in Owerri & Imo State',
                ],
            ],
            'updatedAt' => null,
        ];
    }

    public static function configPath(): string
    {
        return dirname(__DIR__) . '/' . self::CONFIG_FILE;
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $path = self::configPath();
        if (!is_file($path)) {
            return self::defaults();
        }

        $config = require $path;
        if (!is_array($config)) {
            return self::defaults();
        }

        $merged = array_merge(self::defaults(), $config);
        if (!isset($config['slides']) || !is_array($config['slides'])) {
            $merged['slides'] = self::defaults()['slides'];
        }
        if (!isset($config['stats']) || !is_array($config['stats'])) {
            $merged['stats'] = self::defaults()['stats'];
        }
        if (!isset($config['sections']) || !is_array($config['sections'])) {
            $merged['sections'] = self::defaults()['sections'];
        } else {
            $merged['sections'] = array_replace_recursive(self::defaults()['sections'], $config['sections']);
        }

        return $merged;
    }

    /** @param array<string, mixed> $input */
    public static function save(array $input): bool
    {
        $current = self::get();
        $config = [
            'slides'    => self::sanitizeSlides($input['slides'] ?? $current['slides']),
            'stats'     => self::sanitizeStats($input['stats'] ?? $current['stats']),
            'sections'  => self::sanitizeSections($input['sections'] ?? $current['sections']),
            'updatedAt' => date('c'),
        ];

        $php = "<?php\n"
            . "declare(strict_types=1);\n\n"
            . "/** Auto-generated by Admin → Homepage Content */\n"
            . 'return ' . var_export($config, true) . ";\n";

        return file_put_contents(self::configPath(), $php) !== false;
    }

    /** @param mixed $slides @return list<array<string, string>> */
    private static function sanitizeSlides(mixed $slides): array
    {
        if (!is_array($slides)) {
            return self::defaults()['slides'];
        }

        $out = [];
        foreach ($slides as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $out[] = [
                'eyebrow' => self::clip((string) ($slide['eyebrow'] ?? ''), 120),
                'title'   => self::clip((string) ($slide['title'] ?? ''), 300),
                'tagline' => self::clip((string) ($slide['tagline'] ?? ''), 500),
                'bgImage' => self::clip((string) ($slide['bgImage'] ?? ''), 512),
            ];
        }

        return $out !== [] ? $out : self::defaults()['slides'];
    }

    /** @param mixed $stats @return list<array<string, string>> */
    private static function sanitizeStats(mixed $stats): array
    {
        if (!is_array($stats)) {
            return self::defaults()['stats'];
        }

        $out = [];
        foreach ($stats as $stat) {
            if (!is_array($stat)) {
                continue;
            }
            $out[] = [
                'icon'  => self::clip((string) ($stat['icon'] ?? 'home-outline'), 40),
                'num'   => self::clip((string) ($stat['num'] ?? ''), 20),
                'label' => self::clip((string) ($stat['label'] ?? ''), 80),
            ];
        }

        return $out !== [] ? array_slice($out, 0, 6) : self::defaults()['stats'];
    }

    /** @param mixed $sections @return array<string, array<string, string>> */
    private static function sanitizeSections(mixed $sections): array
    {
        $defaults = self::defaults()['sections'];
        if (!is_array($sections)) {
            return $defaults;
        }

        $out = $defaults;
        foreach ($defaults as $key => $fields) {
            if (!isset($sections[$key]) || !is_array($sections[$key])) {
                continue;
            }
            foreach ($fields as $field => $defaultVal) {
                $out[$key][$field] = self::clip((string) ($sections[$key][$field] ?? $defaultVal), 1000);
            }
        }

        return $out;
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }
}
