<?php
/**
 * Bootstrap file for loading environment variables
 * This file should be included at the beginning of the application
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables manually from .env file
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        error_log('Warning: .env file not found at ' . $filePath);
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    error_log('Environment variables loaded successfully from .env file');
    return true;
}

// Load the .env file
loadEnvFile(__DIR__ . '/../.env');

// Set default timezone
if (isset($_ENV['TIMEZONE'])) {
    date_default_timezone_set($_ENV['TIMEZONE']);
} else {
    date_default_timezone_set('UTC');
}

// Set error reporting based on environment
if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>