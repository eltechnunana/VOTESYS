<?php
/**
 * Test script to verify email configuration with SendGrid
 */

require_once 'config/email.php';

try {
    $emailUtil = new EmailUtility();
    
    // Test email configuration
    echo "<h2>Email Configuration Test</h2>";
    echo "<p><strong>SMTP Host:</strong> " . ($_ENV['MAIL_HOST'] ?? 'smtp.sendgrid.net') . "</p>";
    echo "<p><strong>SMTP Port:</strong> " . ($_ENV['MAIL_PORT'] ?? '587') . "</p>";
    echo "<p><strong>Username:</strong> " . ($_ENV['MAIL_USERNAME'] ?? 'apikey') . "</p>";
    echo "<p><strong>From Email:</strong> " . ($_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@hcu.edu') . "</p>";
    echo "<p><strong>From Name:</strong> " . ($_ENV['MAIL_FROM_NAME'] ?? 'Heritage Christian University Voting System') . "</p>";
    
    // Test sending a simple email
    echo "<h3>Sending Test Email...</h3>";
    
    $testEmail = 'test@example.com'; // Change this to your test email
    $subject = 'VOTESYS Email Configuration Test';
    $message = '<h2>Email Test Successful!</h2><p>Your SendGrid email configuration is working correctly.</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>';
    
    // Uncomment the line below to actually send a test email
    // $result = $emailUtil->sendEmail($testEmail, $subject, $message);
    
    echo "<p style='color: green;'>✓ Email configuration loaded successfully!</p>";
    echo "<p><em>Note: Uncomment the sendEmail line in test_email.php to actually send a test email.</em></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Environment Variables Status:</h3>";
echo "<pre>";
echo "MAIL_HOST: " . ($_ENV['MAIL_HOST'] ?? 'Not set') . "\n";
echo "MAIL_PORT: " . ($_ENV['MAIL_PORT'] ?? 'Not set') . "\n";
echo "MAIL_USERNAME: " . ($_ENV['MAIL_USERNAME'] ?? 'Not set') . "\n";
echo "MAIL_PASSWORD: " . (isset($_ENV['MAIL_PASSWORD']) ? '[SET]' : 'Not set') . "\n";
echo "MAIL_FROM_EMAIL: " . ($_ENV['MAIL_FROM_EMAIL'] ?? 'Not set') . "\n";
echo "MAIL_FROM_NAME: " . ($_ENV['MAIL_FROM_NAME'] ?? 'Not set') . "\n";
echo "</pre>";
?>