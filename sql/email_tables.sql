-- Email center, newsletter subscribers, and delivery logs
USE `biverroyal_estate`;

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`        VARCHAR(255) NOT NULL,
    `name`         VARCHAR(120) DEFAULT NULL,
    `status`       ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
    `source`       VARCHAR(50) DEFAULT 'website',
    `subscribed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subscriber_email` (`email`),
    KEY `idx_subscriber_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_templates` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `body_html`   MEDIUMTEXT NOT NULL,
    `created_by`  INT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_template_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_drafts` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id`        INT UNSIGNED NOT NULL,
    `recipient_type`  VARCHAR(30) NOT NULL DEFAULT 'single',
    `recipients_json` TEXT DEFAULT NULL,
    `subject`         VARCHAR(255) NOT NULL DEFAULT '',
    `body_html`       MEDIUMTEXT,
    `template_id`     INT UNSIGNED DEFAULT NULL,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_draft_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipient`  VARCHAR(255) NOT NULL,
    `subject`    VARCHAR(255) NOT NULL,
    `message`    MEDIUMTEXT NOT NULL,
    `status`     ENUM('sent', 'failed', 'queued') NOT NULL DEFAULT 'queued',
    `error_msg`  VARCHAR(512) DEFAULT NULL,
    `sent_at`    DATETIME DEFAULT NULL,
    `admin_id`   INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email_logs_status` (`status`),
    KEY `idx_email_logs_sent` (`sent_at`),
    KEY `idx_email_logs_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_queue` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id`     VARCHAR(64) NOT NULL,
    `recipient`    VARCHAR(255) NOT NULL,
    `recipient_name` VARCHAR(120) DEFAULT NULL,
    `subject`      VARCHAR(255) NOT NULL,
    `body_html`    MEDIUMTEXT NOT NULL,
    `body_plain`   MEDIUMTEXT NOT NULL,
    `status`       ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `error_msg`    VARCHAR(512) DEFAULT NULL,
    `admin_id`     INT UNSIGNED DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_queue_batch` (`batch_id`),
    KEY `idx_queue_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_templates` (`name`, `subject`, `body_html`)
SELECT * FROM (
    SELECT 'Welcome Email' AS name,
           'Welcome to Biver Royalty Homes' AS subject,
           '<p>Dear {{name}},</p><p>Thank you for connecting with <strong>Biver Royalty Homes</strong>. We are delighted to assist you with your real estate journey in Nigeria.</p><p>Warm regards,<br>Biver Royalty Homes Team</p>' AS body_html
    UNION ALL
    SELECT 'Property Approved',
           'Your Property Listing Has Been Approved',
           '<p>Dear {{name}},</p><p>Great news! Your property listing has been <strong>approved</strong> and is now visible on our website.</p><p>Thank you for listing with Biver Royalty Homes.</p>'
    UNION ALL
    SELECT 'Property Rejected',
           'Update on Your Property Listing',
           '<p>Dear {{name}},</p><p>Thank you for submitting your property. After review, we are unable to approve this listing at this time.</p><p>Please contact us if you have questions or would like to resubmit with updated details.</p>'
    UNION ALL
    SELECT 'Property Verification Request',
           'Property Verification Required',
           '<p>Dear {{name}},</p><p>To complete your listing, we need to verify ownership details for your property.</p><p>Please reply with proof of ownership or contact our team at your earliest convenience.</p>'
    UNION ALL
    SELECT 'Promotional Campaign',
           'Exclusive Property Offers — Biver Royalty Homes',
           '<p>Dear {{name}},</p><p>Discover premium properties and exclusive offers from <strong>Biver Royalty Homes</strong>.</p><p>Visit our website to explore the latest listings in Owerri and across Nigeria.</p>'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `email_templates` LIMIT 1);
