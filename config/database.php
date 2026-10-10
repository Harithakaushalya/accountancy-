<?php
/**
 * Database Singleton Configuration
 * Uses PDO with Prepared Statements for maximum SQL Injection Security
 */

require_once __DIR__ . '/app.php';

class DatabaseConnection {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $dbname = env('DB_NAME', 'finance_db');
            $username = env('DB_USER', 'root');
            $password = env('DB_PASS', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Strict native prepared statements
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Return a clean error screen instead of leaking database server passwords/credentials
                error_log("Database Connection Error: " . $e->getMessage());
                die("<div style='font-family:sans-serif; padding:40px; text-align:center; background:#111827; color:#FFFFFF;'>
                    <h2 style='color:#DC2626;'>Database Connection Error</h2>
                    <p>Unable to connect to database host. Please verify your <code>.env</code> file configuration or ensure MySQL service is running in XAMPP.</p>
                </div>");
            }
        }
        return self::$instance;
    }
}
