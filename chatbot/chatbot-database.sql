-- ============================================================
-- Biver Royalty Homes — AI Chat Assistant Database Schema
-- Run against database: biverroyal_estate
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Visitor users (chat participants, not admin accounts)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_uuid` CHAR(36) NOT NULL,
  `name` VARCHAR(120) DEFAULT NULL,
  `email` VARCHAR(190) DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_visitor_uuid` (`visitor_uuid`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Human chat agents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('online','away','offline') NOT NULL DEFAULT 'offline',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agents_email` (`email`),
  KEY `idx_agents_status` (`status`),
  KEY `idx_agents_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Chat sessions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_uuid` CHAR(36) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `agent_id` INT UNSIGNED DEFAULT NULL,
  `mode` ENUM('bot','human') NOT NULL DEFAULT 'bot',
  `status` ENUM('waiting','assigned','active','closed') NOT NULL DEFAULT 'active',
  `subject` VARCHAR(255) DEFAULT NULL,
  `visitor_name` VARCHAR(120) DEFAULT NULL,
  `visitor_email` VARCHAR(190) DEFAULT NULL,
  `visitor_phone` VARCHAR(40) DEFAULT NULL,
  `page_url` VARCHAR(500) DEFAULT NULL,
  `unread_admin` INT UNSIGNED NOT NULL DEFAULT 0,
  `unread_visitor` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chat_sessions_uuid` (`session_uuid`),
  KEY `idx_chat_sessions_user_id` (`user_id`),
  KEY `idx_chat_sessions_agent_id` (`agent_id`),
  KEY `idx_chat_sessions_status` (`status`),
  KEY `idx_chat_sessions_mode` (`mode`),
  KEY `idx_chat_sessions_last_message` (`last_message_at`),
  CONSTRAINT `fk_chat_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_sessions_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Chat messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('visitor','bot','agent','system') NOT NULL,
  `sender_id` INT UNSIGNED DEFAULT NULL,
  `message` TEXT NOT NULL,
  `message_type` ENUM('text','escalation','system','inspection','property') NOT NULL DEFAULT 'text',
  `delivery_status` ENUM('sent','delivered','read') NOT NULL DEFAULT 'sent',
  `metadata` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_messages_session_id` (`session_id`),
  KEY `idx_chat_messages_created_at` (`created_at`),
  KEY `idx_chat_messages_sender` (`sender_type`, `sender_id`),
  CONSTRAINT `fk_chat_messages_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Intent definitions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_intents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intent_key` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `keywords` JSON NOT NULL,
  `priority` INT NOT NULL DEFAULT 50,
  `confidence_threshold` DECIMAL(4,2) NOT NULL DEFAULT 0.35,
  `handler_class` VARCHAR(120) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chatbot_intents_key` (`intent_key`),
  KEY `idx_chatbot_intents_active` (`is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Intent response templates
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_responses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intent_id` INT UNSIGNED NOT NULL,
  `response_text` TEXT NOT NULL,
  `weight` INT NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_responses_intent` (`intent_id`, `is_active`),
  CONSTRAINT `fk_chatbot_responses_intent` FOREIGN KEY (`intent_id`) REFERENCES `chatbot_intents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- FAQ entries
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_faqs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(500) NOT NULL,
  `answer` TEXT NOT NULL,
  `keywords` JSON DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT 'general',
  `priority` INT NOT NULL DEFAULT 50,
  `match_score_threshold` DECIMAL(4,2) NOT NULL DEFAULT 0.40,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_faqs_category` (`category`, `is_active`),
  FULLTEXT KEY `ft_chatbot_faqs_question` (`question`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Knowledge base articles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_knowledgebase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `keywords` JSON DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT 'general',
  `priority` INT NOT NULL DEFAULT 50,
  `match_score_threshold` DECIMAL(4,2) NOT NULL DEFAULT 0.35,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_kb_category` (`category`, `is_active`),
  FULLTEXT KEY `ft_chatbot_kb_title_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Support tickets (human escalation)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `ticket_number` VARCHAR(20) NOT NULL,
  `question` TEXT NOT NULL,
  `status` ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `assigned_agent_id` INT UNSIGNED DEFAULT NULL,
  `visitor_name` VARCHAR(120) DEFAULT NULL,
  `visitor_email` VARCHAR(190) DEFAULT NULL,
  `visitor_phone` VARCHAR(40) DEFAULT NULL,
  `resolution_notes` TEXT DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_support_tickets_number` (`ticket_number`),
  KEY `idx_support_tickets_session` (`session_id`),
  KEY `idx_support_tickets_status` (`status`),
  KEY `idx_support_tickets_agent` (`assigned_agent_id`),
  CONSTRAINT `fk_support_tickets_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_support_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_support_tickets_agent` FOREIGN KEY (`assigned_agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Visitor activity logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `page_url` VARCHAR(500) DEFAULT NULL,
  `referrer` VARCHAR(500) DEFAULT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `event_data` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_visitor_logs_session` (`session_id`),
  KEY `idx_visitor_logs_user` (`user_id`),
  KEY `idx_visitor_logs_event` (`event_type`, `created_at`),
  KEY `idx_visitor_logs_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Admin notification queue
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(64) NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `recipient` VARCHAR(190) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `body` TEXT DEFAULT NULL,
  `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_queue_status` (`status`, `created_at`),
  KEY `idx_notification_queue_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO `agents` (`name`, `email`, `status`, `is_active`) VALUES
('Property Consultant', 'consultant@biverroyaltyhomes.com', 'online', 1);

INSERT INTO `chatbot_intents` (`intent_key`, `name`, `description`, `keywords`, `priority`, `confidence_threshold`) VALUES
('greetings', 'Greetings', 'Welcome and hello messages', '["hello","hi","hey","good morning","good afternoon","good evening","how are you","greetings","hola"]', 100, 0.30),
('thanks', 'Thanks', 'Gratitude expressions', '["thanks","thank you","appreciate it","thank u","thx","grateful"]', 90, 0.35),
('farewell', 'Farewell', 'Goodbye messages', '["bye","goodbye","see you","see ya","good night","take care","later"]', 85, 0.35),
('property_purchase', 'Property Purchase', 'Buying homes and properties', '["buy house","purchase house","home for sale","property for sale","buy property","buy home","purchase property","homes for sale","house for sale"]', 80, 0.35),
('land_purchase', 'Land Purchase', 'Land and plot inquiries', '["land","plot","plots","acre","acreage","buy land","land for sale","plot for sale","buy plot"]', 80, 0.35),
('rental_properties', 'Rental Properties', 'Rent and lease inquiries', '["rent","lease","apartment","flat","duplex rental","rental","for rent","rent a house","rent property"]', 80, 0.35),
('location', 'Location Questions', 'Office and address inquiries', '["where","address","location","office","find you","directions","located","office address"]', 75, 0.35),
('contact', 'Contact Questions', 'Phone email support', '["phone","contact","email","support","call","reach","whatsapp","number"]', 75, 0.35),
('inspection', 'Inspection Booking', 'Schedule visits and inspections', '["inspection","visit","schedule","appointment","book inspection","view property","site visit","tour"]', 85, 0.35),
('pricing', 'Pricing Guidance', 'Price and budget questions', '["price","pricing","cost","budget","how much","afford","payment plan","installment","fees"]', 70, 0.35),
('company_info', 'Company Information', 'About the company', '["about","company","who are you","biver","royalty","services","what do you do","about us"]', 65, 0.35);

INSERT INTO `chatbot_responses` (`intent_id`, `response_text`, `weight`) VALUES
((SELECT id FROM chatbot_intents WHERE intent_key='greetings' LIMIT 1), 'Hello and welcome to Biver Royalty Homes. How may I assist you today?', 1),
((SELECT id FROM chatbot_intents WHERE intent_key='greetings' LIMIT 1), 'Good day! I am your virtual property assistant at Biver Royalty Homes. What can I help you with?', 1),
((SELECT id FROM chatbot_intents WHERE intent_key='thanks' LIMIT 1), 'You are welcome. Feel free to ask any property-related questions.', 1),
((SELECT id FROM chatbot_intents WHERE intent_key='thanks' LIMIT 1), 'My pleasure! I am here whenever you need assistance with properties, land, or rentals.', 1),
((SELECT id FROM chatbot_intents WHERE intent_key='farewell' LIMIT 1), 'Thank you for visiting Biver Royalty Homes. Have a wonderful day.', 1),
((SELECT id FROM chatbot_intents WHERE intent_key='farewell' LIMIT 1), 'Goodbye! We look forward to helping you find your dream property.', 1);

INSERT INTO `chatbot_faqs` (`question`, `answer`, `keywords`, `category`, `priority`) VALUES
('What services does Biver Royalty Homes offer?', 'Biver Royalty Homes offers property sales, land acquisition, luxury home listings, rental properties, property management, and investment advisory across Nigeria — with a strong presence in Owerri, Imo State.', '["services","offer","what do you do"]', 'company', 90),
('Do you help with land documentation?', 'Yes. We guide clients through land verification, survey coordination, and documentation support. Our consultants ensure every transaction follows proper legal due diligence.', '["documentation","title","survey","legal"]', 'land', 85),
('Can I list my property with you?', 'Absolutely. Visit our List Your Property page or contact us directly. Our team will review your submission and guide you through the listing process.', '["list","sell my property","submit"]', 'company', 80),
('What areas do you cover?', 'We primarily serve Owerri and surrounding areas in Imo State, with select premium listings across Nigeria. Ask about a specific location and I can check available properties.', '["areas","locations","coverage","owerri"]', 'location', 85),
('Do you offer payment plans?', 'Selected developments and properties may include flexible payment plans. Share your budget and preferred property type, and our consultants will recommend suitable options.', '["payment plan","installment","financing"]', 'pricing', 80);

INSERT INTO `chatbot_knowledgebase` (`title`, `content`, `keywords`, `category`, `priority`) VALUES
('About Biver Royalty Homes', 'Biver Royalty Homes Ltd is a premium real estate company built on integrity, transparency, and exceptional client service. We specialize in luxury homes, land sales, rental properties, and real estate investment opportunities across Nigeria.', '["about","company","biver","royalty","who"]', 'company', 90),
('Property Purchase Process', 'Our property purchase process includes: (1) Initial consultation, (2) Property shortlisting, (3) Site inspection, (4) Due diligence and documentation review, (5) Payment and handover. Our team supports you at every step.', '["buy","purchase","process","how to buy"]', 'purchase', 85),
('Rental Application Process', 'To rent a property: browse available listings, schedule a viewing, submit your application with valid ID and references, sign the lease agreement, and complete payment. Contact us for current rental availability.', '["rent","lease","application","tenant"]', 'rental', 85),
('Land Investment Guide', 'Land investment with Biver Royalty Homes includes verified plots, clear titles, strategic locations, and growth potential. We provide site visits, pricing guidance, and documentation support for secure land acquisition.', '["land","investment","plot","acre"]', 'land', 85),
('Inspection and Site Visits', 'Schedule a property inspection by sharing your preferred date, property of interest, and contact details. Our agents will confirm your appointment and accompany you on site.', '["inspection","visit","viewing","appointment"]', 'inspection', 90);
