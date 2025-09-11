<?php
require_once 'config/bootstrap.php';
require_once 'config/email.php';

echo "<h2>SendGrid Email Test</h2>";

// Display current configuration
echo "<h3>Current Email Configuration:</h3>";
echo "<p><strong>SMTP Host:</strong> " . $_ENV['MAIL_HOST'] . "</p>";
echo "<p><strong>SMTP Port:</strong> " . $_ENV['MAIL_PORT'] . "</p>";
echo "<p><strong>Username:</strong> " . $_ENV['MAIL_USERNAME'] . "</p>";
echo "<p><strong>From Email:</strong> " . $_ENV['MAIL_FROM_EMAIL'] . "</p>";
echo "<p><strong>From Name:</strong> " . $_ENV['MAIL_FROM_NAME'] . "</p>";

// Test email sending
echo "<h3>Testing Email Send...</h3>";

try {
    $emailUtility = new EmailUtility();
    
    // Test configuration first
    echo "<p>Testing SMTP connection...</p>";
    if ($emailUtility->testConfiguration()) {
        echo "<p style='color: green;'>✓ SMTP connection successful!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ SMTP connection test failed, but attempting to send email...</p>";
    }
    
    // Test sending a password email
    $result = $emailUtility->sendPasswordEmail(
        'test@example.com',
        'Test User',
        'TEST123',
        'TestPassword123!',
        'SendGrid Configuration Test'
    );
    
    if ($result) {
        echo "<p style='color: green;'>✓ Email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Email sending failed!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>Note: Check your email inbox (including spam folder) for the test message.</em></p>";
?>