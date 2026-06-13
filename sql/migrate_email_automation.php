<?php
/**
 * Upgrade email tables for automated workflow (event templates, extended logs).
 * Run: php sql/migrate_email_automation.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/EmailRepository.php';

EmailRepository::ensureTables();
EmailRepository::ensureSchema();

echo "Email automation migration complete.\n";
