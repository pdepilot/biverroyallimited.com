-- Contact inquiries from public contact.php form
USE `biverroyal_estate`;

CREATE TABLE IF NOT EXISTS `contact_inquiries` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name`     VARCHAR(120) NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `phone`         VARCHAR(30)  DEFAULT NULL,
    `inquiry_type`  VARCHAR(50)  NOT NULL DEFAULT 'general',
    `message`       TEXT         NOT NULL,
    `status`        ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    `ip_address`    VARCHAR(45)  DEFAULT NULL,
    `user_agent`    VARCHAR(512) DEFAULT NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_status` (`status`),
    KEY `idx_contact_created` (`created_at`),
    KEY `idx_contact_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_replies` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `inquiry_id`  INT UNSIGNED NOT NULL,
    `admin_id`    INT UNSIGNED NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `body`        TEXT         NOT NULL,
    `sent_to`     VARCHAR(255) NOT NULL,
    `mail_sent`   TINYINT(1)   NOT NULL DEFAULT 0,
    `sent_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reply_inquiry` (`inquiry_id`),
    CONSTRAINT `fk_reply_inquiry`
        FOREIGN KEY (`inquiry_id`) REFERENCES `contact_inquiries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reply_admin`
        FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
