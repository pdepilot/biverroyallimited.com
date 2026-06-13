<?php
/**
 * Intent recognition and conversational response engine.
 * Priority: Intent → FAQ → Knowledge Base → Real-estate fallback → Escalation
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/chatbot-config.php';
require_once __DIR__ . '/ChatbotRepository.php';
require_once dirname(dirname(__DIR__)) . '/includes/PropertyRepository.php';
require_once dirname(dirname(__DIR__)) . '/includes/chatbot_helpers.php';
require_once dirname(dirname(__DIR__)) . '/includes/site_paths.php';

class ChatbotEngine
{
    private ChatbotRepository $repo;
    private array $siteConfig;

    /** Core vocabulary indicating a real-estate-related question. */
    private const REAL_ESTATE_SIGNALS = [
        'property', 'properties', 'house', 'houses', 'home', 'homes', 'land', 'plot', 'plots',
        'rent', 'rental', 'lease', 'leasing', 'tenant', 'landlord', 'buy', 'buying', 'sell', 'selling',
        'purchase', 'sale', 'mortgage', 'loan', 'financing', 'installment', 'payment plan',
        'apartment', 'flat', 'duplex', 'bungalow', 'mansion', 'villa', 'estate', 'estates',
        'commercial', 'residential', 'investment', 'invest', 'roi', 'yield',
        'title', 'deed', 'c of o', 'certificate of occupancy', 'survey', 'documentation', 'legal',
        'inspection', 'viewing', 'valuation', 'appraisal', 'agent', 'broker', 'realtor',
        'bedroom', 'bathroom', 'sqm', 'square meter', 'acre', 'hectare', 'location', 'owerri', 'imo',
        'lagos', 'abuja', 'nigeria', 'port harcourt', 'rivers', 'enugu', 'ph', 'fct', 'diaspora',
        'c of o', 'governor consent', 'deed of assignment', 'survey plan', 'gazette', 'excision', 'omonile',
        'tenancy', 'advance rent', 'service charge', 'estate dues', 'stamp duty', 'mortgage', 'fmbn',
        'listing', 'listings', 'market', 'price', 'pricing', 'cost', 'budget', 'naira', 'million',
        'luxury', 'premium', 'affordable', 'furnished', 'unfurnished', 'amenities', 'parking',
        'developer', 'construction', 'off-plan', 'built', 'move in', 'down payment', 'deposit',
        'commission', 'agency', 'real estate', 'realestate', 'biver', 'royalty',
    ];

    /** Removed before matching so "please show me houses" still matches property intent. */
    private const PLEASANTRY_PHRASES = [
        'please', 'kindly', 'could you', 'can you', 'would you', 'will you',
        'i would like', 'i want to', 'i need to', 'i am looking for', 'i\'m looking for',
        'tell me', 'let me know', 'help me', 'assist me', 'i was wondering',
        'do you', 'does your', 'is it possible', 'may i', 'might i',
    ];

    private const HUMAN_OPENERS = [
        'Certainly!',
        'Of course!',
        'Absolutely!',
        'I\'d be happy to help with that.',
        'Great question!',
        'That\'s a very good question.',
    ];

    public function __construct(?ChatbotRepository $repo = null)
    {
        $this->repo = $repo ?? new ChatbotRepository();
        $this->siteConfig = chatbotSiteConfig();
    }

    /**
     * @return array{response: string, source: string, confidence: float, intent: ?string, escalate: bool, metadata?: array}
     */
    public function processMessage(string $message, array $context = []): array
    {
        $normalized = $this->normalizeText($message);
        $forMatching = $this->stripPleasantries($normalized);

        if ($normalized === '') {
            return $this->wrapResponse(
                'Please go ahead and type your question — I\'m right here to help with anything property-related.',
                'system',
                1.0,
                null,
                false
            );
        }

        if ($this->isPoliteOnly($forMatching)) {
            return $this->wrapResponse(
                $this->humanize('You\'re very welcome! If you need information about properties, pricing, investments, rentals, or land acquisition, I\'m here to help.', 'thanks'),
                'intent',
                0.95,
                'thanks',
                false
            );
        }

        $greeting = $this->matchGreetingResponse($forMatching, $normalized);
        if ($greeting !== null) {
            return $greeting;
        }

        $propertySearch = $this->tryPropertySearch($forMatching);
        if ($propertySearch !== null) {
            return $this->applyHumanTone($propertySearch, 'property_search');
        }

        $intentResult = $this->matchIntent($forMatching);

        if ($intentResult !== null) {
            $meetsThreshold = $intentResult['confidence'] >= $intentResult['threshold'];
            $closeEnough = $intentResult['confidence'] >= 0.28;

            if ($meetsThreshold || $closeEnough) {
                $dynamic = $this->handleIntent($intentResult['intent_key'], $forMatching, $context);

                if ($dynamic !== null) {
                    return $this->applyHumanTone($dynamic, $intentResult['intent_key']);
                }

                $response = $this->pickResponse($intentResult['responses']);
                if ($response !== null) {
                    return $this->wrapResponse(
                        $this->humanize($response, $intentResult['intent_key']),
                        'intent',
                        $intentResult['confidence'],
                        $intentResult['intent_key'],
                        false
                    );
                }
            }
        }

        $isRealEstate = $this->isRealEstateTopic($forMatching);

        $faqResult = $this->searchFaqs($forMatching, $isRealEstate);
        if ($faqResult !== null) {
            return $this->wrapResponse(
                $this->humanize($faqResult['answer'], 'faq'),
                'faq',
                $faqResult['score'],
                null,
                false,
                ['faq_id' => $faqResult['id']]
            );
        }

        $kbResult = $this->searchKnowledgeBase($forMatching, $isRealEstate);
        if ($kbResult !== null) {
            $content = $this->formatKnowledgeArticle($kbResult);
            return $this->wrapResponse(
                $this->humanize($content, 'knowledge'),
                'knowledgebase',
                $kbResult['score'],
                null,
                false,
                ['kb_id' => $kbResult['id'], 'title' => $kbResult['title']]
            );
        }

        if ($isRealEstate) {
            $fallback = $this->handleRealEstateFallback($forMatching);
            if ($fallback !== null) {
                return $fallback;
            }

            return $this->wrapResponse(
                "I couldn't find a reliable answer to that question in my knowledge base.\n\n"
                . "Would you like assistance from our support team? You can stay in this chat — no need to leave the website.",
                'fallback',
                0.0,
                null,
                false,
                [
                    'show_support_options'   => true,
                    'show_escalation_button' => true,
                    'offer_human_support'    => true,
                    'original_message'       => $message,
                ]
            );
        }

        return $this->wrapResponse(
            "I didn't quite understand that. I'm the **Biver Royalty Homes** property assistant — I can help with buying, renting, selling, or investing in property, land, prices in **₦**, inspections, and documentation.\n\n"
            . "Please try rephrasing your question, for example: \"Do you have 3-bedroom houses for rent in Owerri?\" or \"What documents do I need to buy land?\"",
            'clarify',
            0.0,
            null,
            false
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchGreetingResponse(string $forMatching, string $normalized): ?array
    {
        if (preg_match('/\b(thank you|thanks|thank u|thx)\b/u', $forMatching) && !preg_match('/\b(buy|rent|property|house|land|price)\b/u', $forMatching)) {
            return $this->wrapResponse(
                $this->humanize('You\'re welcome. If you need information about properties, pricing, investments, rentals, or land acquisition, I\'m here to help.', 'thanks'),
                'intent',
                0.95,
                'thanks',
                false
            );
        }

        if (preg_match('/\b(bye|goodbye|see you|see ya|later)\b/u', $forMatching)) {
            return $this->wrapResponse(
                'Goodbye! Thank you for visiting Biver Royalty Homes. We hope to assist you again soon.',
                'intent',
                0.95,
                'farewell',
                false
            );
        }

        if (preg_match('/\b(nice to meet you|pleased to meet)\b/u', $forMatching)) {
            return $this->wrapResponse(
                'Nice to meet you too! I\'m the Biver Royalty Homes assistant — ask me anything about buying, renting, selling, or investing in property.',
                'intent',
                0.92,
                'greetings',
                false
            );
        }

        if (preg_match('/^(hi|hello|hey|hiya)\b/u', $forMatching)
            || preg_match('/\b(good morning|good afternoon|good evening)\b/u', $forMatching)
            || preg_match('/\b(how are you|how\'s it going|what\'s up|whats up)\b/u', $forMatching)
            || preg_match('/^(can you help|help me)\b/u', $forMatching)
        ) {
            if (preg_match('/\b(how are you|how\'s it going)\b/u', $forMatching)) {
                return $this->wrapResponse(
                    'I\'m doing well, thank you for asking! I\'m here and ready to help with properties, land, rentals, pricing, or anything else about real estate at Biver Royalty Homes. What would you like to know?',
                    'intent',
                    0.95,
                    'greetings',
                    false
                );
            }

            return $this->wrapResponse(
                "Hello 👋 Welcome to Biver Royalty Homes.\n\nHow can I assist you today?\nAre you looking to **buy**, **rent**, **sell**, **invest**, or learn more about real estate?",
                'intent',
                0.96,
                'greetings',
                false
            );
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryPropertySearch(string $text): ?array
    {
        $wantsList = preg_match(
            '/\b(show|list|find|search|available|recommend|cheapest|luxury|commercial|under|between|budget|properties|houses|apartment|flat|duplex|land for)\b/ui',
            $text
        );
        if (!$wantsList && !chatbot_parse_budget($text)) {
            return null;
        }

        $filters = ['limit' => 8];
        $budget = chatbot_parse_budget($text);
        if ($budget) {
            $filters['minPrice'] = $budget['min'];
            $filters['maxPrice'] = $budget['max'];
        }

        $locations = chatbot_extract_locations($text);
        if ($locations !== []) {
            $filters['locations'] = $locations;
        } else {
            $loc = $this->extractLocationFromText($text);
            if ($loc) {
                $filters['search'] = $loc;
            }
        }

        if (preg_match('/\b(rent|rental|lease)\b/ui', $text)) {
            $filters['type'] = 'rent';
        } elseif (preg_match('/\b(buy|sale|purchase|for sale)\b/ui', $text)) {
            $filters['type'] = 'sale';
        }

        if (preg_match('/\b(land|plot)\b/ui', $text)) {
            $filters['search'] = trim(($filters['search'] ?? '') . ' land');
        }

        if (preg_match('/\b(luxury|premium|executive|mansion)\b/ui', $text)) {
            $filters['luxury'] = true;
        }

        if (preg_match('/\b(commercial|office|shop|warehouse)\b/ui', $text)) {
            $filters['commercial'] = true;
        }

        if (preg_match('/\b(cheapest|lowest|affordable)\b/ui', $text)) {
            $filters['cheapest'] = true;
        }

        if (preg_match('/\b(investment|invest)\b/ui', $text) && empty($filters['type'])) {
            $filters['type'] = 'sale';
        }

        try {
            $properties = PropertyRepository::searchForChatbot($filters);
        } catch (Throwable) {
            return null;
        }

        if ($properties === []) {
            return null;
        }

        return $this->buildPropertyListResponse($properties, 'property_search');
    }

    /**
     * @param list<array<string, mixed>> $properties
     * @return array<string, mixed>
     */
    private function buildPropertyListResponse(array $properties, string $intentKey): array
    {
        $lines = ["Here are available properties:\n"];
        foreach ($properties as $prop) {
            $rentSuffix = ($prop['type'] ?? '') === 'rent' ? '/month' : '';
            $beds = ($prop['bedrooms'] ?? 0) > 0 ? ' — ' . (int) $prop['bedrooms'] . ' bed' : '';
            $lines[] = sprintf(
                '• %s%s — %s',
                $prop['title'],
                $beds,
                chatbot_format_naira((int) $prop['price']) . $rentSuffix
            );
        }
        $lines[] = "\nWould you like more information on any of these properties?";
        $detailUrl = function_exists('pageUrl') ? pageUrl('property.php') : '/property.php';

        return $this->buildIntentResponse(
            implode("\n", $lines),
            $intentKey,
            0.92,
            ['property_ids' => array_column($properties, 'id'), 'property_page' => $detailUrl]
        );
    }

    private function stripPleasantries(string $text): string
    {
        $out = $text;
        foreach (self::PLEASANTRY_PHRASES as $phrase) {
            $out = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/u', ' ', $out) ?? $out;
        }
        return trim(preg_replace('/\s+/u', ' ', $out) ?? '');
    }

    private function isPoliteOnly(string $text): bool
    {
        $stripped = preg_replace(
            '/\b(please|thanks|thank you|thank u|thx|ok|okay|yes|no|sure|alright|fine|lovely|great)\b/u',
            '',
            $text
        ) ?? $text;
        return trim($stripped) === '';
    }

    private function isRealEstateTopic(string $text): bool
    {
        $hits = 0;
        foreach (self::REAL_ESTATE_SIGNALS as $signal) {
            if (str_contains($text, $signal)) {
                $hits++;
            }
        }
        if ($hits >= 1) {
            return true;
        }

        if (preg_match('/\b(how|what|where|when|which|can|do|does|is|are|any)\b/u', $text)) {
            return preg_match('/\b(house|home|land|rent|buy|sell|property|flat|apartment)\b/u', $text) === 1;
        }

        return false;
    }

    /**
     * @return array{intent_key: string, confidence: float, threshold: float, responses: list<array>}|null
     */
    private function matchIntent(string $text): ?array
    {
        $intents = $this->repo->getActiveIntents();
        $tokens = $this->tokenize($text);
        $best = null;
        $bestScore = 0.0;

        foreach ($intents as $intent) {
            $score = $this->scoreKeywords($text, $tokens, $intent['keywords'] ?? []);
            $priorityBoost = ((int) ($intent['priority'] ?? 50)) / 500;
            $score = min(1.0, $score + $priorityBoost);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'intent_key'  => $intent['intent_key'],
                    'confidence'  => $score,
                    'threshold'   => (float) ($intent['confidence_threshold'] ?? CHATBOT_DEFAULT_CONFIDENCE),
                    'responses'   => $intent['responses'] ?? [],
                ];
            }
        }

        return $best;
    }

    /**
     * @param list<string> $keywords
     */
    private function scoreKeywords(string $text, array $tokens, array $keywords): float
    {
        if ($keywords === []) {
            return 0.0;
        }

        $matches = 0.0;
        $totalWeight = 0.0;

        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower(trim((string) $keyword));
            if ($keyword === '') {
                continue;
            }

            $weight = str_contains($keyword, ' ') ? 2.5 : 1.0;
            $totalWeight += $weight;

            if (str_contains($text, $keyword)) {
                $matches += $weight;
                continue;
            }

            $keywordTokens = $this->tokenize($keyword);
            if ($keywordTokens === []) {
                continue;
            }
            $tokenHits = count(array_intersect($tokens, $keywordTokens));
            if ($tokenHits > 0) {
                $matches += ($tokenHits / count($keywordTokens)) * $weight * 0.85;
            }
        }

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $ratio = $matches / $totalWeight;

        if (count($tokens) >= 3) {
            $overlap = 0;
            foreach ($keywords as $keyword) {
                foreach ($this->tokenize((string) $keyword) as $kt) {
                    if (in_array($kt, $tokens, true)) {
                        $overlap++;
                    }
                }
            }
            $ratio = max($ratio, min(0.95, $overlap / max(1, count($tokens)) * 1.5));
        }

        return min(1.0, $ratio);
    }

    private function handleIntent(string $intentKey, string $text, array $context): ?array
    {
        return match ($intentKey) {
            'greetings'         => $this->handleGreetingIntent($text),
            'thanks'            => $this->handleThanksIntent(),
            'farewell'          => $this->handleFarewellIntent(),
            'please_ack'        => $this->handlePleaseIntent($text),
            'property_purchase' => $this->handlePropertyIntent('sale', $text),
            'land_purchase'     => $this->handleLandIntent($text),
            'rental_properties' => $this->handlePropertyIntent('rent', $text),
            'sell_property'     => $this->handleSellIntent(),
            'investment'        => $this->handleInvestmentIntent(),
            'mortgage_financing'=> $this->handleMortgageIntent(),
            'legal_docs'        => $this->handleLegalIntent(),
            'luxury_homes'      => $this->handleLuxuryIntent($text),
            'location'          => $this->handleLocationIntent(),
            'contact'           => $this->handleContactIntent(),
            'inspection'        => $this->handleInspectionIntent(),
            'pricing'           => $this->handlePricingIntent($text),
            'company_info'      => $this->handleCompanyIntent(),
            'help'              => $this->handleHelpIntent(),
            default             => null,
        };
    }

    private function handleGreetingIntent(string $text): ?array
    {
        if (preg_match('/how are you|how\'s it going|how do you do/u', $text)) {
            return $this->buildIntentResponse(
                'I\'m doing well, thank you for asking! I\'m here and ready to help you with properties, land, rentals, pricing, or anything else about real estate at Biver Royalty Homes. What would you like to know?',
                'greetings',
                0.95
            );
        }

        return null;
    }

    private function handleThanksIntent(): ?array
    {
        return null;
    }

    private function handleFarewellIntent(): ?array
    {
        return null;
    }

    private function handlePleaseIntent(string $text): array
    {
        if ($this->isRealEstateTopic($text)) {
            return $this->buildIntentResponse(
                'Of course — I\'d be glad to help. Could you share a bit more detail about what you\'re looking for (location, budget, or property type)? That way I can give you the most useful answer.',
                'please_ack',
                0.88
            );
        }

        return $this->buildIntentResponse(
            'Of course! How may I assist you today? You can ask about properties for sale, land, rentals, pricing, inspections, or our services.',
            'please_ack',
            0.9
        );
    }

    private function handleHelpIntent(): array
    {
        return $this->buildIntentResponse(
            "I can help you with:\n\n" .
            "• Homes and properties for **sale** or **rent**\n" .
            "• **Land** and plot acquisition\n" .
            "• **Pricing**, payment plans, and budgets\n" .
            "• **Inspections** and site visits\n" .
            "• **Documentation**, titles, and due diligence\n" .
            "• **Investment** and luxury property guidance\n" .
            "• Our **office location** and contact details\n\n" .
            "Just ask naturally — for example, \"Do you have 3-bedroom houses in Owerri?\" or \"How do I book an inspection?\"",
            'help',
            0.92
        );
    }

    private function handleSellIntent(): array
    {
        return $this->buildIntentResponse(
            "If you'd like to **sell or list** your property with us, we'd love to hear from you.\n\n" .
            "Visit our **List Your Property** page on the website, or share your property details here (location, type, and your phone number). Our team will review it and contact you to discuss listing, marketing, and the sales process.\n\n" .
            "You can also call us at {$this->siteConfig['contactPhone']}.",
            'sell_property',
            0.9
        );
    }

    private function handleInvestmentIntent(): array
    {
        return $this->buildIntentResponse(
            "Real estate investment is one of our core strengths at Biver Royalty Homes.\n\n" .
            "We guide clients toward properties and land with strong growth potential in Owerri, Imo State, and select locations nationwide. We support you with market insight, verified titles, site visits, and documentation.\n\n" .
            "Tell me your budget range and whether you prefer land, residential, or commercial assets — I can point you toward suitable opportunities or arrange a consultation.",
            'investment',
            0.9
        );
    }

    private function handleMortgageIntent(): array
    {
        return $this->buildIntentResponse(
            "Many buyers ask about **financing and payment plans**. While we are not a bank, we work with clients on structured payment options for selected properties and developments.\n\n" .
            "Flexible instalment plans may be available depending on the property. Share the listing you're interested in and your budget, and our consultants will explain what payment structures are possible.\n\n" .
            "For mortgage loans, we can also advise you on typical documentation banks require in Nigeria.",
            'mortgage_financing',
            0.88
        );
    }

    private function handleLegalIntent(): array
    {
        return $this->buildIntentResponse(
            "Proper **documentation** is essential for any property or land purchase.\n\n" .
            "Our team assists with title verification, survey plans, Certificate of Occupancy (C of O) guidance, and due diligence before you commit funds. We coordinate with legal professionals where needed.\n\n" .
            "Never pay for a property without verifying the title. Would you like to schedule a consultation or inspection?",
            'legal_docs',
            0.9
        );
    }

    private function handleLuxuryIntent(string $text): array
    {
        $properties = PropertyRepository::getPublic(5, 'sale', $this->extractLocationFromText($text));

        if ($properties !== []) {
            $lines = ["Here are some premium listings that may interest you:\n"];
            foreach ($properties as $prop) {
                $lines[] = sprintf(
                    '• %s — %s, %s',
                    $prop['title'],
                    $prop['location'],
                    chatbot_format_naira((int) $prop['price'])
                );
            }
            $lines[] = "\nI can arrange a private viewing if any of these appeal to you.";
            return $this->buildIntentResponse(implode("\n", $lines), 'luxury_homes', 0.9);
        }

        return $this->buildIntentResponse(
            "Biver Royalty Homes specialises in **premium and luxury properties** — executive homes, upscale estates, and high-value land.\n\n" .
            "Tell me your preferred location and budget, and our consultants will curate options for you. We can also arrange private inspections at your convenience.",
            'luxury_homes',
            0.88
        );
    }

    private function handlePropertyIntent(string $type, string $text): array
    {
        $search = $this->extractLocationFromText($text);
        $properties = PropertyRepository::getPublic(5, $type, $search);

        $typeLabel = $type === 'rent' ? 'rental' : 'sale';

        if ($properties === []) {
            $msg = $type === 'rent'
                ? "I've checked our current listings — we have limited rentals showing online right now, but our team often has additional options available.\n\nPlease share your preferred location, number of bedrooms, and budget, and we'll match you with suitable rental properties. You can also call {$this->siteConfig['contactPhone']}."
                : "I'd love to help you find the right property.\n\nBrowse our **Properties** page on the website, or tell me your preferred location, budget, and property type (e.g. duplex, flat, land). Our consultants will recommend options and can arrange inspections.\n\nCall us anytime at {$this->siteConfig['contactPhone']}.";

            return $this->buildIntentResponse($msg, $type === 'rent' ? 'rental_properties' : 'property_purchase', 0.85);
        }

        $lines = ["Here are some properties we currently have available for {$typeLabel}:\n"];
        foreach ($properties as $prop) {
            $rentSuffix = $type === 'rent' ? '/month' : '';
            $lines[] = sprintf(
                '• %s — %s, %s%s',
                $prop['title'],
                $prop['location'],
                chatbot_format_naira((int) $prop['price']) . $rentSuffix,
                $prop['bedrooms'] > 0 ? " ({$prop['bedrooms']} bed)" : ''
            );
        }
        $lines[] = "\nWould you like to schedule a viewing or speak with a consultant about any of these?";

        return $this->buildIntentResponse(implode("\n", $lines), $type === 'rent' ? 'rental_properties' : 'property_purchase', 0.9);
    }

    private function handleLandIntent(string $text): array
    {
        $properties = PropertyRepository::getPublic(5, 'sale', 'land');

        if ($properties === []) {
            $properties = array_filter(
                PropertyRepository::getPublic(8, 'sale'),
                static fn ($p) => preg_match('/land|plot|acre/i', ($p['title'] ?? '') . ' ' . ($p['propertyCategory'] ?? ''))
            );
            $properties = array_slice(array_values($properties), 0, 5);
        }

        if ($properties === []) {
            return $this->buildIntentResponse(
                "We offer **verified land and plots** in strategic locations across Owerri and Imo State.\n\n" .
                "Our team handles title checks, survey coordination, and flexible acquisition options. Share your preferred area, plot size, and budget — or call {$this->siteConfig['contactPhone']} to speak with a land specialist.",
                'land_purchase',
                0.88
            );
        }

        $lines = ["Here are some land and plot opportunities available now:\n"];
        foreach ($properties as $prop) {
            $lines[] = sprintf('• %s — %s, %s', $prop['title'], $prop['location'], chatbot_format_naira((int) $prop['price']));
        }
        $lines[] = "\nWould you like to arrange a site visit?";

        return $this->buildIntentResponse(implode("\n", $lines), 'land_purchase', 0.9);
    }

    private function handleLocationIntent(): array
    {
        $address = $this->siteConfig['address'];
        $hours = $this->siteConfig['businessHours'] ?? 'Monday – Saturday: 8:00 AM – 6:00 PM';

        return $this->buildIntentResponse(
            "You can find us here:\n📍 {$address}\n\n🕐 {$hours}\n\nWe serve clients across Owerri, Imo State, and selected locations nationwide. Would you like directions or to book an appointment?",
            'location',
            0.95
        );
    }

    private function handleContactIntent(): array
    {
        $phone = $this->siteConfig['contactPhone'];
        $email = $this->siteConfig['contactEmail'];

        return $this->buildIntentResponse(
            "Here's how to reach our team:\n\n" .
            "📞 {$phone}\n" .
            "📧 {$email}\n\n" .
            "You can also tap **Request Human Response** in this chat — a consultant will reply here without leaving the website.",
            'contact',
            0.95
        );
    }

    private function handleInspectionIntent(): array
    {
        return $this->buildIntentResponse(
            "I'd be delighted to help you **book an inspection**.\n\n" .
            "Please share:\n1. The property or area you're interested in\n2. Your preferred date and time\n3. Your name and phone number\n\n" .
            "You can also use the **Book Inspection** button in this chat, or call {$this->siteConfig['contactPhone']}. An agent will confirm your appointment.",
            'inspection',
            0.92,
            ['requires_inspection_form' => true]
        );
    }

    private function handlePricingIntent(string $text): array
    {
        $properties = PropertyRepository::getPublic(3, null, $this->extractLocationFromText($text));

        $msg = "Property prices depend on location, size, finishing, and property type — we have options from starter homes to luxury estates.\n\n";

        if ($properties !== []) {
            $msg .= "Here are a few current guide prices from our listings:\n";
            foreach ($properties as $prop) {
                $msg .= sprintf("• %s — from %s\n", $prop['title'], chatbot_format_naira((int) $prop['price']));
            }
            $msg .= "\n";
        }

        $msg .= "Share your budget and what you're looking for (buy, rent, or land), and we'll recommend the best matches. Flexible payment plans may be available on selected properties.";

        return $this->buildIntentResponse($msg, 'pricing', 0.88);
    }

    private function handleCompanyIntent(): array
    {
        $about = $this->siteConfig['aboutText'];
        $name = $this->siteConfig['siteName'];

        return $this->buildIntentResponse(
            "{$name} — {$about}\n\nWe help clients buy, sell, rent, and invest in property with honesty and professionalism. Whether you need a family home, land, a rental, or investment advice — we're here for you.",
            'company_info',
            0.9
        );
    }

    /**
     * @return array{answer: string, score: float, id: int}|null
     */
    private function searchFaqs(string $text, bool $isRealEstate): ?array
    {
        $faqs = $this->repo->getActiveFaqs();
        $tokens = $this->tokenize($text);
        $best = null;
        $bestScore = 0.0;

        foreach ($faqs as $faq) {
            $questionScore = $this->scoreKeywords($text, $tokens, $this->tokenize($faq['question']));
            $keywordScore = $this->scoreKeywords($text, $tokens, $faq['keywords'] ?? []);
            $answerScore = $this->scoreKeywords($text, $tokens, array_slice($this->tokenize($faq['answer']), 0, 40)) * 0.5;
            $score = max($questionScore * 1.3, $keywordScore, $answerScore);

            $threshold = (float) ($faq['match_score_threshold'] ?? 0.4);
            if ($isRealEstate) {
                $threshold *= 0.65;
            }

            if ($score >= $threshold && $score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'id'     => (int) $faq['id'],
                    'answer' => $faq['answer'],
                    'score'  => min(1.0, $score),
                ];
            }
        }

        return $best;
    }

    /**
     * @return array{content: string, score: float, id: int, title: string}|null
     */
    private function searchKnowledgeBase(string $text, bool $isRealEstate): ?array
    {
        $articles = $this->repo->getActiveKnowledgeBase();
        $tokens = $this->tokenize($text);
        $best = null;
        $bestScore = 0.0;

        foreach ($articles as $article) {
            $titleScore = $this->scoreKeywords($text, $tokens, $this->tokenize($article['title']));
            $keywordScore = $this->scoreKeywords($text, $tokens, $article['keywords'] ?? []);
            $contentTokens = array_slice($this->tokenize($article['content']), 0, 80);
            $contentScore = $this->scoreKeywords($text, $tokens, $contentTokens) * 0.75;

            $score = max($titleScore * 1.4, $keywordScore * 1.1, $contentScore);
            $threshold = (float) ($article['match_score_threshold'] ?? 0.35);
            if ($isRealEstate) {
                $threshold *= 0.6;
            }

            if ($score >= $threshold && $score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'id'      => (int) $article['id'],
                    'title'   => $article['title'],
                    'content' => $article['content'],
                    'score'   => min(1.0, $score),
                ];
            }
        }

        return $best;
    }

    /**
     * @param array{title: string, content: string} $article
     */
    private function formatKnowledgeArticle(array $article): string
    {
        $title = $article['title'];
        $content = $article['content'];

        if (!str_contains(mb_strtolower($content), mb_strtolower($title))) {
            return $title . "\n\n" . $content;
        }

        return $content;
    }

    /**
     * Helpful answer when topic is real estate but no exact FAQ/KB match.
     *
     * @return array|null
     */
    private function handleRealEstateFallback(string $text): ?array
    {
        $buckets = [
            'rent|lease|tenant|landlord' => 'rental_properties',
            'land|plot|acre|hectare' => 'land_purchase',
            'sell|list my|listing my' => 'sell_property',
            'invest|roi|return' => 'investment',
            'mortgage|loan|financ|installment|payment plan' => 'mortgage_financing',
            'title|deed|survey|document|legal|c of o' => 'legal_docs',
            'luxury|premium|executive|mansion' => 'luxury_homes',
            'inspect|viewing|visit|see the' => 'inspection',
            'price|cost|budget|how much|afford' => 'pricing',
            'where|location|address|office|owerri' => 'location',
            'phone|email|contact|call|whatsapp' => 'contact',
            'buy|purchase|house|home|apartment|duplex|flat' => 'property_purchase',
        ];

        foreach ($buckets as $pattern => $intentKey) {
            if (preg_match('/\b(' . $pattern . ')\b/ui', $text)) {
                $handled = $this->handleIntent($intentKey, $text, []);
                if ($handled !== null) {
                    return $this->applyHumanTone($handled, $intentKey);
                }
            }
        }

        $name = $this->siteConfig['siteName'];
        $phone = $this->siteConfig['contactPhone'];

        return $this->wrapResponse(
            $this->humanize(
                "That's a thoughtful real estate question. At {$name}, we support clients with buying, selling, renting, land, documentation, and investments across Nigeria — especially Owerri and Imo State.\n\n" .
                "I want to make sure you get accurate, personalised advice. Could you tell me a little more — for example the location, property type, or whether you're buying, renting, or investing?\n\n" .
                "You can also speak directly with our consultants at {$phone} — they're very helpful.",
                'fallback'
            ),
            'fallback',
            0.55,
            'general_real_estate',
            false
        );
    }

    private function humanize(string $text, ?string $intentKey = null): string
    {
        if ($intentKey === 'thanks' || $intentKey === 'farewell') {
            return $text;
        }

        if (mb_strlen($text) < 12) {
            return $text;
        }

        $alreadyStarts = preg_match('/^(certainly|of course|absolutely|great|hello|hi|thank|you\'re|i\'d)/i', $text);
        if ($alreadyStarts) {
            return $text;
        }

        if (random_int(1, 100) <= 45) {
            $opener = self::HUMAN_OPENERS[array_rand(self::HUMAN_OPENERS)];
            return $opener . ' ' . $text;
        }

        return $text;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function applyHumanTone(array $result, ?string $intentKey): array
    {
        if (isset($result['response'])) {
            $result['response'] = $this->humanize((string) $result['response'], $intentKey);
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{response: string, source: string, confidence: float, intent: ?string, escalate: bool, metadata?: array}
     */
    private function wrapResponse(
        string $response,
        string $source,
        float $confidence,
        ?string $intent,
        bool $escalate,
        array $metadata = []
    ): array {
        $result = [
            'response'   => $response,
            'source'     => $source,
            'confidence' => $confidence,
            'intent'     => $intent,
            'escalate'   => $escalate,
        ];
        if ($metadata !== []) {
            $result['metadata'] = $metadata;
        }
        if ($escalate) {
            $result['metadata'] = array_merge($result['metadata'] ?? [], [
                'show_escalation_button' => true,
                'show_support_options'   => true,
                'offer_human_support'    => true,
            ]);
        }
        return $result;
    }

    /**
     * @param list<array{text?: string, weight?: int}> $responses
     */
    private function pickResponse(array $responses): ?string
    {
        if ($responses === []) {
            return null;
        }

        $pool = [];
        foreach ($responses as $r) {
            $text = $r['text'] ?? '';
            $weight = max(1, (int) ($r['weight'] ?? 1));
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $text;
            }
        }

        return $pool === [] ? null : $pool[array_rand($pool)];
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s\'\-\.,\?!]/u', ' ', $text) ?? '';
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $text = $this->normalizeText($text);
        if ($text === '') {
            return [];
        }

        $stop = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'to', 'of', 'in', 'on', 'at', 'for', 'and', 'or'];
        $parts = preg_split('/[\s,\.\?!]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter($parts, static fn ($w) => !in_array($w, $stop, true) && mb_strlen($w) > 1));
    }

    private function extractLocationFromText(string $text): ?string
    {
        if (preg_match('/\b(?:in|at|near|around|within)\s+([a-z\s]{3,40})/i', $text, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/\b(owerri|imo state|imo|lagos|abuja|port harcourt|ph city|enugu|abia)\b/i', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array{response: string, source: string, confidence: float, intent: string, escalate: bool, metadata?: array}
     */
    private function buildIntentResponse(string $response, string $intent, float $confidence, array $metadata = []): array
    {
        $result = [
            'response'   => $response,
            'source'     => 'intent',
            'confidence' => $confidence,
            'intent'     => $intent,
            'escalate'   => false,
        ];

        if ($metadata !== []) {
            $result['metadata'] = $metadata;
        }

        return $result;
    }
}
