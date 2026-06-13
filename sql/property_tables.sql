-- Property listings managed via admin panel
USE `biverroyal_estate`;

CREATE TABLE IF NOT EXISTS `properties` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            VARCHAR(255) NOT NULL,
    `price`            BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `type`             ENUM('sale', 'rent') NOT NULL DEFAULT 'sale',
    `location`         VARCHAR(255) NOT NULL,
    `bedrooms`         TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `bathrooms`        TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `area`             INT UNSIGNED NOT NULL DEFAULT 0,
    `approval_status`  ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `source`             ENUM('admin','public') NOT NULL DEFAULT 'admin',
    `owner_name`         VARCHAR(120) DEFAULT NULL,
    `owner_email`        VARCHAR(255) DEFAULT NULL,
    `owner_phone`        VARCHAR(30) DEFAULT NULL,
    `contact_method`     VARCHAR(20) DEFAULT NULL,
    `listing_purpose`    VARCHAR(20) DEFAULT NULL,
    `property_category`  VARCHAR(50) DEFAULT NULL,
    `property_address`   TEXT DEFAULT NULL,
    `property_features`  TEXT DEFAULT NULL,
    `ownership_status`   VARCHAR(30) DEFAULT NULL,
    `property_size`      VARCHAR(80) DEFAULT NULL,
    `image_url`        VARCHAR(512) DEFAULT NULL,
    `video_url`          VARCHAR(512) DEFAULT NULL,
    `description`      TEXT DEFAULT NULL,
    `admin_notes`        TEXT DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_properties_type` (`type`),
    KEY `idx_properties_status` (`approval_status`),
    KEY `idx_properties_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `properties` (`title`, `price`, `type`, `location`, `image_url`, `description`, `approval_status`)
SELECT * FROM (
    SELECT 'Luxury Villa in New Owerri', 85000000, 'sale', 'New Owerri, Imo State',
           'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=600&h=400&fit=crop',
           'Spacious villa with modern finishes and secure estate access.', 'approved'
    UNION ALL
    SELECT 'Executive Apartment', 450000, 'rent', 'Aladinma, Owerri',
           'https://images.unsplash.com/photo-1502672260266-1c1ef2cd9361?w=600&h=400&fit=crop',
           'Fully serviced apartment ideal for professionals.', 'approved'
    UNION ALL
    SELECT 'Commercial Plaza', 250000000, 'sale', 'Wetheral Road, Owerri',
           'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&h=400&fit=crop',
           'Prime commercial property with high foot traffic.', 'approved'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `properties` LIMIT 1);
