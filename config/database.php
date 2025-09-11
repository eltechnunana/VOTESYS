<?php
/**
 * Database Configuration for Heritage Christian University Voting System
 * Secure database connection with error handling
 */

// Load bootstrap to ensure environment variables are available
require_once __DIR__ . '/bootstrap.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'voting_system';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4; SET time_zone = '+00:00';"
                ]
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
}

/**
 * Get database connection
 * @return PDO Database connection
 */
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// CSRF Token generation moved to session.php

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to log audit trail
function logAudit($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null, $user_id = null, $admin_id = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Determine user type based on which ID is provided
        $user_type = $admin_id ? 'admin' : 'voter';
        
        $query = "INSERT INTO audit_logs (user_type, user_id, admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                  VALUES (:user_type, :user_id, :admin_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_type', $user_type);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':old_values', json_encode($old_values));
        $stmt->bindParam(':new_values', json_encode($new_values));
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

// Rate limiting function
function checkRateLimit($action, $limit = 5, $window = 300) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    if ($now - $data['start'] > $window) {
        $_SESSION['rate_limit'][$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
?>