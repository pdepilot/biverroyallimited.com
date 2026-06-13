<?php
/**
 * One-time installer: testimonials + service areas tables with seed data.
 * Run: http://localhost/BIVER_ROYAL_ESTATE/sql/install_content.php
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/config/database.php';

$sqlFile = __DIR__ . '/content_tables.sql';

if (!is_readable($sqlFile)) {
    http_response_code(500);
    echo "content_tables.sql not found.\n";
    exit(1);
}

try {
    $pdo = getDatabaseConnection();
    $pdo->exec(file_get_contents($sqlFile));

    $testimonialCount = (int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
    if ($testimonialCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO testimonials (client_name, message, rating, initials, role_label, sort_order)
             VALUES (:name, :message, :rating, :initials, :role_label, :sort_order)'
        );
        $seedTestimonials = [
            ['Chisom O.', 'Biver Royalty Homes made finding my dream house in Owerri so easy. The team was highly professional and transparent.', 5, 'CO', 'Happy Client', 1],
            ['Emeka U.', 'Their property management services are top-notch. I have peace of mind knowing my investments are in good hands.', 5, 'EU', 'Happy Client', 2],
            ['Adaobi N.', 'From start to finish, the purchasing process was seamless. I highly recommend them to anyone looking for real estate in Imo State.', 5, 'AN', 'Happy Client', 3],
        ];
        foreach ($seedTestimonials as [$name, $message, $rating, $initials, $role, $order]) {
            $stmt->execute([
                'name'       => $name,
                'message'    => $message,
                'rating'     => $rating,
                'initials'   => $initials,
                'role_label' => $role,
                'sort_order' => $order,
            ]);
        }
        echo "Seeded 3 testimonials.\n";
    }

    $areaCount = (int) $pdo->query('SELECT COUNT(*) FROM service_areas')->fetchColumn();
    if ($areaCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO service_areas
             (title, tag, image_url, description, meta1_icon, meta1_text, meta2_icon, meta2_text, link_url, sort_order)
             VALUES
             (:title, :tag, :image_url, :description, :meta1_icon, :meta1_text, :meta2_icon, :meta2_text, :link_url, :sort_order)'
        );
        $seedAreas = [
            ['World Bank Housing Estate', 'Popular', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&auto=format&fit=crop&q=80', 'Gated community living with strong security, paved roads, and premium villas ideal for families and investors.', 'home-outline', 'Villas & duplexes', 'trending-up-outline', 'High demand', 'property.php', 1],
            ['New Owerri', 'Rentals', 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&auto=format&fit=crop&q=80', 'Modern apartments and terraces close to business districts — perfect for professionals and young families.', 'business-outline', 'Central access', 'key-outline', 'Rent & sale', 'property.php', 2],
            ['Government Station Layout', 'Executive', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=80', 'Spacious mansions and executive homes in a prestigious address with excellent long-term value.', 'star-outline', 'Premium homes', 'shield-checkmark-outline', 'Verified titles', 'property.php', 3],
            ['Aladinma', 'Value', 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&auto=format&fit=crop&q=80', 'Affordable bungalows and semi-detached homes — a smart entry point for first-time buyers in Owerri.', 'cash-outline', 'Great value', 'people-outline', 'Family-friendly', 'property.php', 4],
            ['Works Layout', 'Growing', 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800&auto=format&fit=crop&q=80', 'Rapidly developing area with new builds, duplexes, and strong rental yields for property investors.', 'construct-outline', 'New developments', 'analytics-outline', 'Investment potential', 'property.php', 5],
            ['Port Harcourt Road Corridor', 'Commercial', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&auto=format&fit=crop&q=80', 'Mixed-use plots, commercial-facing properties, and residential options along a major Owerri artery.', 'storefront-outline', 'Mixed-use', 'navigate-outline', 'Main road access', 'property.php', 6],
        ];
        foreach ($seedAreas as [$title, $tag, $image, $desc, $i1, $t1, $i2, $t2, $link, $order]) {
            $stmt->execute([
                'title'       => $title,
                'tag'         => $tag,
                'image_url'   => $image,
                'description' => $desc,
                'meta1_icon'  => $i1,
                'meta1_text'  => $t1,
                'meta2_icon'  => $i2,
                'meta2_text'  => $t2,
                'link_url'    => $link,
                'sort_order'  => $order,
            ]);
        }
        echo "Seeded 6 service areas.\n";
    }

    echo "Content tables installed successfully.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
