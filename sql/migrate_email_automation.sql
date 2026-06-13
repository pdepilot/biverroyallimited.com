-- Automated email workflow schema upgrades
USE `biverroyal_estate`;

ALTER TABLE `email_templates`
    ADD COLUMN IF NOT EXISTS `event_key` VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `is_system` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `email_logs`
    ADD COLUMN IF NOT EXISTS `recipient_name` VARCHAR(120) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `email_type` VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `related_record_id` INT UNSIGNED DEFAULT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `uq_template_event` ON `email_templates` (`event_key`);
