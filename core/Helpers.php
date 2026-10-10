<?php
/**
 * Security & Utility Helpers
 * ApexFinance - Secure Smart Loan System
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/**
 * XSS Prevention: Safe Output Escaping Wrapper
 */
function e($value): string {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Currency Formatter
 */
function formatMoney($amount): string {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Clean Date Formatter
 */
function formatDate($dateStr, $format = 'M d, Y'): string {
    if (empty($dateStr)) return 'N/A';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Flash Notification Helpers
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

function getFlash(string $type): ?string {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function hasFlash(string $type): bool {
    return isset($_SESSION['flash'][$type]);
}

/**
 * Safe Redirect
 */
function redirect(string $path): void {
    $url = (strpos($path, 'http') === 0) ? $path : APP_URL . '/' . ltrim($path, '/');
    header("Location: {$url}");
    exit();
}

/**
 * Audit Logging Function
 */
function auditLog(string $action, string $details = ''): void {
    try {
        $db = DatabaseConnection::getInstance();
        $userId = $_SESSION['user']['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip)");
        $stmt->execute([
            ':user_id' => $userId,
            ':action'  => substr($action, 0, 100),
            ':details' => $details,
            ':ip'      => $ip
        ]);
    } catch (Exception $e) {
        // Audit log failure should not crash main execution
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Generate Unique Account Number
 */
function generateAccountNo(string $role): string {
    $prefix = match(strtolower($role)) {
        'admin' => 'ACC-ADM-',
        'officer' => 'ACC-OFF-',
        default => 'ACC-CUST-'
    };
    return $prefix . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
}

/**
 * Generate Unique Loan Reference Number
 */
function generateLoanNo(): string {
    return 'LN-' . date('Ym') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 5));
}

/**
 * Generate Unique Receipt Reference Number
 */
function generateReceiptNo(): string {
    return 'RCP-' . date('Ymd') . '-' . sprintf('%04d', mt_rand(1, 9999));
}
