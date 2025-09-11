<?php
/**
 * Heritage Christian University Online Voting System
 * Security Configuration and Utilities
 * Enhanced security measures for the voting system
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Include constants
require_once __DIR__ . '/constants.php';

/**
 * Security Configuration Class
 */
class SecurityConfig {
    
    // Security headers
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict transport security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net https://ajax.googleapis.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.datatables.net; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self'");
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    // Rate limiting configuration
    public static function checkRateLimit($action, $identifier, $maxAttempts = 5, $timeWindow = 300) {
        $key = "rate_limit_{$action}_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window has passed
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    // IP validation and blocking
    public static function validateIP() {
        $ip = self::getRealIP();
        
        // Check for blocked IPs (you can expand this list)
        $blockedIPs = [
            // Add blocked IP addresses here
        ];
        
        if (in_array($ip, $blockedIPs)) {
            self::logSecurityEvent('blocked_ip_access', $ip);
            http_response_code(403);
            die('Access denied');
        }
        
        return $ip;
    }
    
    // Get real IP address
    public static function getRealIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    // Log security events
    public static function logSecurityEvent($event, $details = '', $severity = 'medium') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => self::getRealIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details,
            'severity' => $severity,
            'session_id' => session_id()
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Write to log file
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Also log to system error log for high severity events
        if ($severity === 'high') {
            error_log("SECURITY ALERT: {$event} - IP: {$logEntry['ip']} - Details: {$details}");
        }
    }
}

/**
 * Input Validation and Sanitization Class
 */
class InputValidator {
    
    // Validate and sanitize student ID
    public static function validateStudentId($studentId) {
        $studentId = trim($studentId);
        
        if (empty($studentId)) {
            throw new InvalidArgumentException('Student ID is required');
        }
        
        if (!preg_match('/^[A-Z0-9-]{6,20}$/i', $studentId)) {
            throw new InvalidArgumentException('Invalid student ID format');
        }
        
        return strtoupper($studentId);
    }
    
    // Validate email
    public static function validateEmail($email) {
        $email = trim($email);
        
        if (empty($email)) {
            throw new InvalidArgumentException('Email is required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
        
        return strtolower($email);
    }
    
    // Validate password
    public static function validatePassword($password) {
        if (empty($password)) {
            throw new InvalidArgumentException('Password is required');
        }
        
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            throw new InvalidArgumentException('Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }
        
        return $password;
    }
    
    // Sanitize general text input
    public static function sanitizeText($text, $maxLength = 255) {
        $text = trim($text);
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }
        
        return $text;
    }
    
    // Validate integer input
    public static function validateInteger($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Value must be a number');
        }
        
        $value = (int)$value;
        
        if ($min !== null && $value < $min) {
            throw new InvalidArgumentException("Value must be at least {$min}");
        }
        
        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("Value must be at most {$max}");
        }
        
        return $value;
    }
    
    // Validate file upload
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 2097152) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('No file uploaded or invalid upload');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload error: ' . $file['error']);
        }
        
        if ($file['size'] > $maxSize) {
            throw new InvalidArgumentException('File size exceeds maximum allowed size');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new InvalidArgumentException('Invalid file type');
        }
        
        return true;
    }
}

/**
 * Encryption and Hashing Utilities
 */
class CryptoUtils {
    
    // Encrypt sensitive data
    public static function encrypt($data, $key = null) {
        if ($key === null) {
            $key = ENCRYPTION_KEY;
        }
        
        $cipher = 'AES-256-GCM';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    // Decrypt sensitive data
    public static function decrypt($encryptedData, $key = null) {
        if ($key === null) {
            $key = ENCRYPTION_KEY;
        }
        
        $data = base64_decode($encryptedData);
        $cipher = 'AES-256-GCM';
        $ivLength = openssl_cipher_iv_length($cipher);
        
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $encrypted = substr($data, $ivLength + 16);
        
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }
        
        return $decrypted;
    }
    
    // Generate secure random token
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Hash password securely
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Session Security Manager
 */
class SessionSecurity {
    
    // Initialize secure session
    public static function initializeSecureSession() {
        // Configure session settings only if session hasn't started yet
        if (session_status() === PHP_SESSION_NONE) {
            // Session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            ini_set('session.gc_maxlifetime', VOTER_SESSION_TIMEOUT);
            
            // Start session
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Validate session
        self::validateSession();
    }
    
    // Validate session integrity
    public static function validateSession() {
        // Determine appropriate timeout based on session type
        $timeout = VOTER_SESSION_TIMEOUT; // Default to voter timeout
        if (isset($_SESSION['admin_id'])) {
            $timeout = ADMIN_SESSION_TIMEOUT;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Validate IP address (optional - can cause issues with mobile users)
        if (VALIDATE_SESSION_IP && isset($_SESSION['ip_address'])) {
            if ($_SESSION['ip_address'] !== SecurityConfig::getRealIP()) {
                SecurityConfig::logSecurityEvent('session_ip_mismatch', 'IP changed during session', 'high');
                self::destroySession();
                return false;
            }
        }
        
        // Validate user agent (basic check)
        if (isset($_SESSION['user_agent'])) {
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $currentUserAgent) {
                SecurityConfig::logSecurityEvent('session_ua_mismatch', 'User agent changed during session', 'medium');
                // Don't destroy session for user agent changes as they can be legitimate
            }
        }
        
        return true;
    }
    
    // Destroy session securely
    public static function destroySession() {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    // Set session data securely
    public static function setSessionData($key, $value) {
        $_SESSION[$key] = $value;
        
        // Set additional security data on login
        if ($key === 'voter_id') {
            $_SESSION['ip_address'] = SecurityConfig::getRealIP();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['login_time'] = time();
        }
    }
}

// Initialize security headers
SecurityConfig::setSecurityHeaders();

// Validate IP address
SecurityConfig::validateIP();

// Initialize secure session
SessionSecurity::initializeSecureSession();

?>