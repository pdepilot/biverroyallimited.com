<?php
declare(strict_types=1);

final class PageContentService
{
    private const CONFIG_FILE = 'config/page-content.php';

    /** @return list<string> */
    public static function allowedPages(): array
    {
        return ['about', 'services', 'terms'];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'about' => [
                'hero' => [
                    'subtitle'    => 'EST. 2015',
                    'title'       => 'Architects of <span class="gold-accent">Dreams</span>,<br>Builders of Trust',
                    'description' => 'Biver Royalty Homes wasn\'t built on transactions — it was built on relationships. In a world of empty promises, we chose integrity as our foundation.',
                    'stats'       => [
                        ['num' => '1,200+', 'label' => 'Families Served'],
                        ['num' => '500+', 'label' => 'Properties Sold'],
                        ['num' => '100%', 'label' => 'Client Trust'],
                        ['num' => '10+', 'label' => 'Years of Excellence'],
                    ],
                ],
                'narrative' => [
                    'badge'      => 'The Untold Story',
                    'title'      => 'From a Bold Vision to Nigeria\'s Most Trusted Real Estate Name',
                    'paragraph1' => 'In 2015, Oliva Guiffo saw a gap in Nigeria\'s real estate market — not a gap in properties, but a gap in integrity.',
                    'paragraph2' => 'What started as a one-man mission in Owerri has blossomed into a movement.',
                    'quote'      => 'We don\'t sell houses. We hand over the keys to futures.',
                    'signature'  => 'Mr. Oliva Guiffo, Founder',
                    'mainImage'  => './assets/images/engineer1.png',
                    'floatImage' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=500&auto=format&fit=crop&q=80',
                    'caption'    => 'The Journey Begins in Owerri, 2015',
                ],
                'philosophy' => [
                    'items' => [
                        [
                            'icon'  => 'shield-checkmark-outline',
                            'title' => 'Radical Integrity',
                            'text'  => 'We speak truth even when it costs us a sale. No hidden fees, no misleading listings, no fine print surprises. Just honest guidance.',
                        ],
                        [
                            'icon'  => 'heart-outline',
                            'title' => 'Obsessive Care',
                            'text'  => 'Your dream becomes our mission. We lose sleep so you can rest easy, handling every detail with white-glove precision.',
                        ],
                        [
                            'icon'  => 'star-outline',
                            'title' => 'Unwavering Excellence',
                            'text'  => 'From property sourcing to legal documentation, we obsess over quality. Mediocrity has no place in our vocabulary.',
                        ],
                    ],
                ],
                'journey' => [
                    'eyebrow' => 'Our Journey',
                    'title'   => 'The Road to Redefining Real Estate',
                    'items'   => [
                        ['year' => '2015', 'title' => 'The Seed is Planted', 'text' => 'Biver Royalty Homes opens its doors in a small office on Wetheral Road, Owerri. First property sold within 3 months.'],
                        ['year' => '2017', 'title' => 'Expansion & Recognition', 'text' => 'Named "Most Trusted Real Estate Agency" in Imo State. Team grows to 12 dedicated agents.'],
                        ['year' => '2019', 'title' => 'Digital Transformation', 'text' => 'Launch of comprehensive online platform, making property search accessible to thousands across Nigeria.'],
                        ['year' => '2022', 'title' => '1,000+ Families', 'text' => 'Milestone achievement: 1,000 happy families find their dream homes through Biver Royalty.'],
                        ['year' => '2024', 'title' => 'Industry Leadership', 'text' => 'Recognized as a leading force in Nigerian real estate, setting new standards for integrity and client care.'],
                    ],
                ],
                'values' => [
                    'image' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=600&auto=format&fit=crop&q=80',
                    'items' => [
                        ['icon' => 'people-outline', 'title' => 'People First, Always', 'text' => 'Behind every transaction is a family, a dream, a future. We never forget that.'],
                        ['icon' => 'eye-outline', 'title' => 'Radical Transparency', 'text' => 'Every document shared, every fee explained, every process visible. No secrets. Ever.'],
                        ['icon' => 'infinite-outline', 'title' => 'Lifelong Relationships', 'text' => 'We don\'t close doors — we open them. Many clients return for their second, third, and fourth homes.'],
                        ['icon' => 'cube-outline', 'title' => 'Community Builders', 'text' => 'We\'re not just selling properties; we\'re shaping neighborhoods, strengthening communities.'],
                    ],
                ],
                'team' => [
                    'eyebrow' => 'The Heart Behind the Brand',
                    'title'   => 'Meet the Dream Weavers',
                    'members' => [
                        ['name' => 'Oliva Guiffo', 'role' => 'Founder & CEO', 'image' => './assets/images/engineer1.png'],
                        ['name' => 'Amara Okafor', 'role' => 'Operations Director', 'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&auto=format&fit=crop&q=80'],
                        ['name' => 'Emeka Obi', 'role' => 'Head of Sales', 'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400&auto=format&fit=crop&q=80'],
                        ['name' => 'Chioma Eze', 'role' => 'Client Relations', 'image' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=400&auto=format&fit=crop&q=80'],
                    ],
                ],
                'cta' => [
                    'title' => 'Ready to Write Your Story With Us?',
                    'text'  => 'Every great home begins with a conversation. Let\'s start yours.',
                    'label' => 'Start Your Journey',
                    'link'  => 'contact',
                ],
            ],
            'services' => [
                'hero' => [
                    'badge'       => 'Premium Services',
                    'title'       => 'Comprehensive <span class="gold-accent">Real Estate</span> Solutions',
                    'description' => 'From property acquisition to estate management, we deliver excellence at every step.',
                    'stats'       => [
                        ['num' => '500+', 'label' => 'Properties Sold'],
                        ['num' => '1,200+', 'label' => 'Happy Clients'],
                        ['num' => '98%', 'label' => 'Satisfaction Rate'],
                    ],
                ],
                'showcase' => [
                    'eyebrow' => 'What We Offer',
                    'title'   => 'Tailored Services for Every Need',
                ],
                'process' => [
                    'eyebrow' => 'Simple Process',
                    'title'   => 'How We Deliver Excellence',
                ],
                'whyChoose' => [
                    'eyebrow' => 'Why Choose Us',
                    'title'   => 'The Biver Royalty Advantage',
                ],
                'cta' => [
                    'title' => 'Ready to Start Your Property Journey?',
                    'text'  => 'Let\'s turn your real estate dreams into reality. Contact our expert team today.',
                    'label' => 'Schedule a Consultation',
                    'link'  => 'contact',
                ],
                'cards' => [
                    [
                        'icon' => 'home-outline', 'title' => 'Property Sales',
                        'description' => 'Find your perfect home or investment property with our expert guidance.',
                        'features' => ['Verified Listings', 'Market Valuation', 'Legal Documentation'],
                        'linkLabel' => 'Explore Properties', 'linkPage' => 'property',
                    ],
                    [
                        'icon' => 'key-outline', 'title' => 'Property Rentals',
                        'description' => 'Flexible rental solutions for residential and commercial spaces.',
                        'features' => ['Wide Selection', 'Lease Negotiation', 'Property Inspection'],
                        'linkLabel' => 'Find Rentals', 'linkPage' => 'property',
                    ],
                    [
                        'icon' => 'construct-outline', 'title' => 'Estate Management',
                        'description' => 'Professional management services to maintain your property\'s value.',
                        'features' => ['Tenant Management', 'Maintenance Services', 'Rent Collection'],
                        'linkLabel' => 'Learn More', 'linkPage' => 'contact',
                    ],
                    [
                        'icon' => 'business-outline', 'title' => 'Property Development',
                        'description' => 'End-to-end development from land acquisition to completion.',
                        'features' => ['Land Acquisition', 'Project Management', 'Quality Construction'],
                        'linkLabel' => 'Start a Project', 'linkPage' => 'contact',
                    ],
                    [
                        'icon' => 'document-text-outline', 'title' => 'Legal & Documentation',
                        'description' => 'Complete legal support for all real estate transactions with certified professionals.',
                        'features' => ['Title Verification', 'Contract Drafting', 'Due Diligence'],
                        'linkLabel' => 'Get Consultation', 'linkPage' => 'contact',
                    ],
                    [
                        'icon' => 'map-outline', 'title' => 'Survey & Land Services',
                        'description' => 'Professional survey plans and land documentation for secure property ownership.',
                        'features' => ['Survey Plans', 'Land Verification', 'Site Planning'],
                        'linkLabel' => 'Request Survey', 'linkPage' => 'contact',
                    ],
                ],
            ],
            'terms' => [
                'hero' => [
                    'eyebrow' => 'Legal',
                    'title'   => 'Terms & Conditions',
                    'lead'    => 'Please read these terms carefully before using the Biver Royalty Homes website or services.',
                ],
                'updatedLabel' => 'Last updated',
                'intro' => 'These Terms & Conditions govern your access to and use of the Biver Royalty Homes website, property listings, enquiry forms, and related services operated by Mannavilla Limited / Biver Royalty Homes Ltd (“we”, “us”, “our”). By using our site or engaging our services, you agree to these terms.',
                'sections' => [
                    [
                        'title'   => '1. About us',
                        'content' => "Biver Royalty Homes provides real estate brokerage, rental facilitation, property advisory, and related services primarily in Owerri, Imo State, and across Nigeria.\n\nRegistered office / contact address: No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State.\nEmail: biverroyaltyhomes01@gmail.com\nPhone: +234 903 685 1168",
                    ],
                    [
                        'title'   => '2. Use of this website',
                        'content' => "You may browse listings, submit enquiries, and use tools for lawful personal or business purposes only.\n\nYou agree not to:\n• Misuse forms, upload harmful files, or attempt to disrupt the site\n• Scrape, copy, or republish listing content without written permission\n• Impersonate another person or provide knowingly false information\n• Use the site for fraud, money laundering, or any illegal activity",
                    ],
                    [
                        'title'   => '3. Property information',
                        'content' => "Listings, prices, availability, floor areas, and descriptions are provided for general information and may change without notice. While we take care to present accurate details, we do not warrant that all information is complete, current, or error-free.\n\nYou should independently verify title, survey, planning status, physical condition, and all material facts before committing to any transaction. Site visits and professional due diligence are strongly recommended.",
                    ],
                    [
                        'title'   => '4. Enquiries and communications',
                        'content' => "When you contact us via forms, phone, WhatsApp, email, or chatbot, you consent to us processing your details to respond to your enquiry and to provide related estate services.\n\nWe may keep records of communications for service quality, compliance, and dispute resolution. Marketing messages (if any) will follow applicable consent rules and can be opted out of where required.",
                    ],
                    [
                        'title'   => '5. Transactions and agency role',
                        'content' => "Unless a separate written agreement states otherwise, Biver Royalty Homes acts as an intermediary or facilitator. Sale, purchase, lease, and payment obligations are between the relevant parties (buyer/seller or landlord/tenant) and are governed by contracts signed by those parties.\n\nFees, commissions, deposits, and refund terms (if applicable) will be disclosed in writing before you are bound. Receipts or certificates issued by us document specific transactions and do not replace legal conveyancing or tenancy instruments prepared by qualified professionals where required.",
                    ],
                    [
                        'title'   => '6. User-submitted content',
                        'content' => "If you list a property or submit documents/photos, you confirm you have the right to provide them and that they are accurate and lawful. You grant us a non-exclusive licence to display and use that material to market and administer the listing.\n\nWe may refuse, edit, or remove submissions that appear misleading, infringing, or inappropriate.",
                    ],
                    [
                        'title'   => '7. Intellectual property',
                        'content' => "Website design, branding, logos, copy, and original photography remain our property or that of our licensors. You may not reproduce them for commercial use without prior written consent.",
                    ],
                    [
                        'title'   => '8. Third-party links and tools',
                        'content' => "Our site may link to third-party websites, maps, payment providers, or social platforms. We are not responsible for their content, policies, or availability. Use of third-party services is at your own risk and subject to their terms.",
                    ],
                    [
                        'title'   => '9. Limitation of liability',
                        'content' => "To the fullest extent permitted by Nigerian law, we are not liable for indirect, incidental, or consequential losses arising from use of the website or reliance on listing information, except where loss is caused by our fraud or wilful misconduct.\n\nNothing in these terms excludes liability that cannot legally be excluded.",
                    ],
                    [
                        'title'   => '10. Privacy',
                        'content' => "How we collect and use personal data is described in our practices shared through the site (including cookie/consent tools where applicable). By using the site you acknowledge that processing as needed to operate enquiries and services may occur.",
                    ],
                    [
                        'title'   => '11. Changes',
                        'content' => "We may update these Terms & Conditions from time to time. The “Last updated” date on this page will change when revisions are published. Continued use of the site after changes constitutes acceptance of the revised terms.",
                    ],
                    [
                        'title'   => '12. Governing law',
                        'content' => "These terms are governed by the laws of the Federal Republic of Nigeria. Disputes shall be subject to the competent courts in Nigeria, without prejudice to any mandatory consumer protections that apply.",
                    ],
                    [
                        'title'   => '13. Contact',
                        'content' => "Questions about these Terms & Conditions:\nEmail: biverroyaltyhomes01@gmail.com\nPhone: +234 903 685 1168\nAddress: No. 31 Wetheral Road, Angelina Plaza, Owerri, Imo State.",
                    ],
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

        return array_replace_recursive(self::defaults(), $config);
    }

    /** @return array<string, mixed> */
    public static function getPage(string $page): array
    {
        $all = self::get();
        $page = strtolower(trim($page));

        return is_array($all[$page] ?? null) ? $all[$page] : [];
    }

    /** @param array<string, mixed> $input */
    public static function savePage(string $page, array $input): bool
    {
        $page = strtolower(trim($page));
        if (!in_array($page, self::allowedPages(), true)) {
            throw new InvalidArgumentException('Invalid page slug.');
        }

        $all = self::get();
        $defaults = self::defaults()[$page] ?? [];
        $merged = array_replace_recursive($defaults, $input);

        // List fields must come from the payload so removals stick (array_replace_recursive keeps extras).
        if ($page === 'about') {
            if (isset($input['hero']['stats']) && is_array($input['hero']['stats'])) {
                $merged['hero']['stats'] = $input['hero']['stats'];
            }
            if (isset($input['philosophy']['items']) && is_array($input['philosophy']['items'])) {
                $merged['philosophy']['items'] = $input['philosophy']['items'];
            }
            if (isset($input['journey']['items']) && is_array($input['journey']['items'])) {
                $merged['journey']['items'] = $input['journey']['items'];
            }
            if (isset($input['values']['items']) && is_array($input['values']['items'])) {
                $merged['values']['items'] = $input['values']['items'];
            }
            if (isset($input['team']['members']) && is_array($input['team']['members'])) {
                $merged['team']['members'] = $input['team']['members'];
            }
        }
        if ($page === 'terms' && isset($input['sections']) && is_array($input['sections'])) {
            $merged['sections'] = $input['sections'];
        }
        if ($page === 'services' && isset($input['cards']) && is_array($input['cards'])) {
            $merged['cards'] = $input['cards'];
        }

        $all[$page] = self::sanitizePage($page, $merged);
        $all['updatedAt'] = date('c');

        $dir = dirname(self::configPath());
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create config directory.');
        }

        $php = "<?php\n"
            . "declare(strict_types=1);\n\n"
            . "/** Auto-generated by Admin → Site Pages */\n"
            . 'return ' . var_export($all, true) . ";\n";

        return file_put_contents(self::configPath(), $php) !== false;
    }

    /**
     * Light sanitization / structure guards per page.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function sanitizePage(string $page, array $input): array
    {
        if ($page === 'about') {
            $input['hero']['stats'] = self::listOfMaps($input['hero']['stats'] ?? [], ['num', 'label']);
            $input['philosophy']['items'] = self::listOfMaps($input['philosophy']['items'] ?? [], ['icon', 'title', 'text']);
            $input['journey']['items'] = self::listOfMaps($input['journey']['items'] ?? [], ['year', 'title', 'text']);
            $input['values']['items'] = self::listOfMaps($input['values']['items'] ?? [], ['icon', 'title', 'text']);
            $input['team']['members'] = self::listOfMaps($input['team']['members'] ?? [], ['name', 'role', 'image']);
        }

        if ($page === 'terms') {
            $input['sections'] = self::listOfMaps($input['sections'] ?? [], ['title', 'content']);
            $input['intro'] = (string) ($input['intro'] ?? '');
            $input['updatedLabel'] = (string) ($input['updatedLabel'] ?? 'Last updated');
        }

        if ($page === 'services' && isset($input['cards']) && is_array($input['cards'])) {
            $cards = [];
            foreach ($input['cards'] as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $features = $card['features'] ?? [];
                if (is_string($features)) {
                    $features = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $features) ?: [])));
                }
                $cards[] = [
                    'icon'        => (string) ($card['icon'] ?? 'home-outline'),
                    'title'       => (string) ($card['title'] ?? ''),
                    'description' => (string) ($card['description'] ?? ''),
                    'features'    => is_array($features) ? array_values(array_map('strval', $features)) : [],
                    'linkLabel'   => (string) ($card['linkLabel'] ?? 'Learn More'),
                    'linkPage'    => (string) ($card['linkPage'] ?? 'contact'),
                ];
            }
            $input['cards'] = $cards;
        }

        return $input;
    }

    /**
     * @param mixed $list
     * @param list<string> $keys
     * @return list<array<string, string>>
     */
    private static function listOfMaps($list, array $keys): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($keys as $key) {
                $item[$key] = (string) ($row[$key] ?? '');
            }
            $out[] = $item;
        }

        return $out;
    }
}
