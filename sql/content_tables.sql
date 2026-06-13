-- Testimonials and service areas (homepage content)
-- Database: biverroyal_estate

CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    initials VARCHAR(8) DEFAULT NULL,
    image_path VARCHAR(512) DEFAULT NULL,
    role_label VARCHAR(80) NOT NULL DEFAULT 'Happy Client',
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_testimonials_published (is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_areas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    tag VARCHAR(40) NOT NULL DEFAULT '',
    image_url VARCHAR(512) NOT NULL,
    description TEXT NOT NULL,
    meta1_icon VARCHAR(40) NOT NULL DEFAULT 'home-outline',
    meta1_text VARCHAR(80) NOT NULL DEFAULT '',
    meta2_icon VARCHAR(40) NOT NULL DEFAULT 'star-outline',
    meta2_text VARCHAR(80) NOT NULL DEFAULT '',
    link_url VARCHAR(255) NOT NULL DEFAULT 'property.php',
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_areas_published (is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
