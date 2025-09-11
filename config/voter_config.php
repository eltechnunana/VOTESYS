<?php
/**
 * Heritage Christian University Online Voting System
 * Voter Configuration and Authentication
 */

// Prevent multiple inclusions
if (defined('VOTER_CONFIG_LOADED')) {
    return;
}
define('VOTER_CONFIG_LOADED', true);

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Include required files
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

/**
 * Voter Database Singleton Class
 */
class VoterDatabase {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $database = new Database();
        $this->connection = $database->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

/**
 * Voter Security Class
 */
class VoterSecurity {
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    

    
    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure session ID
     */
    public static function generateSessionId() {
        return bin2hex(random_bytes(32));
    }
}

/**
 * Voter Authentication Class
 */
class VoterAuth {
    private $db;
    private $currentVoter = null;
    
    public function __construct() {
        $this->db = VoterDatabase::getInstance()->getConnection();
        $this->startSecureSession();
    }
    
    /**
     * Start secure session
     */
    private function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Login voter with student ID and password
     */
    public function login($student_id, $password, $election_id = null) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM voters WHERE student_id = ? AND is_active = 1");
            $stmt->execute([$student_id]);
            $user = $stmt->fetch();
            
            if ($user && VoterSecurity::verifyPassword($password, $user['password'])) {
                return $this->setUserSession($user, $election_id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Login voter with student ID only (for simplified authentication)
     */
    public function loginWithStudentId($student_id, $election_id = null) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM voters WHERE student_id = ? AND is_active = 1");
            $stmt->execute([$student_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                return $this->setUserSession($user, $election_id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Student ID login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Login voter with access token (for token-based authentication)
     */
    public function loginWithToken($token, $election_id = null) {
        try {
            // For now, implement a simple token validation
            // In a real implementation, you'd have a tokens table
            $stmt = $this->db->prepare("SELECT * FROM voters WHERE student_id = ? AND is_active = 1");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                return $this->setUserSession($user, $election_id);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Token login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set user session variables
     */
    private function setUserSession($user, $election_id = null) {
        // Set session variables
        $_SESSION['voter_id'] = $user['id'];
        $_SESSION['voter_student_id'] = $user['student_id'];
        $_SESSION['voter_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['voter_logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        if ($election_id) {
            $_SESSION['current_election_id'] = $election_id;
        }
        
        // Update last login (add last_login column if it doesn't exist)
        try {
            $updateStmt = $this->db->prepare("UPDATE voters SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
        } catch (PDOException $e) {
            // Column might not exist, ignore this error
            error_log("Last login update failed: " . $e->getMessage());
        }
        
        $this->currentVoter = $user;
        return true;
    }
    
    /**
     * Log voter activity
     */
    public function logVoterActivity($student_id, $election_id, $activity) {
        try {
            // Check if audit_logs table exists and has the right structure
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_type, user_id, action, details, ip_address, user_agent, created_at) 
                VALUES ('voter', ?, ?, ?, ?, ?, NOW())
            ");
            
            $details = json_encode([
                'student_id' => $student_id,
                'election_id' => $election_id,
                'activity' => $activity
            ]);
            
            $stmt->execute([
                $student_id,
                $activity,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Activity logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if voter is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['voter_logged_in']) || !$_SESSION['voter_logged_in']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > VOTER_SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current voter
     */
    public function getCurrentVoter() {
        if ($this->currentVoter === null && $this->isLoggedIn()) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM voters WHERE id = ?");
                $stmt->execute([$_SESSION['voter_id']]);
                $this->currentVoter = $stmt->fetch();
            } catch (PDOException $e) {
                error_log("Get current voter error: " . $e->getMessage());
                return null;
            }
        }
        
        return $this->currentVoter;
    }
    
    /**
     * Check if voter has voted in current election
     */
    public function hasVotedInElection($voterId, $electionId = CURRENT_ELECTION_ID) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM votes WHERE voter_id = ? AND election_id = ?");
            $stmt->execute([$voterId, $electionId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Check vote status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout voter
     */
    public function logout() {
        // Clear session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        $this->currentVoter = null;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: voter_login.php?error=Please log in to access this page.');
            exit();
        }
    }
}

/**
 * Global function to require voter authentication
 */
function requireVoterAuth() {
    $auth = new VoterAuth();
    $auth->requireAuth();
}

// Set security headers
SecurityConfig::setSecurityHeaders();

?>