# Email Configuration Guide - SendGrid Integration

This guide explains how to configure and use the email functionality in the VOTESYS application with SendGrid SMTP.

## Overview

The VOTESYS application has been configured to use SendGrid as the email service provider for sending notifications, results, and other communications.

## Configuration Files

### 1. Environment Variables (.env)

The main email configuration is stored in the `.env` file:

```env
# Email Configuration (SendGrid SMTP)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key_here
MAIL_FROM_EMAIL=noreply@hcu.edu
MAIL_FROM_NAME=Heritage Christian University Voting System
MAIL_ENCRYPTION=tls
```

### 2. Email Utility Class

The `config/email.php` file contains the `EmailUtility` class that handles all email operations using PHPMailer and SendGrid SMTP.

### 3. Bootstrap Configuration

The `config/bootstrap.php` file ensures that environment variables are loaded consistently across the application.

## SendGrid Configuration Details

- **Server**: smtp.sendgrid.net
- **Ports**: 
  - 587 (for unencrypted/TLS connections)
  - 465 (for SSL connections)
- **Username**: apikey (this is literal, not a placeholder)
- **Password**: Your SendGrid API Key
- **Encryption**: TLS

## Features Implemented

### 1. Election Results Email Notifications

- **Location**: `admin/results.php`
- **Feature**: Enhanced publish functionality with email notifications
- **Usage**: When publishing results, administrators can choose to send email notifications to all registered voters

### 2. Email Utility Methods

The `EmailUtility` class provides the following methods:

- `sendEmail($to, $subject, $message, $isHTML = true)` - Send basic email
- `sendBulkEmail($recipients, $subject, $message)` - Send to multiple recipients
- `sendElectionNotification($election_id, $type)` - Send election-specific notifications

## Testing Email Configuration

### 1. Use the Test Script

Visit `http://localhost:8000/test_email.php` to verify the email configuration:

- Displays current email settings
- Shows environment variable status
- Provides option to send test email (uncomment the line in the script)

### 2. Manual Testing

```php
<?php
require_once 'config/email.php';

$emailUtil = new EmailUtility();
$result = $emailUtil->sendEmail(
    'test@example.com',
    'Test Email',
    '<h1>Test successful!</h1><p>Your email configuration is working.</p>'
);

if ($result) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email.";
}
?>
```

## Security Considerations

1. **API Key Security**: The SendGrid API key is stored in the `.env` file and should never be committed to version control
2. **Environment Variables**: Always use environment variables for sensitive configuration
3. **SSL/TLS**: The configuration uses TLS encryption for secure email transmission

## Troubleshooting

### Common Issues

1. **Authentication Failed**
   - Verify the SendGrid API key is correct
   - Ensure the API key has the necessary permissions

2. **Connection Timeout**
   - Check firewall settings for port 587
   - Verify network connectivity

3. **Environment Variables Not Loading**
   - Ensure the `.env` file exists in the root directory
   - Check that `config/bootstrap.php` is included in your scripts

### Debug Steps

1. Check the test script: `http://localhost:8000/test_email.php`
2. Verify environment variables are loaded
3. Check server logs for detailed error messages
4. Test with a simple email first before bulk operations

## Usage Examples

### Sending Election Results

```php
// In admin/results.php
$emailUtil = new EmailUtility();
$voters = getAllVoterEmails(); // Your function to get voter emails

foreach ($voters as $voter) {
    $emailUtil->sendEmail(
        $voter['email'],
        'Election Results Published',
        generateResultsEmail($voter['id']) // Your function to generate email content
    );
}
```

### Sending Notifications

```php
$emailUtil = new EmailUtility();
$emailUtil->sendEmail(
    'voter@example.com',
    'Voting Reminder',
    '<h2>Reminder: Election Voting</h2><p>Don\'t forget to cast your vote!</p>'
);
```

## Integration Points

The email functionality is integrated into:

1. **Results Publishing** (`admin/results.php`) - Send results to voters
2. **User Registration** - Welcome emails
3. **Election Notifications** - Voting reminders
4. **Administrative Alerts** - System notifications

## Maintenance

- Monitor SendGrid usage and quotas
- Regularly update API keys as needed
- Review email templates for accuracy
- Test email functionality after system updates

---

**Note**: This configuration uses the provided SendGrid credentials. For production use, ensure you have proper SendGrid account setup and domain verification.