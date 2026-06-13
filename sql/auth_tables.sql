-- =============================================================================
-- Biver Royal Estate - Admin Authentication Schema
-- Database: biverroyal_estate
-- Run this script once in phpMyAdmin or: mysql -u root biverroyal_estate < auth_tables.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `biverroyal_estate`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `biverroyal_estate`;

-- -----------------------------------------------------------------------------
-- Admin user accounts (passwords stored with password_hash / bcrypt)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`         VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name`     VARCHAR(120) DEFAULT 'Administrator',
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Per-IP failed login counter (resets on success or after lockout applied)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address`       VARCHAR(45)  NOT NULL,
    `failed_attempts`  INT UNSIGNED NOT NULL DEFAULT 0,
    `first_attempt_at` DATETIME     DEFAULT NULL,
    `last_attempt_at`  DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_login_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Active IP restrictions (temporary; never permanent lifetime bans)
-- lockout_level: 1 = 72 hours, 2 = 30 days, 3 = manual review required
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_lockouts` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address`              VARCHAR(45)  NOT NULL,
    `ban_reason`              VARCHAR(500) NOT NULL,
    `lockout_level`           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `requires_manual_review`  TINYINT(1)   NOT NULL DEFAULT 0,
    `locked_at`               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`              DATETIME     DEFAULT NULL COMMENT 'NULL when manual review required',
    `is_active`               TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_lockout_ip_active` (`ip_address`, `is_active`),
    KEY `idx_lockout_expires` (`expires_at`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Historical lockout record for progressive penalties (repeat offenders)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lockout_history` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address`              VARCHAR(45)  NOT NULL,
    `lockout_level`           TINYINT UNSIGNED NOT NULL,
    `ban_reason`              VARCHAR(500) NOT NULL,
    `locked_at`               DATETIME     NOT NULL,
    `expires_at`              DATETIME     DEFAULT NULL,
    `lifted_at`               DATETIME     DEFAULT NULL COMMENT 'Auto-unban or manual lift timestamp',
    `lift_method`             ENUM('auto_expiry','manual','login_success') DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_history_ip` (`ip_address`),
    KEY `idx_history_locked` (`locked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Security audit trail
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_audit_log` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type`  ENUM(
                    'login_success',
                    'login_failed',
                    'ip_restricted',
                    'lockout_lifted',
                    'logout',
                    'session_expired',
                    'csrf_failure',
                    'manual_review_block'
                  ) NOT NULL,
    `admin_id`    INT UNSIGNED DEFAULT NULL,
    `ip_address`  VARCHAR(45)  NOT NULL,
    `user_agent`  VARCHAR(512) DEFAULT NULL,
    `details`     TEXT         DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_event` (`event_type`),
    KEY `idx_audit_ip` (`ip_address`),
    KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Seed default administrator (password: Echefu10@321@)
-- Hash generated with: password_hash('Echefu10@321@', PASSWORD_BCRYPT)
-- Change password after first login in production.
-- -----------------------------------------------------------------------------
INSERT INTO `admin_users` (`email`, `password_hash`, `full_name`, `is_active`)
VALUES (
    'admin@biverroyalty.com',
    '$2y$10$UdBsjOMP8dm9k10cJi1gmeNmM6YkYDJCldeSHp0U8Y5eGeTJ9viwa',
    'Biver Royalty Administrator',
    1
)
ON DUPLICATE KEY UPDATE `email` = `email`;
