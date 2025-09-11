# VOTESYS Administrator User Guide

## Table of Contents
1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Managing Elections](#managing-elections)
4. [Managing Positions](#managing-positions)
5. [Managing Candidates](#managing-candidates)
6. [Managing Voters](#managing-voters)
7. [Monitoring Elections](#monitoring-elections)
8. [Viewing Results](#viewing-results)
9. [System Administration](#system-administration)
10. [Security Best Practices](#security-best-practices)
11. [Troubleshooting](#troubleshooting)

## Getting Started

### System Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Stable internet connection
- Administrator account credentials

### Logging In
1. Navigate to the VOTESYS admin portal: `http://your-domain.com/VOTESYS/admin/`
2. Enter your username and password
3. Click "Login" to access the admin dashboard

### First Time Setup
After logging in for the first time:
1. Change your default password
2. Update your profile information
3. Review system settings
4. Familiarize yourself with the dashboard

## Dashboard Overview

The admin dashboard provides a comprehensive overview of your voting system:

### Key Metrics
- **Total Elections**: Number of elections created
- **Active Elections**: Currently running elections
- **Total Candidates**: All registered candidates
- **Total Voters**: Registered voter count
- **Votes Cast**: Total votes across all elections
- **Today's Activity**: Recent voting activity

### Quick Actions
- Create new election
- Add candidates
- Register voters
- View live results
- Access system monitoring

### Recent Activity
Monitor recent system activities including:
- New voter registrations
- Vote submissions
- Administrative actions
- System alerts

## Managing Elections

### Creating a New Election
1. Click "Elections" in the main navigation
2. Click "Create New Election" button
3. Fill in the election details:
   - **Title**: Election name (e.g., "Student Council Elections 2024")
   - **Description**: Detailed election information
   - **Start Date**: When voting begins
   - **End Date**: When voting ends
   - **Settings**: Configure election-specific options
4. Click "Save" to create the election

### Election Settings
- **Allow Multiple Votes**: Whether voters can change their votes
- **Require Verification**: Enable SMS/email verification
- **Public Results**: Show results during voting
- **Anonymous Voting**: Ensure voter anonymity

### Managing Election Status
- **Draft**: Election is being prepared
- **Active**: Voting is open
- **Paused**: Temporarily suspended
- **Completed**: Voting has ended
- **Cancelled**: Election was cancelled

### Editing Elections
1. Go to Elections page
2. Click "Edit" next to the election
3. Modify the necessary fields
4. Click "Update" to save changes

**Note**: Some fields cannot be edited once voting has started.

### Deleting Elections
⚠️ **Warning**: Deleting an election removes all associated data permanently.

1. Go to Elections page
2. Click "Delete" next to the election
3. Confirm the deletion
4. All votes, candidates, and positions will be removed

## Managing Positions

Positions represent the roles voters can elect candidates for.

### Creating Positions
1. Navigate to an election
2. Click "Positions" tab
3. Click "Add Position"
4. Enter position details:
   - **Title**: Position name (e.g., "President", "Secretary")
   - **Description**: Role responsibilities
   - **Max Candidates**: Maximum number of candidates
   - **Max Votes**: Votes each voter can cast for this position
   - **Order**: Display order on ballot
5. Click "Save"

### Position Configuration
- **Single Vote**: Voters select one candidate
- **Multiple Votes**: Voters can select multiple candidates
- **Ranked Choice**: Voters rank candidates by preference

### Editing Positions
1. Go to the election's Positions tab
2. Click "Edit" next to the position
3. Modify the details
4. Click "Update"

### Deleting Positions
1. Click "Delete" next to the position
2. Confirm deletion
3. All associated candidates will be removed

## Managing Candidates

### Adding Candidates
1. Navigate to an election
2. Click "Candidates" tab
3. Click "Add Candidate"
4. Fill in candidate information:
   - **Name**: Full name
   - **Position**: Select from available positions
   - **Student ID**: Unique identifier
   - **Course**: Academic program
   - **Year Level**: Academic year
   - **Biography**: Candidate background
   - **Platform**: Campaign promises/goals
   - **Photo**: Upload candidate photo
5. Click "Save"

### Photo Requirements
- **Format**: JPG, PNG, or GIF
- **Size**: Maximum 2MB
- **Dimensions**: Recommended 300x300 pixels
- **Content**: Professional headshot

### Bulk Import Candidates
1. Click "Import Candidates"
2. Download the CSV template
3. Fill in candidate data
4. Upload the completed CSV file
5. Review and confirm the import

### Editing Candidates
1. Go to the Candidates page
2. Click "Edit" next to the candidate
3. Modify the information
4. Click "Update"

### Managing Candidate Status
- **Active**: Candidate appears on ballot
- **Inactive**: Candidate hidden from ballot
- **Withdrawn**: Candidate withdrew from election

## Managing Voters

### Adding Individual Voters
1. Click "Voters" in the main navigation
2. Click "Add Voter"
3. Enter voter information:
   - **Student ID**: Unique identifier
   - **Name**: Full name
   - **Email**: Valid email address
   - **Course**: Academic program
   - **Year Level**: Academic year
4. Click "Save"
5. **Auto-Generated Password**: A secure password is automatically generated and sent to the voter's email

### Bulk Import Voters
1. Click "Import Voters"
2. Download the CSV template
3. Fill in voter data:
   ```csv
   student_id,name,email,course,year_level
   STU001,John Doe,john@university.edu,Computer Science,3rd Year
   STU002,Jane Smith,jane@university.edu,Engineering,2nd Year
   ```
4. Upload the CSV file
5. Review and confirm the import
6. **Auto-Generated Passwords**: Secure passwords are automatically generated and emailed to all imported voters

### Voter Management Features
- **Search**: Find voters by name, ID, or course
- **Filter**: Filter by course, year level, or status
- **Export**: Download voter list as CSV
- **Reset Password**: Generate new secure passwords and email them to voters
- **Activate/Deactivate**: Enable or disable voter accounts
- **Email Notifications**: Automatic email delivery of login credentials

### Election-Specific Voter Access

#### Generating Election Login Links
To direct voters to a specific election, you can create election-specific login links:

1. **Standard Login Link**: `http://your-domain.com/VOTESYS/voter_page.php`
   - Uses the default election set in system configuration
   - Suitable for single-election scenarios

2. **Election-Specific Link**: `http://your-domain.com/VOTESYS/voter_page.php?election_id=12`
   - Replace `12` with the actual election ID
   - Directs voters to a specific election
   - Useful for multiple concurrent elections

#### Finding Election IDs
To find the election ID for creating specific links:
1. Go to "Elections" in the admin panel
2. The election ID is displayed in the elections list
3. Use this ID in the URL parameter: `?election_id=YOUR_ID`

#### Best Practices for Election Links
- **Email Distribution**: Include election-specific links in voter notification emails
- **Website Integration**: Embed links on institutional websites or portals
- **QR Codes**: Generate QR codes for easy mobile access
- **Link Validation**: Test links before distributing to voters
- **Multiple Elections**: Use specific links when running concurrent elections

#### URL Parameter Handling
- The system automatically detects the `election_id` parameter
- Falls back to the default election if no parameter is provided
- Invalid election IDs redirect to the default election
- Voters see the correct election information and ballot

### Voter Verification
- **Email Verification**: Send verification emails
- **Manual Verification**: Manually verify voters
- **Bulk Verification**: Verify multiple voters at once

## Monitoring Elections

### Real-time Monitoring
The monitoring dashboard provides live election data:

1. Navigate to "Monitoring" page
2. Select the election to monitor
3. View real-time statistics:
   - **Vote Count**: Total votes cast
   - **Participation Rate**: Percentage of voters who voted
   - **Votes per Hour**: Voting activity timeline
   - **Position Breakdown**: Votes by position
   - **Geographic Distribution**: Votes by location (if enabled)

### Monitoring Features
- **Live Updates**: Automatic refresh every 30 seconds
- **Export Data**: Download monitoring reports
- **Alert System**: Notifications for unusual activity
- **Vote Verification**: Track verification status

### Security Monitoring
- **Multiple Vote Attempts**: Detect duplicate voting attempts
- **Suspicious Activity**: Unusual voting patterns
- **IP Tracking**: Monitor voting locations
- **Session Management**: Track active voter sessions

## Viewing Results

### Election Results
1. Navigate to "Results" page
2. Select the election
3. View comprehensive results:
   - **Overall Results**: Complete election summary
   - **Position Results**: Results by position
   - **Candidate Performance**: Individual candidate statistics
   - **Voting Statistics**: Participation metrics

### Result Features
- **Real-time Updates**: Live result updates during voting
- **Visual Charts**: Graphs and charts for easy interpretation
- **Export Options**: PDF, Excel, and CSV formats
- **Print Reports**: Formatted reports for printing

### Result Analysis
- **Vote Distribution**: How votes were distributed
- **Participation Analysis**: Voter turnout by demographics
- **Time Analysis**: Voting patterns over time
- **Comparison Tools**: Compare multiple elections

## System Administration

### User Management
#### Managing Admin Accounts
1. Go to "System" → "Administrators"
2. View existing admin accounts
3. Add new administrators:
   - **Username**: Unique username
   - **Email**: Valid email address
   - **Full Name**: Administrator's name
   - **Role**: Admin level (Super Admin, Admin, Moderator)
   - **Password**: Secure password

#### Admin Roles
- **Super Admin**: Full system access
- **Admin**: Election management access
- **Moderator**: Limited monitoring access

### System Settings
1. Navigate to "System" → "Settings"
2. Configure system-wide options:
   - **Site Title**: System name
   - **Email Settings**: SMTP configuration
   - **SMS Settings**: SMS gateway configuration
   - **Security Settings**: Password policies, session timeouts
   - **Backup Settings**: Automated backup configuration

### Audit Logs
1. Go to "System" → "Audit Logs"
2. Review system activities:
   - **User Actions**: Login, logout, data changes
   - **System Events**: Backups, maintenance, errors
   - **Security Events**: Failed logins, suspicious activity
   - **Data Changes**: All database modifications

### Backup and Restore
#### Creating Backups
1. Navigate to "System" → "Backup"
2. Click "Create Backup"
3. Select backup type:
   - **Full Backup**: Complete system backup
   - **Data Only**: Database backup only
   - **Files Only**: Uploaded files backup
4. Download backup file

#### Restoring from Backup
⚠️ **Warning**: Restoring will overwrite current data.

1. Go to "System" → "Restore"
2. Upload backup file
3. Select restore options
4. Confirm restoration
5. System will restart after restoration

## Security Best Practices

### Password Security
- Use strong, unique passwords
- Change passwords regularly
- Enable two-factor authentication
- Never share admin credentials

### System Security
- Keep system updated
- Monitor audit logs regularly
- Use HTTPS for all connections
- Implement IP restrictions if needed

### Data Protection
- Regular backups
- Secure backup storage
- Data encryption
- Access control policies

### Election Security
- Verify voter eligibility
- Monitor for suspicious activity
- Secure candidate data
- Protect vote anonymity

## Troubleshooting

### Common Issues

#### Login Problems
**Issue**: Cannot log in to admin panel
**Solutions**:
1. Verify username and password
2. Check if account is active
3. Clear browser cache and cookies
4. Try different browser
5. Contact system administrator

#### Voter Registration Issues
**Issue**: Cannot add voters
**Solutions**:
1. Check for duplicate student IDs
2. Verify email format
3. Ensure all required fields are filled
4. Check file format for bulk imports

#### Election Creation Problems
**Issue**: Cannot create election
**Solutions**:
1. Verify all required fields
2. Check date format and validity
3. Ensure end date is after start date
4. Check user permissions

#### Performance Issues
**Issue**: System running slowly
**Solutions**:
1. Check server resources
2. Clear browser cache
3. Optimize database
4. Review system logs
5. Contact technical support

### Error Messages

#### "Access Denied"
- Check user permissions
- Verify session is active
- Contact administrator for role assignment

#### "Database Connection Error"
- Check database server status
- Verify connection settings
- Contact system administrator

#### "File Upload Failed"
- Check file size and format
- Verify upload permissions
- Try different file

### Getting Help

#### Support Channels
- **Documentation**: Check this user guide
- **System Logs**: Review error logs
- **Technical Support**: Contact IT department
- **User Community**: Online forums and discussions

#### Reporting Issues
When reporting issues, include:
1. Detailed description of the problem
2. Steps to reproduce the issue
3. Error messages (if any)
4. Browser and operating system information
5. Screenshots (if helpful)

---

## Appendix

### Keyboard Shortcuts
- `Ctrl + N`: Create new election
- `Ctrl + S`: Save current form
- `Ctrl + F`: Search/Filter
- `F5`: Refresh page
- `Esc`: Close modal dialogs

### Browser Compatibility
- **Chrome**: Version 80+
- **Firefox**: Version 75+
- **Safari**: Version 13+
- **Edge**: Version 80+

### System Limits
- **Maximum Elections**: 100 active elections
- **Maximum Candidates**: 1000 per election
- **Maximum Voters**: 50,000 per system
- **File Upload Size**: 10MB per file
- **Session Timeout**: 2 hours of inactivity

---

**Note**: This user guide is updated regularly. Always refer to the latest version for accurate information. For technical support, contact your system administrator.