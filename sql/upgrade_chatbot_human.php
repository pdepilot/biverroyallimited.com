<?php
/**
 * Upgrade chatbot knowledge for human-like, broad real-estate responses.
 * Run: http://localhost/BIVER_ROYAL_ESTATE/sql/upgrade_chatbot_human.php
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/chatbot/includes/ChatbotRepository.php';

$pdo = getDatabaseConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function upsertIntent(PDO $pdo, string $key, string $name, array $keywords, int $priority, float $threshold): int
{
    $stmt = $pdo->prepare('SELECT id FROM chatbot_intents WHERE intent_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $id = $stmt->fetchColumn();

    $json = json_encode($keywords, JSON_UNESCAPED_UNICODE);

    if ($id) {
        $pdo->prepare(
            'UPDATE chatbot_intents SET name = :name, keywords = :kw, priority = :p, confidence_threshold = :t, is_active = 1 WHERE id = :id'
        )->execute(['name' => $name, 'kw' => $json, 'p' => $priority, 't' => $threshold, 'id' => $id]);
        return (int) $id;
    }

    $pdo->prepare(
        'INSERT INTO chatbot_intents (intent_key, name, keywords, priority, confidence_threshold) VALUES (:key, :name, :kw, :p, :t)'
    )->execute(['key' => $key, 'name' => $name, 'kw' => $json, 'p' => $priority, 't' => $threshold]);

    return (int) $pdo->lastInsertId();
}

function addResponses(PDO $pdo, int $intentId, array $texts): void
{
    $check = $pdo->prepare('SELECT COUNT(*) FROM chatbot_responses WHERE intent_id = :id');
    $check->execute(['id' => $intentId]);
    if ((int) $check->fetchColumn() >= count($texts)) {
        return;
    }

    $ins = $pdo->prepare('INSERT INTO chatbot_responses (intent_id, response_text, weight) VALUES (:id, :text, 1)');
    foreach ($texts as $text) {
        $exists = $pdo->prepare('SELECT id FROM chatbot_responses WHERE intent_id = :id AND response_text = :text LIMIT 1');
        $exists->execute(['id' => $intentId, 'text' => $text]);
        if (!$exists->fetchColumn()) {
            $ins->execute(['id' => $intentId, 'text' => $text]);
        }
    }
}

function addFaq(PDO $pdo, string $question, string $answer, array $keywords, string $category, int $priority): void
{
    $stmt = $pdo->prepare('SELECT id FROM chatbot_faqs WHERE question = :q LIMIT 1');
    $stmt->execute(['q' => $question]);
    if ($stmt->fetchColumn()) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO chatbot_faqs (question, answer, keywords, category, priority, match_score_threshold) VALUES (:q, :a, :kw, :c, :p, 0.32)'
    )->execute([
        'q'  => $question,
        'a'  => $answer,
        'kw' => json_encode($keywords, JSON_UNESCAPED_UNICODE),
        'c'  => $category,
        'p'  => $priority,
    ]);
}

function addKb(PDO $pdo, string $title, string $content, array $keywords, string $category, int $priority): void
{
    $stmt = $pdo->prepare('SELECT id FROM chatbot_knowledgebase WHERE title = :t LIMIT 1');
    $stmt->execute(['t' => $title]);
    if ($stmt->fetchColumn()) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO chatbot_knowledgebase (title, content, keywords, category, priority, match_score_threshold) VALUES (:t, :c, :kw, :cat, :p, 0.28)'
    )->execute([
        't'   => $title,
        'c'   => $content,
        'kw'  => json_encode($keywords, JSON_UNESCAPED_UNICODE),
        'cat' => $category,
        'p'   => $priority,
    ]);
}

try {
    echo "Upgrading chatbot content…\n\n";

    $pdo->prepare(
        "UPDATE chatbot_intents SET keywords = :kw, confidence_threshold = 0.28 WHERE intent_key = 'greetings'"
    )->execute(['kw' => json_encode([
        'hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening',
        'how are you', 'how are u', 'greetings', 'hola', 'welcome', 'morning', 'evening',
    ])]);

    $pdo->prepare(
        "UPDATE chatbot_intents SET keywords = :kw, confidence_threshold = 0.25 WHERE intent_key = 'thanks'"
    )->execute(['kw' => json_encode([
        'thanks', 'thank you', 'thank u', 'thx', 'appreciate', 'grateful', 'much appreciated', 'cheers',
    ])]);

    $pdo->prepare(
        "UPDATE chatbot_intents SET keywords = :kw WHERE intent_key IN ('property_purchase','rental_properties','land_purchase','pricing')"
    )->execute(['kw' => json_encode([
        'buy', 'purchase', 'house', 'home', 'property', 'apartment', 'flat', 'duplex', 'bungalow',
        'bedroom', 'bedrooms', 'bathroom', 'for sale', 'available', 'listing', 'listings', 'show me', 'looking for',
    ])]);

    $intents = [
        'please_ack' => ['Please / Polite requests', ['please', 'kindly', 'could you', 'can you', 'would you', 'help me with'], 88, 0.25],
        'help' => ['General Help', ['help', 'what can you do', 'how can you help', 'assist me', 'support', 'options'], 82, 0.28],
        'sell_property' => ['Sell / List Property', ['sell', 'selling', 'list my', 'list your', 'list property', 'put on market', 'vendor'], 78, 0.30],
        'investment' => ['Investment', ['invest', 'investment', 'roi', 'returns', 'portfolio', 'passive income', 'capital'], 76, 0.30],
        'mortgage_financing' => ['Mortgage & Financing', ['mortgage', 'loan', 'bank loan', 'financing', 'installment', 'payment plan', 'down payment', 'deposit'], 75, 0.30],
        'legal_docs' => ['Legal & Documentation', ['title', 'deed', 'c of o', 'certificate of occupancy', 'survey', 'documentation', 'legal', 'gazette', 'perfection'], 74, 0.30],
        'luxury_homes' => ['Luxury Properties', ['luxury', 'premium', 'executive', 'high end', 'mansion', 'villa', 'upscale'], 72, 0.30],
    ];

    foreach ($intents as $key => [$name, $kw, $pri, $thr]) {
        $id = upsertIntent($pdo, $key, $name, $kw, $pri, $thr);
        echo "Intent: {$key} (id {$id})\n";
    }

    $greetId = (int) $pdo->query("SELECT id FROM chatbot_intents WHERE intent_key='greetings'")->fetchColumn();
    addResponses($pdo, $greetId, [
        'Hello! Welcome to Biver Royalty Homes — it\'s great to have you here. How may I assist you with your property needs today?',
        'Hi there! I\'m your property assistant. Feel free to ask about homes, land, rentals, prices, or inspections — I\'m happy to help.',
        'Good day! Thanks for reaching out to Biver Royalty Homes. What can I help you find today?',
    ]);

    $thanksId = (int) $pdo->query("SELECT id FROM chatbot_intents WHERE intent_key='thanks'")->fetchColumn();
    addResponses($pdo, $thanksId, [
        'You\'re very welcome! If you have any other property questions, I\'m right here.',
        'My pleasure! Don\'t hesitate to ask anything else about homes, land, or rentals.',
        'Happy to help! Wishing you the best in your property search.',
    ]);

    $faqs = [
        ['Do you have 2 bedroom apartments for rent?', 'We regularly have apartments and flats for rent in Owerri and surrounding areas. Tell me your preferred location and budget, or browse our Properties page filtered by "rent" — I can also help you book a viewing.', ['2 bedroom', 'apartment', 'rent', 'flat'], 'rental', 88],
        ['Do you have 3 bedroom houses?', 'We list 3-bedroom houses and duplexes for sale and rent. Share your location and budget and I\'ll point you to current options, or check our Properties page on the website.', ['3 bedroom', 'three bedroom', 'house', 'duplex'], 'purchase', 88],
        ['How do I know if a property title is genuine?', 'Always verify the title before payment. We assist with due diligence — survey plans, C of O checks, and legal review. Never pay cash without documentation. Our consultants can guide you through the verification process.', ['genuine', 'fake', 'title', 'verify', 'scam'], 'legal', 90],
        ['What documents do I need to buy a house in Nigeria?', 'Typically you need: valid ID, purchase agreement, proof of payment, survey plan, title documents (C of O or Deed), and tax clearance where applicable. We walk buyers through every step.', ['documents', 'requirements', 'buy house', 'nigeria'], 'legal', 88],
        ['Can foreigners buy property in Nigeria?', 'Yes, foreigners can acquire property in Nigeria subject to certain regulations. We recommend professional legal advice for non-resident buyers. Our team can connect you with the right consultants.', ['foreigner', 'diaspora', 'abroad', 'overseas'], 'legal', 85],
        ['How long does property purchase take?', 'Timelines vary: typically 4–12 weeks after due diligence, depending on title perfection and payment structure. We keep you informed at every stage.', ['how long', 'timeline', 'duration', 'completion'], 'purchase', 85],
        ['Do you have commercial properties?', 'We handle select commercial and mixed-use opportunities. Describe what you need (shop, office, warehouse) and our team will advise on availability.', ['commercial', 'office', 'shop', 'warehouse'], 'commercial', 82],
        ['What is the minimum deposit for a property?', 'Deposits vary by property and seller — often 10–30% initially for off-plan or instalment sales. Share the property you like and we\'ll confirm the exact terms.', ['deposit', 'initial payment', 'down payment', 'minimum'], 'pricing', 84],
        ['Are your properties in Owerri only?', 'We focus on Owerri and Imo State with additional premium listings in other Nigerian cities. Ask about any specific location.', ['owerri only', 'other cities', 'lagos', 'abuja'], 'location', 83],
        ['Can I schedule a weekend viewing?', 'Yes — weekend inspections can be arranged. Share the property, your preferred Saturday or Sunday, and your contact number. We\'ll confirm with an agent.', ['weekend', 'saturday', 'sunday', 'viewing'], 'inspection', 86],
    ];

    foreach ($faqs as [$q, $a, $kw, $cat, $pri]) {
        addFaq($pdo, $q, $a, $kw, $cat, $pri);
    }

    $kb = [
        ['Duplex vs Bungalow', 'A duplex is a two-floor residential unit, often semi-detached, popular for families needing space. A bungalow is single-level — easier access, lower maintenance. We list both across Owerri and Imo State. Tell us your preference and budget.', ['duplex', 'bungalow', 'difference', 'which is better'], 'purchase', 85],
        ['Renting in Owerri — What to Expect', 'Rental prices in Owerri vary by neighbourhood, bedrooms, and finishing. Expect to provide ID, references, and a deposit plus advance rent. We help tenants find verified listings and sign fair lease agreements.', ['renting owerri', 'tenant', 'rent price owerri'], 'rental', 86],
        ['Land Buying Tips', 'Before buying land: verify title, confirm survey, visit the site, check access road and neighbourhood growth, and use a trusted agent. Biver Royalty Homes provides full due diligence support.', ['land tips', 'buy land safely', 'plot advice'], 'land', 88],
        ['Property Investment in Imo State', 'Imo State, especially Owerri, has seen steady demand from residents and diaspora investors. Land and residential property remain popular. We identify assets with strong documentation and growth corridors.', ['invest imo', 'investment owerri', 'imo state property'], 'investment', 87],
        ['Amenities to Consider', 'When choosing a home, consider security, water supply, road access, parking, power backup, proximity to schools and markets, and estate management fees. Share your priorities and we\'ll shortlist suitable properties.', ['amenities', 'what to look for', 'facilities', 'estate features'], 'purchase', 84],
        ['Selling Your Property Through an Agent', 'Listing with Biver Royalty Homes includes professional marketing, qualified buyer screening, viewings, and negotiation support. We aim for transparent, timely sales at fair market value.', ['selling tips', 'how to sell', 'agent commission'], 'sell', 83],
    ];

    foreach ($kb as [$title, $content, $kw, $cat, $pri]) {
        addKb($pdo, $title, $content, $kw, $cat, $pri);
    }

    ChatbotRepository::invalidateContentCache();
    echo "\nUpgrade complete. Content cache cleared — changes apply immediately.\n";
    echo "Intent count: " . $pdo->query('SELECT COUNT(*) FROM chatbot_intents')->fetchColumn() . "\n";
    echo "FAQ count: " . $pdo->query('SELECT COUNT(*) FROM chatbot_faqs')->fetchColumn() . "\n";
    echo "KB count: " . $pdo->query('SELECT COUNT(*) FROM chatbot_knowledgebase')->fetchColumn() . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Upgrade failed: ' . $e->getMessage();
}
