<?php
/**
 * Database Configuration - Rangsit CDP
 * PDO connection with MySQL/MariaDB
 */

 define('BASE_URL', 'http://localhost/cdp'); // Local development URL
// Database settings locally
define('DB_HOST', 'localhost');
define('DB_NAME', 'rangsit_cdp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');


// define('BASE_URL', 'http://cdp.rangsitcity.go.th');
// Database settings for production (uncomment when deploying)
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'rangsitadmin_rcm-cdp');
// define('DB_USER', 'rangsitadmin_rcm-cdp');
// define('DB_PASS', 'RcmCDP@2026');
// define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 *
 * @return PDO
 * @throws PDOException
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
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
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci",
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
