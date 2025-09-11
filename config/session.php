<?php
/**
 * Session Configuration for Heritage Christian University Voting System
 * Secure session handling with proper security measures
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Configure session security settings only if session is not active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Session cookie
    
    // Start session
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Session timeout check
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // Last request was more than 1 hour ago
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Generate CSRF token for forms
 */
function generateCSRFToken() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in as admin
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * Check if user has specific admin role
 */
function hasAdminRole($role) {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
}

/**
 * Get current admin ID
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin username
 */
function getCurrentAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

/**
 * Get current admin role
 */
function getCurrentAdminRole() {
    return $_SESSION['admin_role'] ?? null;
}

/**
 * Destroy admin session
 */
function destroyAdminSession() {
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = '') {
    $log_entry = date('Y-m-d H:i:s') . " - " . $event;
    if ($details) {
        $log_entry .= " - " . $details;
    }
    $log_entry .= " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    
    $log_file = __DIR__ . '/../logs/security.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>