<?php
/**
 * Application Settings & Environment Loader
 * ApexFinance - Secure Smart Loan System
 */

// Prevent direct execution outside app entry
if (!defined('APEX_LOADED')) {
    define('APEX_LOADED', true);
}

// Simple .env Loader
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, "\"' \t\n\r\0\x0B");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env from root directory
loadEnv(__DIR__ . '/../.env');

// Helper to get environment variables securely
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $_ENV[$key] ?? $default;
    }
    return $value;
}

// Define Constants
define('APP_NAME', env('APP_NAME', 'ApexFinance'));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost/finance'), '/'));
define('CURRENCY_SYMBOL', env('CURRENCY_SYMBOL', '$'));
define('APP_SECRET', env('APP_SECRET', 'apex_secure_key_default_998877'));

// Set Timezone
date_default_timezone_set('UTC');

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}
