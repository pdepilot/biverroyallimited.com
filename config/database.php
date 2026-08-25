<?php
/**
 * PDO database connection for Biver Royal Estate.
 *
 * Credentials load in this order:
 * 1. config/database.local.php (per-machine secrets — not committed to git)
 * 2. Environment variables: DB_HOST, DB_NAME, DB_USER, DB_PASS
 * 3. Defaults below (Hostinger database name/user; password must be set locally)
 *
 * Local XAMPP: copy database.local.php.example → database.local.php and use root + empty password.
 * Hostinger:    same file on the server with your MySQL password from hPanel.
 */

declare(strict_types=1);

$localConfig = __DIR__ . '/database.local.php';
if (is_readable($localConfig)) {
    require $localConfig;
}

if (!defined('DB_HOST')) {
    $host = getenv('DB_HOST');
    define('DB_HOST', ($host !== false && $host !== '') ? $host : 'localhost');
}

if (!defined('DB_NAME')) {
    $name = getenv('DB_NAME');
    define('DB_NAME', ($name !== false && $name !== '') ? $name : 'u292007149_biverroyalty');
}

if (!defined('DB_USER')) {
    $user = getenv('DB_USER');
    define('DB_USER', ($user !== false && $user !== '') ? $user : 'u292007149_biverroyalty');
}

if (!defined('DB_PASS')) {
    $pass = getenv('DB_PASS');
    define('DB_PASS', $pass !== false ? $pass : '');
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

/**
 * Returns a singleton PDO instance with secure defaults.
 *
 * @return PDO
 * @throws PDOException When connection fails
 */
function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    return $pdo;
}
