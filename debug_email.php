<?php
// Debug email functionality
require_once 'config/bootstrap.php';
require_once 'config/email.php';

echo "<h2>Email Configuration Debug</h2>";

// Display current email configuration
echo "<h3>Current Email Settings:</h3>";
echo "<ul>";
echo "<li>Host: " . getenv('MAIL_HOST') . "</li>";
echo "<li>Port: " . getenv('MAIL_PORT') . "</li>";
echo "<li>Username: " . getenv('MAIL_USERNAME') . "</li>";
echo "<li>Password: " . (getenv('MAIL_PASSWORD') ? '[SET]' : '[NOT SET]') . "</li>";
echo "<li>From Email: " . getenv('MAIL_FROM_EMAIL') . "</li>";
echo "<li>From Name: " . getenv('MAIL_FROM_NAME') . "</li>";
echo "<li>Encryption: " . getenv('MAIL_ENCRYPTION') . "</li>";
echo "</ul>";

// Test email utility initialization
echo "<h3>Testing Email Utility Initialization:</h3>";
try {
    $emailUtility = new EmailUtility();
    echo "<p style='color: green;'>✓ EmailUtility initialized successfully</p>";
    
    // Test password generation
    $testPassword = EmailUtility::generateSecurePassword(12);
    echo "<p style='color: green;'>✓ Password generation works: " . $testPassword . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ EmailUtility initialization failed: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>Stack trace: " . $e->getTraceAsString() . "</p>";
}

// Test sending a simple email
echo "<h3>Email Sending Test:</h3>";

// Test email sending with a safe test email
try {
    $testEmail = 'test@example.com'; // This won't actually send since it's a fake domain
    echo "<p>Testing email sending to: " . $testEmail . "</p>";
    
    $result = $emailUtility->sendPasswordEmail(
        $testEmail,
        'Test User',
        'TEST123',
        $testPassword,
        'Test Election'
    );
    
    if ($result) {
        echo "<p style='color: green;'>✓ Test email sent successfully to " . $testEmail . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send test email</p>";
        // Get PHPMailer error info
        $mailer = $emailUtility->getMailer();
        if ($mailer && $mailer->ErrorInfo) {
            echo "<p style='color: red;'>PHPMailer Error: " . htmlspecialchars($mailer->ErrorInfo) . "</p>";
        }
        echo "<p style='color: orange;'>Check the error logs below for detailed information.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Email sending error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: red;'>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

// Check PHP mail configuration
echo "<h3>PHP Mail Configuration:</h3>";
echo "<ul>";
echo "<li>mail() function available: " . (function_exists('mail') ? 'Yes' : 'No') . "</li>";
echo "<li>OpenSSL extension: " . (extension_loaded('openssl') ? 'Loaded' : 'Not loaded') . "</li>";
echo "<li>cURL extension: " . (extension_loaded('curl') ? 'Loaded' : 'Not loaded') . "</li>";
echo "</ul>";

// Check for recent error logs
echo "<h3>Recent Error Logs:</h3>";
if (function_exists('error_get_last')) {
    $lastError = error_get_last();
    if ($lastError) {
        echo "<p>Last PHP Error: " . $lastError['message'] . " in " . $lastError['file'] . " on line " . $lastError['line'] . "</p>";
    } else {
        echo "<p>No recent PHP errors</p>";
    }
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Check if all email settings are properly configured above</li>";
echo "<li>Verify that OpenSSL extension is loaded for SMTP connections</li>";
echo "<li>Check server logs for detailed error messages</li>";
echo "<li>Uncomment the email test section above to test actual sending</li>";
echo "</ol>";
?>