<?php
// config/database.php
// Single PDO connection, reused everywhere.

define('DB_HOST', 'localhost');
define('DB_NAME', 'resume_builder');
define('DB_USER', 'root');
define('DB_PASS', '');  // <-- change if your MySQL has a password

class Database {
    private static $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        }
        return self::$instance;
    }
}