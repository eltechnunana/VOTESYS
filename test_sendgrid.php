<?php
/**
 * Test SendGrid SMTP connection directly
 */

require_once 'config/bootstrap.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>SendGrid SMTP Connection Test</h2>";

// Display configuration
echo "<h3>Configuration:</h3>";
echo "<ul>";
echo "<li>Host: " . getenv('MAIL_HOST') . "</li>";
echo "<li>Port: " . getenv('MAIL_PORT') . "</li>";
echo "<li>Username: " . getenv('MAIL_USERNAME') . "</li>";
echo "<li>Password: " . (getenv('MAIL_PASSWORD') ? '[SET - Length: ' . strlen(getenv('MAIL_PASSWORD')) . ']' : '[NOT SET]') . "</li>";
echo "<li>From Email: " . getenv('MAIL_FROM_EMAIL') . "</li>";
echo "</ul>";

// Test SMTP connection
echo "<h3>SMTP Connection Test:</h3>";

try {
    $mail = new PHPMailer(true);
    
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px; font-family: monospace;'>";
        echo "[Level $level] " . htmlspecialchars($str);
        echo "</div>";
    };
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = getenv('MAIL_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = getenv('MAIL_USERNAME');
    $mail->Password = getenv('MAIL_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = getenv('MAIL_PORT');
    
    // Disable SSL verification for testing
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Recipients
    $mail->setFrom(getenv('MAIL_FROM_EMAIL'), getenv('MAIL_FROM_NAME'));
    $mail->addAddress('test@example.com', 'Test User');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SendGrid Test Email';
    $mail->Body = '<h1>Test Email</h1><p>This is a test email from the voting system.</p>';
    
    echo "<p style='color: blue;'>Attempting to send email...</p>";
    
    $result = $mail->send();
    
    if ($result) {
        echo "<p style='color: green;'>✓ Email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send email</p>";
        echo "<p style='color: red;'>Error: " . $mail->ErrorInfo . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: orange;'>Stack trace:</p>";
    echo "<pre style='background: #f8f8f8; padding: 10px; overflow: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h3>Troubleshooting Tips:</h3>";
echo "<ul>";
echo "<li>Verify the SendGrid API key is valid and has 'Mail Send' permissions</li>";
echo "<li>Check if the 'From' email address is verified in SendGrid</li>";
echo "<li>Ensure the API key hasn't expired</li>";
echo "<li>Verify network connectivity to smtp.sendgrid.net:587</li>";
echo "</ul>";
?>