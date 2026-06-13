<?php
/**
 * Expand chatbot FAQs & knowledge base — Nigerian real estate topics.
 * Run: http://localhost/BIVER_ROYAL_ESTATE/sql/upgrade_chatbot_nigeria_content.php
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/chatbot/includes/ChatbotRepository.php';

$pdo = getDatabaseConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function addFaq(PDO $pdo, string $question, string $answer, array $keywords, string $category, int $priority): void
{
    $stmt = $pdo->prepare('SELECT id FROM chatbot_faqs WHERE question = :q LIMIT 1');
    $stmt->execute(['q' => $question]);
    if ($stmt->fetchColumn()) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO chatbot_faqs (question, answer, keywords, category, priority, match_score_threshold, is_active)
         VALUES (:q, :a, :kw, :c, :p, 0.30, 1)'
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
        'INSERT INTO chatbot_knowledgebase (title, content, keywords, category, priority, match_score_threshold, is_active)
         VALUES (:t, :c, :kw, :cat, :p, 0.26, 1)'
    )->execute([
        't'   => $title,
        'c'   => $content,
        'kw'  => json_encode($keywords, JSON_UNESCAPED_UNICODE),
        'cat' => $category,
        'p'   => $priority,
    ]);
}

try {
    echo "Adding Nigerian real-estate FAQs & knowledge base…\n\n";

    $faqs = [
        [
            'What is a Certificate of Occupancy (C of O)?',
            'A Certificate of Occupancy (C of O) is a state-issued document confirming that the government has granted you the right to occupy and use land for a specified purpose (usually residential or commercial). It is one of the strongest forms of land title in Nigeria. Before buying, confirm whether the property has a valid C of O or is on a path to obtaining one. Biver Royalty Homes assists buyers with title verification.',
            ['c of o', 'certificate of occupancy', 'occupancy certificate', 'title document'],
            'legal',
            92,
        ],
        [
            'What is Governor\'s Consent and do I need it?',
            'When you buy property with an existing C of O or deed, the transfer often requires **Governor\'s Consent** (or Commissioner\'s Consent in some states) to make the new ownership legally recognised. Without it, resale and mortgage may be difficult. Factor consent fees and timeline (often weeks to months) into your budget. Our consultants explain state-specific requirements for Imo and other locations.',
            ['governor consent', 'governors consent', 'consent fee', 'land transfer'],
            'legal',
            91,
        ],
        [
            'What is a Deed of Assignment?',
            'A **Deed of Assignment** is the legal document that transfers ownership interest in land or property from the seller (assignor) to the buyer (assignee). It should describe the plot, parties, consideration (price in ₦), and be registered where required. Always use a qualified lawyer to review the deed before payment.',
            ['deed of assignment', 'assignment deed', 'transfer deed', 'sale agreement'],
            'legal',
            90,
        ],
        [
            'What is a survey plan and why does it matter?',
            'A **survey plan** shows the exact boundaries, coordinates, and size of a plot. It helps confirm you are buying the correct land and reduces disputes. For land purchases, insist on a registered survey from a licensed surveyor and cross-check with the survey plan attached to the title documents.',
            ['survey plan', 'survey', 'beacon', 'boundaries', 'coordinates'],
            'legal',
            89,
        ],
        [
            'How much does land cost in Owerri?',
            'Land prices in Owerri and Imo State vary widely by area, road access, and title status — from a few million Naira for peri-urban plots to significantly more for prime or estate land. Share your preferred neighbourhood and budget (e.g. under ₦5M, ₦10M–₦20M) and we will shortlist verified options.',
            ['land cost owerri', 'price of land owerri', 'how much land imo', 'plot price'],
            'land',
            88,
        ],
        [
            'Do you have properties in Port Harcourt or Rivers State?',
            'Biver Royalty Homes primarily lists and advises on **Owerri and Imo State**, and we support clients seeking opportunities in **Rivers State, Port Harcourt (PH), and other Nigerian cities** through our network. Tell us your budget in ₦ and property type — we will guide you on available listings or referrals.',
            ['port harcourt', 'ph city', 'rivers state', 'portharcourt', 'woji', 'gra ph'],
            'location',
            88,
        ],
        [
            'Do you have properties in Lagos or Abuja?',
            'Our core inventory is in **Owerri / Imo State**, but we assist buyers and diaspora clients interested in **Lagos, Abuja (FCT), Enugu**, and other markets with due diligence and partner referrals. Describe your target area and budget in Naira for tailored advice.',
            ['lagos', 'abuja', 'fct', 'lekki', 'ajah', 'gwarinpa', 'enugu'],
            'location',
            87,
        ],
        [
            'How much advance rent do tenants pay in Nigeria?',
            'In many Nigerian cities, landlords request **1–2 years rent in advance**, plus a caution deposit (often 10% of annual rent). Lagos has specific tenancy regulations; elsewhere terms are often negotiated. Always sign a written **tenancy agreement** stating rent, duration, renewal, and maintenance responsibilities.',
            ['advance rent', 'two years rent', 'tenant deposit', 'caution fee', 'upfront rent'],
            'rental',
            88,
        ],
        [
            'What should be in a tenancy agreement in Nigeria?',
            'A solid tenancy agreement should include: parties\' names, property address, rent amount in **₦**, duration, notice period, who maintains what, rent review clause, and dispute resolution. Both parties should sign; witnesses or stamping may apply depending on state. We help tenants and landlords with fair, clear lease terms.',
            ['tenancy agreement', 'lease agreement', 'rental contract', 'tenant rights'],
            'rental',
            87,
        ],
        [
            'Can diaspora Nigerians buy property from abroad?',
            'Yes. Many clients in the **UK, US, Canada, and Europe** buy land or homes in Nigeria. Use trusted agents, never pay without verification, request video walkthroughs, and complete documentation through a lawyer. Biver Royalty Homes supports diaspora buyers with transparent updates and documented transactions.',
            ['diaspora', 'abroad', 'overseas nigerian', 'from uk', 'from usa', 'in the diaspora'],
            'purchase',
            90,
        ],
        [
            'What is estate dues or service charge?',
            'In gated estates, **service charge / estate dues** cover security, waste disposal, road maintenance, and shared amenities. Ask the monthly or annual amount in **₦** before buying or renting. Compare estates by what is included (generator, water, street lighting).',
            ['service charge', 'estate dues', 'maintenance fee', 'estate levy'],
            'purchase',
            84,
        ],
        [
            'What is the difference between excision and gazette?',
            '**Excision** is the process of releasing land from government acquisition so it can be allocated to individuals. A **gazette** is the official government publication that records that release. Buying land still under acquisition without excision/gazette is risky — always verify status with a lawyer.',
            ['excision', 'gazette', 'government acquisition', 'committed land'],
            'legal',
            89,
        ],
        [
            'How do I avoid land fraud in Nigeria?',
            'Key steps: (1) Verify seller identity, (2) Confirm survey and title documents, (3) Search at land registry where possible, (4) Visit the site, (5) Pay through traceable channels in tranches tied to milestones, (6) Use a lawyer. Avoid pressure to pay cash without paperwork. Biver Royalty Homes prioritises verified listings.',
            ['land fraud', 'scam', 'omo onile', 'omonile', 'fake documents', '419 land'],
            'legal',
            91,
        ],
        [
            'Do you offer payment plans for property?',
            'Selected properties and developments may offer **instalment or payment plans** (e.g. initial deposit plus monthly payments over 6–24 months). Terms vary by project. Share the property you are interested in and we will confirm availability, amounts in **₦**, and documentation.',
            ['payment plan', 'installment', 'pay small small', 'spread payment', 'hire purchase property'],
            'pricing',
            86,
        ],
        [
            'What banks offer mortgages in Nigeria?',
            'Several Nigerian banks and primary mortgage institutions offer home loans (e.g. FCMB, Access, UBA, Stanbic, FMBN for eligible schemes). Requirements typically include equity contribution, payslips or business records, property valuation, and title perfection. We can outline typical steps while you approach your preferred lender.',
            ['mortgage bank', 'home loan nigeria', 'housing loan', 'fmbn', 'primary mortgage'],
            'mortgage',
            85,
        ],
        [
            'What is stamp duty on property in Nigeria?',
            'Property transactions often attract **stamp duties** and registration fees, calculated on the consideration or assessed value. Rates depend on state and transaction type. Budget roughly 0.5%–2% or more depending on location — your lawyer will advise exact figures when preparing the deed.',
            ['stamp duty', 'registration fee', 'land use charge', 'transaction tax'],
            'legal',
            82,
        ],
        [
            'Should I buy land or a built house?',
            '**Land** can offer appreciation and flexibility to build later but requires construction cost and time. A **built house** gives immediate use or rental income. Consider title clarity, location growth, and total budget in **₦** including finishing. We help you compare both options in Owerri and beyond.',
            ['land or house', 'buy land vs house', 'build or buy', 'unfinished'],
            'purchase',
            84,
        ],
        [
            'What is a Certificate of Occupancy vs R of O?',
            '**C of O (Certificate of Occupancy)** is the long-term right to use land from the state. **R of O (Right of Occupancy)** is often an earlier or customary stage of that right. Understand which document the seller holds and what steps remain before full perfection.',
            ['r of o', 'right of occupancy', 'c of o vs r of o'],
            'legal',
            86,
        ],
        [
            'Do you handle property management and tenants?',
            'We advise on **tenant sourcing, rent collection support, basic maintenance coordination, and inspections** for landlords. For full facility management, scope and fees are agreed in writing. Ask about our management services for Owerri and Imo properties.',
            ['property management', 'facility management', 'manage my property', 'tenant management'],
            'management',
            83,
        ],
        [
            'What are typical house prices in Owerri?',
            'As a guide, entry-level homes may start from roughly **₦15M–₦40M+**, mid-range family homes **₦40M–₦80M**, and premium estates higher — depending on finish and area. Prices change with market conditions. Browse our listings or tell us your budget band (e.g. under ₦50M) for current matches.',
            ['house price owerri', 'how much house owerri', 'cost of house imo', 'naira price'],
            'pricing',
            88,
        ],
        [
            'Can I get a receipt and contract when I pay for property?',
            'Yes — you should always receive a **written contract or offer letter**, payment receipts, and later the deed and title documents. Never pay without a paper trail. Biver Royalty Homes documents transactions professionally for buyer protection.',
            ['receipt', 'contract', 'payment proof', 'agreement'],
            'legal',
            85,
        ],
        [
            'What is built-up area vs plot size?',
            '**Plot size** is the land dimension (often in sqm or acres). **Built-up area** is the floor space of the structure on the land. When comparing prices in **₦**, check both — a large plot with a small building differs from a compact plot with a spacious duplex.',
            ['built up area', 'plot size', 'square meter', 'sqm', 'acre'],
            'purchase',
            82,
        ],
        [
            'Is short-let or Airbnb allowed in your rentals?',
            'Short-let use depends on **landlord permission, estate rules, and local regulations**. Some estates prohibit daily rentals. If you need short-let or serviced apartment use, tell us upfront so we match you with suitable properties.',
            ['short let', 'airbnb', 'serviced apartment', 'daily rental'],
            'rental',
            80,
        ],
        [
            'What due diligence do you do before listing land?',
            'We review seller identity, available title/survey documents, site access, and dispute indicators. We recommend independent legal search for high-value purchases. Our goal is to reduce risk — buyers should still engage their lawyer for final perfection.',
            ['due diligence', 'verification', 'before listing', 'checked land'],
            'land',
            86,
        ],
        [
            'How do I book a property inspection in Owerri?',
            'Use the **Book Inspection** button in this chat, or tell us the property name, preferred date, your name and phone number. Inspections are free for serious buyers on our approved listings. Weekend visits can often be arranged.',
            ['book inspection', 'site visit', 'viewing', 'see the property'],
            'inspection',
            87,
        ],
    ];

    foreach ($faqs as [$q, $a, $kw, $cat, $pri]) {
        addFaq($pdo, $q, $a, $kw, $cat, $pri);
        echo "FAQ: " . mb_substr($q, 0, 50) . "…\n";
    }

    $kb = [
        [
            'Nigerian Property Title Documents Explained',
            "Common documents in Nigerian real estate:\n\n• **C of O (Certificate of Occupancy)** — state grant to use land\n• **Deed of Assignment** — transfer from seller to buyer\n• **Survey Plan** — boundary description\n• **Governor's Consent** — approval of transfer\n• **Gazette / Excision** — proof land is free from acquisition\n\nAlways engage a property lawyer. Biver Royalty Homes supports verification before you pay.",
            ['title documents', 'land papers', 'legal documents nigeria', 'paperwork'],
            'legal',
            92,
        ],
        [
            'Buying Land in Imo State — Step by Step',
            "1. Define budget in **₦** and preferred area (Owerri municipal, Orlu road corridor, etc.)\n2. Shortlist plots with verified sellers\n3. Inspect site and access road\n4. Lawyer reviews survey and title\n5. Sign contract and pay in tranches\n6. Perfect title and take possession\n\nWe guide clients through each stage and flag red flags early.",
            ['buy land imo', 'land purchase owerri', 'imo land process'],
            'land',
            91,
        ],
        [
            'Renting in Nigeria — Tenant Checklist',
            "Before paying rent:\n• Inspect property and neighbourhood security\n• Confirm who pays for repairs and generator fuel\n• Agree rent, duration, and notice period in writing\n• Document meter readings and inventory\n• Pay to landlord account with receipt\n\nAdvance rent of 1–2 years is common outside Lagos rent-control zones. Ask us for current rental listings in **₦** per annum.",
            ['tenant checklist', 'renting nigeria', 'before renting'],
            'rental',
            90,
        ],
        [
            'Port Harcourt & Rivers State Property Market',
            'Port Harcourt (PH) and Rivers State attract oil-sector professionals and investors. Prices vary sharply between Old GRA, Peter Odili, Woji, and satellite towns. Title and community issues require extra care. Biver Royalty Homes can advise Imo-based clients expanding into Rivers and connect you with trusted partners for viewings.',
            ['port harcourt market', 'rivers property', 'ph real estate', 'oil city property'],
            'location',
            88,
        ],
        [
            'Lagos Property Market — What Buyers Should Know',
            'Lagos is Nigeria\'s largest market — higher prices in **₦**, traffic considerations, and Lekki/Victoria Island/Ikorodu submarkets differ greatly. Confirm **Governor\'s Consent**, survey, and building approvals. Diaspora buyers should avoid rushing remote payments. We support Lagos purchases primarily through legal partners and referrals while our listings focus on Owerri/Imo.',
            ['lagos property', 'buy in lagos', 'lekki', 'mainland lagos'],
            'location',
            87,
        ],
        [
            'Abuja (FCT) Real Estate Overview',
            'Abuja FCT uses a structured district system (Gwarinpa, Maitama, Kubwa, etc.) with generally strong documentation on allocated plots. Prices are often higher than Owerri. Factor infrastructure levies and development charges. Contact us if you are comparing Abuja investments with Imo State opportunities.',
            ['abuja', 'fct', 'federal capital', 'gwarinpa', 'maitama'],
            'location',
            86,
        ],
        [
            'Diaspora Guide — Buying Property from Overseas',
            "If you live abroad:\n• Use video inspections and independent lawyers in Nigeria\n• Avoid paying 'agents' without company address and contract\n• Request scanned title/survey before deposit\n• Milestone payments tied to documentation\n• Plan for Governor's Consent and final handover trip\n\nBiver Royalty Homes works with diaspora clients regularly — all amounts quoted in **₦**.",
            ['diaspora guide', 'buy from abroad', 'overseas buyer'],
            'purchase',
            91,
        ],
        [
            'Property Investment Strategies in South-East Nigeria',
            "Popular strategies:\n• **Buy-to-let** near schools and markets in Owerri\n• **Land banking** along growth corridors\n• **Flip** renovated units in high-demand areas\n• **Commercial** shops on arterial roads\n\nReturns depend on title quality and management. We discuss realistic rental yields and capital appreciation in **₦**, not speculative promises.",
            ['investment strategy', 'roi nigeria', 'rental yield', 'south east'],
            'investment',
            89,
        ],
        [
            'Understanding Omonile and Community Fees',
            '**Omonile** (indigenous land owners) sometimes demand fees before development or after purchase on customary land. Clarify all community charges in writing before buying. On government-titled estates, fees should be structured and receipted. Our team helps buyers understand local expectations in Imo communities.',
            ['omonile', 'omo onile', 'community fee', 'family land'],
            'legal',
            88,
        ],
        [
            'Mortgage & Home Loan Process in Nigeria',
            "Typical steps:\n1. Choose property with acceptable title\n2. Apply to bank or PMI with equity (often 20–30%+)\n3. Bank valuation and legal search\n4. Loan offer and mortgage deed\n5. Disbursement to seller / perfection\n\nInterest rates and tenors vary. Budget legal fees and insurance. We help you prepare property packs for lenders.",
            ['mortgage process', 'home loan steps', 'bank mortgage nigeria'],
            'mortgage',
            87,
        ],
        [
            'Off-Plan vs Completed Property',
            '**Off-plan** purchases (before completion) may offer lower **₦** pricing but carry construction and developer-default risk — use strong contracts and staged payments. **Completed** homes allow immediate inspection and move-in. Compare total cost including finishing when buying shells.',
            ['off plan', 'under construction', 'uncompleted building', 'shell property'],
            'purchase',
            85,
        ],
        [
            'Estate Living vs Standalone Property',
            '**Estates** offer security, paved roads, and shared amenities but add service charges. **Standalone** homes may be cheaper per sqm but need private security and borehole. In Owerri, popular estates attract families; standalone suits custom builds. Tell us your priority and budget in **₦**.',
            ['estate vs standalone', 'gated community', 'secure estate'],
            'purchase',
            84,
        ],
        [
            'Power, Water & Borehole Considerations',
            'In much of Nigeria, plan for **generator or inverter**, borehole or water treatment, and septic/soakaway. Ask sellers about monthly diesel costs. Newer estates sometimes provide central borehole and street lighting — confirm in the sale agreement.',
            ['borehole', 'generator', 'nepa', 'phcn', 'water supply', 'inverter'],
            'purchase',
            83,
        ],
        [
            'Commercial Property in Owerri & Imo',
            'Shops, plazas, and office spaces cluster around Douglas, Wetheral Road, and emerging corridors. Commercial leases may be multi-year with annual reviews in **₦**. Yield depends on foot traffic and parking. Describe your business type for suitable commercial referrals.',
            ['commercial property', 'shop for rent', 'office space owerri', 'plaza'],
            'commercial',
            84,
        ],
        [
            'Probate and Inherited Property in Nigeria',
            'Selling inherited property requires **probate letters or administration** from court, proving authority to sell. Buyers should not pay until succession is clear. Lawyers handle estate resolution. We can wait-list purchases until title is clean.',
            ['probate', 'inheritance', 'family property', 'estate of deceased'],
            'legal',
            82,
        ],
        [
            'Power of Attorney in Property Transactions',
            'A **Power of Attorney (POA)** lets someone act for the owner — useful for diaspora sellers. It must be properly drafted, notarised where required, and limited to specific transactions. Buyers should verify authenticity and that the POA is still valid.',
            ['power of attorney', 'poa', 'attorney property'],
            'legal',
            81,
        ],
        [
            'Naira Budget Bands for Property Search',
            "When searching, useful bands:\n• **Under ₦10M** — entry plots / small units (location-dependent)\n• **₦10M–₦20M** — starter homes and peri-urban land\n• **₦20M–₦50M** — family homes in Owerri\n• **₦50M–₦100M** — premium homes and prime land\n• **Above ₦100M** — luxury and high-value assets\n\nTell us your band and we filter listings accordingly.",
            ['budget', 'price range', 'million naira', 'under 20 million', '50 million'],
            'pricing',
            90,
        ],
        [
            'Joint Venture Property Development',
            'Landowners sometimes partner with developers on **joint venture** (land + construction expertise, share units or profit). Contracts must define timelines, profit split, and exit. Legal review is essential before signing JV agreements.',
            ['joint venture', 'jv development', 'developer partner'],
            'investment',
            80,
        ],
        [
            'Flood-Prone Areas and Site Drainage',
            'During inspections, check drainage, road elevation, and history of flooding in rainy season (especially near waterways in PH, Lagos, and low-lying plots). Avoid filling wetlands without permits. We recommend site visits in **June–September** when possible.',
            ['flood', 'flooding', 'drainage', 'waterlogged'],
            'land',
            81,
        ],
        [
            'Real Estate Agent Commission in Nigeria',
            'Agent commission is commonly **5–10%** of sale or rent (negotiable), paid as agreed in an engagement letter. Transparent agents disclose fees upfront. Biver Royalty Homes explains commission and marketing scope before listing your property.',
            ['agent commission', 'agency fee', 'broker fee', 'commission rate'],
            'sell',
            82,
        ],
    ];

    echo "\nKnowledge base articles:\n";
    foreach ($kb as [$title, $content, $kw, $cat, $pri]) {
        addKb($pdo, $title, $content, $kw, $cat, $pri);
        echo "KB: {$title}\n";
    }

    // Broader keyword coverage on legal_docs intent
    $pdo->prepare(
        "UPDATE chatbot_intents SET keywords = :kw WHERE intent_key = 'legal_docs'"
    )->execute(['kw' => json_encode([
        'title', 'deed', 'c of o', 'certificate of occupancy', 'survey', 'documentation', 'legal',
        'gazette', 'perfection', 'governor consent', 'assignment', 'excision', 'r of o', 'stamp duty',
        'probate', 'power of attorney', 'land registry', 'verification',
    ], JSON_UNESCAPED_UNICODE)]);

    $pdo->prepare(
        "UPDATE chatbot_intents SET keywords = :kw WHERE intent_key = 'location'"
    )->execute(['kw' => json_encode([
        'location', 'where', 'address', 'office', 'owerri', 'imo', 'port harcourt', 'rivers',
        'lagos', 'abuja', 'enugu', 'ph', 'nigeria', 'south east', 'south-east',
    ], JSON_UNESCAPED_UNICODE)]);

    ChatbotRepository::invalidateContentCache();

    $faqCount = (int) $pdo->query('SELECT COUNT(*) FROM chatbot_faqs WHERE is_active = 1')->fetchColumn();
    $kbCount = (int) $pdo->query('SELECT COUNT(*) FROM chatbot_knowledgebase WHERE is_active = 1')->fetchColumn();

    echo "\nDone. Content cache cleared.\n";
    echo "Active FAQs: {$faqCount}\n";
    echo "Active KB articles: {$kbCount}\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Upgrade failed: ' . $e->getMessage();
}
