<?php
/**
 * Actual email sending test script
 */

require_once 'config/email.php';

try {
    $emailUtil = new EmailUtility();
    
    echo "<h2>Email Configuration Test</h2>";
    echo "<p><strong>SMTP Host:</strong> " . ($_ENV['MAIL_HOST'] ?? 'smtp.sendgrid.net') . "</p>";
    echo "<p><strong>SMTP Port:</strong> " . ($_ENV['MAIL_PORT'] ?? '587') . "</p>";
    echo "<p><strong>Username:</strong> " . ($_ENV['MAIL_USERNAME'] ?? 'apikey') . "</p>";
    echo "<p><strong>From Email:</strong> " . ($_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@hcu.edu') . "</p>";
    echo "<p><strong>From Name:</strong> " . ($_ENV['MAIL_FROM_NAME'] ?? 'Heritage Christian University Voting System') . "</p>";
    
    // Test SMTP connection first
    echo "<h3>Testing SMTP Connection...</h3>";
    $connectionTest = $emailUtil->testConfiguration();
    
    if ($connectionTest) {
        echo "<p style='color: green;'>✓ SMTP connection successful!</p>";
    } else {
        echo "<p style='color: red;'>✗ SMTP connection failed!</p>";
    }
    
    // Test actual email sending
    echo "<h3>Testing Email Sending...</h3>";
    
    // Test with the configured from email address
    $testEmail = $_ENV['MAIL_FROM_EMAIL'] ?? 'voteheritage@gmail.com';
    $testName = 'Test User';
    $testStudentId = 'TEST001';
    $testPassword = 'TestPass123!';
    
    echo "<p><strong>Test Email:</strong> $testEmail</p>";
    echo "<p><em>Sending test email to the configured from address for verification...</em></p>";
    
    // Send actual test email
    $result = $emailUtil->sendPasswordEmail($testEmail, $testName, $testStudentId, $testPassword, 'Email Configuration Test');
    
    if ($result) {
        echo "<p style='color: green;'>✓ Test email sent successfully!</p>";
        echo "<p>Check the inbox of: $testEmail</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send test email!</p>";
        echo "<p>Check the error logs for more details.</p>";
    }
    
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