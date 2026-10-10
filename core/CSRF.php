<?php
/**
 * CSRF Protection System
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/app.php';

class CSRF {
    
    /**
     * Generate or fetch current session CSRF Token
     */
    public static function token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Return hidden HTML input tag with CSRF Token
     */
    public static function field(): string {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF Token
     */
    public static function validate(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check POST Requests automatically and die on invalid token
     */
    public static function checkPost(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validate($submittedToken)) {
                http_response_code(403);
                die("<div style='font-family:sans-serif; text-align:center; padding:50px; background:#111827; color:#FFF;'>
                    <h2 style='color:#DC2626;'>403 Security Check Failed</h2>
                    <p>Invalid or expired CSRF token. Please return to the previous page and refresh.</p>
                </div>");
            }
        }
    }
}
