<?php
/**
 * Heritage Christian University Online Voting System
 * Email Utility Class
 */

// Load bootstrap to ensure environment variables are available
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailUtility {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->config = $this->getEmailConfig();
        $this->setupMailer();
    }
    
    /**
     * Get email configuration from environment or defaults
     */
    private function getEmailConfig() {
        return [
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.sendgrid.net',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? 'apikey',
            'password' => $_ENV['MAIL_PASSWORD'] ?? 'your_sendgrid_api_key_here',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@hcu.edu',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Heritage Christian University Voting System'
        ];
    }
    
    /**
     * Setup PHPMailer configuration
     */
    private function setupMailer() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = $this->config['encryption'];
            $this->mailer->Port = $this->config['port'];
            
            // Enable SMTP debugging for troubleshooting
            if (getenv('APP_DEBUG') === 'true' || $_ENV['APP_DEBUG'] === 'true') {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug [$level]: $str");
                };
            }
            
            // Default sender
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Character set
            $this->mailer->CharSet = 'UTF-8';
            
            // Additional SMTP options for better compatibility
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Get the PHPMailer instance for debugging
     * 
     * @return PHPMailer The PHPMailer instance
     */
    public function getMailer() {
        return $this->mailer;
    }
    
    /**
     * Check if email configuration is properly set
     * 
     * @return bool True if email is configured
     */
    private function isEmailConfigured() {
        // Check if required email settings are present
        $required = ['host', 'username', 'password', 'from_email'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }
        
        // Test SMTP connection
        try {
            $testMailer = new PHPMailer(true);
            $testMailer->isSMTP();
            $testMailer->Host = $this->config['host'];
            $testMailer->SMTPAuth = true;
            $testMailer->Username = $this->config['username'];
            $testMailer->Password = $this->config['password'];
            $testMailer->SMTPSecure = $this->config['encryption'];
            $testMailer->Port = $this->config['port'];
            $testMailer->Timeout = 10; // Short timeout for quick check
            
            // Disable SSL verification for testing
            $testMailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Try to connect
            $testMailer->smtpConnect();
            $testMailer->smtpClose();
            return true;
            
        } catch (Exception $e) {
            error_log("Email configuration test failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send auto-generated password to voter
     * 
     * @param string $email Voter's email address
     * @param string $name Voter's full name
     * @param string $studentId Voter's student ID
     * @param string $password Auto-generated password
     * @param string $electionTitle Election title (optional)
     * @return bool Success status
     */
    public function sendPasswordEmail($email, $name, $studentId, $password, $electionTitle = '') {
        // Check if email is properly configured
        if (!$this->isEmailConfigured()) {
            error_log("Email not configured properly. Password for $studentId ($name): $password");
            return false;
        }
        try {
            // Clear any previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your Voting System Login Credentials - Heritage Christian University';
            
            $electionInfo = $electionTitle ? "<p><strong>Election:</strong> {$electionTitle}</p>" : '';
            
            $this->mailer->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                        .content { background-color: #f8f9fa; padding: 30px; border-radius: 5px; margin: 20px 0; }
                        .credentials { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .password { font-size: 18px; font-weight: bold; color: #dc3545; }
                        .footer { text-align: center; color: #6c757d; font-size: 12px; margin-top: 30px; }
                        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Heritage Christian University</h1>
                            <h2>Online Voting System</h2>
                        </div>
                        
                        <div class='content'>
                            <h3>Welcome, {$name}!</h3>
                            <p>Your voter registration has been successfully completed. Below are your login credentials for the online voting system.</p>
                            
                            {$electionInfo}
                            
                            <div class='credentials'>
                                <h4>Your Login Credentials:</h4>
                                <p><strong>Student ID:</strong> {$studentId}</p>
                                <p><strong>Password:</strong> <span class='password'>{$password}</span></p>
                            </div>
                            
                            <div class='warning'>
                                <h4>⚠️ Important Security Information:</h4>
                                <ul>
                                    <li>Keep your password secure and do not share it with anyone</li>
                                    <li>You can change your password after logging in</li>
                                    <li>If you suspect your account has been compromised, contact the administrator immediately</li>
                                    <li>This password is auto-generated for security purposes</li>
                                </ul>
                            </div>
                            
                            <p><strong>How to Login:</strong></p>
                            <ol>
                                <li>Visit the voter login page</li>
                                <li>Enter your Student ID and the password provided above</li>
                                <li>Follow the instructions to cast your vote</li>
                            </ol>
                            
                            <p>If you have any questions or need assistance, please contact the election administrator.</p>
                        </div>
                        
                        <div class='footer'>
                            <p>This is an automated message from the Heritage Christian University Online Voting System.</p>
                            <p>Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Alternative plain text version
            $this->mailer->AltBody = "
Heritage Christian University - Online Voting System\n\n
Welcome, {$name}!\n\nYour voter registration has been successfully completed. Below are your login credentials:\n\nStudent ID: {$studentId}\nPassword: {$password}\n\nIMPORTANT SECURITY INFORMATION:\n- Keep your password secure and do not share it with anyone\n- You can change your password after logging in\n- If you suspect your account has been compromised, contact the administrator immediately\n\nHow to Login:\n1. Visit the voter login page\n2. Enter your Student ID and the password provided above\n3. Follow the instructions to cast your vote\n\nIf you have any questions or need assistance, please contact the election administrator.\n\nThis is an automated message. Please do not reply to this email.\n";
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Password email sent successfully to: {$email}");
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mailer->ErrorInfo . " - " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Generate a secure random password
     * 
     * @param int $length Password length (default: 12)
     * @return string Generated password
     */
    public static function generateSecurePassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';
        
        // Ensure at least one character from each set
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest randomly
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
    
    /**
     * Test email configuration
     * 
     * @return bool Configuration is valid
     */
    public function testConfiguration() {
        try {
            return $this->mailer->smtpConnect();
        } catch (Exception $e) {
            error_log("Email configuration test failed: " . $e->getMessage());
            return false;
        }
    }
}
?>