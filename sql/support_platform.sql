-- Biver Royalty Homes — Support platform (conversations, messages, CRM leads)
-- Run via sql/install_support_platform.php

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `support_conversations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `visitor_name` VARCHAR(120) DEFAULT NULL,
  `visitor_email` VARCHAR(190) DEFAULT NULL,
  `visitor_phone` VARCHAR(40) DEFAULT NULL,
  `status` ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
  `assigned_to` VARCHAR(120) DEFAULT NULL,
  `agent_id` INT UNSIGNED DEFAULT NULL,
  `lead_stage` ENUM('New','Contacted','Interested','Inspection Scheduled','Negotiating','Closed Sale') NOT NULL DEFAULT 'New',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_support_conv_session` (`session_id`),
  KEY `idx_support_conv_status` (`status`),
  KEY `idx_support_conv_lead_stage` (`lead_stage`),
  KEY `idx_support_conv_created` (`created_at`),
  CONSTRAINT `fk_support_conv_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_support_conv_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender` ENUM('user','bot','admin') NOT NULL,
  `message` TEXT NOT NULL,
  `chat_message_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_support_msg_conversation` (`conversation_id`),
  KEY `idx_support_msg_created` (`created_at`),
  CONSTRAINT `fk_support_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED DEFAULT NULL,
  `session_id` INT UNSIGNED DEFAULT NULL,
  `visitor_name` VARCHAR(120) NOT NULL,
  `visitor_email` VARCHAR(190) DEFAULT NULL,
  `visitor_phone` VARCHAR(40) NOT NULL,
  `question` TEXT NOT NULL,
  `stage` ENUM('New','Contacted','Interested','Inspection Scheduled','Negotiating','Closed Sale') NOT NULL DEFAULT 'New',
  `assigned_to` VARCHAR(120) DEFAULT NULL,
  `source` VARCHAR(50) NOT NULL DEFAULT 'chatbot',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_leads_stage` (`stage`),
  KEY `idx_chat_leads_session` (`session_id`),
  KEY `idx_chat_leads_created` (`created_at`),
  CONSTRAINT `fk_chat_leads_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_leads_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

