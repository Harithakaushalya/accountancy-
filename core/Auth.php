<?php
/**
 * Authentication & Role-Based Access Control (RBAC) Engine
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Helpers.php';

class Auth {
    
    /**
     * Authenticate user credentials securely
     */
    public static function login(string $email, string $password): bool {
        $db = DatabaseConnection::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                setFlash('danger', 'Your account is ' . $user['status'] . '. Please contact administration.');
                return false;
            }

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Store user session payload
            $_SESSION['user'] = [
                'id'             => $user['id'],
                'account_number' => $user['account_number'],
                'name'           => $user['name'],
                'email'          => $user['email'],
                'role'           => $user['role'],
                'phone'          => $user['phone']
            ];

            auditLog('LOGIN_SUCCESS', "User {$user['email']} logged in successfully as {$user['role']}.");
            return true;
        }

        auditLog('LOGIN_FAILED', "Failed login attempt for email: {$email}");
        setFlash('danger', 'Invalid email address or password.');
        return false;
    }

    /**
     * Logout and destroy session securely
     */
    public static function logout(): void {
        if (self::check()) {
            auditLog('LOGOUT', "User {$_SESSION['user']['email']} logged out.");
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Check if current visitor is authenticated
     */
    public static function check(): bool {
        return isset($_SESSION['user']['id']);
    }

    /**
     * Get active logged in user array
     */
    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get active logged in user role
     */
    public static function role(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }

    /**
     * Enforce authentication gate on protected routes
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            setFlash('warning', 'Please login to access this area.');
            redirect('login.php');
        }
    }

    /**
     * Strict RBAC Permission Enforcer
     * Example: Auth::requireRole(['admin', 'officer'])
     */
    public static function requireRole(array $allowedRoles): void {
        self::requireLogin();
        $userRole = self::role();

        if (!in_array($userRole, $allowedRoles, true)) {
            auditLog('UNAUTHORIZED_ACCESS_ATTEMPT', "Role {$userRole} attempted to access restricted route.");
            http_response_code(403);
            die("<div style='font-family:sans-serif; text-align:center; padding:60px; background:#111827; color:#FFF;'>
                <h1 style='color:#DC2626; font-size:48px; margin-bottom:10px;'>403</h1>
                <h3 style='margin-bottom:20px;'>Access Denied</h3>
                <p style='color:#9CA3AF;'>You do not have permission to view this resource.</p>
                <a href='" . APP_URL . "/dashboard.php' style='display:inline-block; margin-top:20px; padding:10px 24px; background:#DC2626; color:#FFF; text-decoration:none; border-radius:6px; font-weight:bold;'>Return to Dashboard</a>
            </div>");
        }
    }
}
