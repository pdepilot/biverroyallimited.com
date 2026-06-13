<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/AdminUserRepository.php';

AdminUserRepository::ensureSchema();
echo "Admin roles schema ready.\n";
