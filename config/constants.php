<?php
/**
 * Heritage Christian University Online Voting System
 * System Constants Configuration
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Session timeout settings (in seconds)
define('VOTER_SESSION_TIMEOUT', 3600);      // 1 hour
define('ADMIN_SESSION_TIMEOUT', 7200);      // 2 hours

// Encryption settings
define('ENCRYPTION_KEY', 'HCU_VOTING_2024_SECURE_KEY_CHANGE_IN_PRODUCTION');

// Security settings
define('VALIDATE_SESSION_IP', false);       // Set to true for stricter security
define('MAX_LOGIN_ATTEMPTS', 5);            // Maximum login attempts
define('RATE_LIMIT_WINDOW', 900);           // Rate limit window in seconds (15 minutes)

// Development settings
define('DEVELOPMENT_MODE', true);           // Set to false in production

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Election settings
define('CURRENT_ELECTION_ID', 12);          // Active election ID (CESA)
define('TIMEZONE', 'UTC');

// Voting settings
define('VOTE_HASH_ALGORITHM', 'sha256');

// File upload settings
define('MAX_FILE_SIZE', 5242880);           // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif');

// Set timezone
date_default_timezone_set(TIMEZONE);

?>