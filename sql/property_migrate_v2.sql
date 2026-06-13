-- Add extended property fields (run once on existing installs)
USE `biverroyal_estate`;

ALTER TABLE `properties`
    ADD COLUMN IF NOT EXISTS `bedrooms`  TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER `location`,
    ADD COLUMN IF NOT EXISTS `bathrooms` TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER `bedrooms`,
    ADD COLUMN IF NOT EXISTS `area`      INT UNSIGNED NOT NULL DEFAULT 0 AFTER `bathrooms`;
