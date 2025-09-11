# VOTESYS Deployment Guide

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Pre-deployment Checklist](#pre-deployment-checklist)
3. [Local Development Setup](#local-development-setup)
4. [Production Deployment](#production-deployment)
5. [Docker Deployment](#docker-deployment)
6. [Cloud Deployment](#cloud-deployment)
7. [Database Setup](#database-setup)
8. [Configuration](#configuration)
9. [Security Hardening](#security-hardening)
10. [Monitoring and Maintenance](#monitoring-and-maintenance)
11. [Backup and Recovery](#backup-and-recovery)
12. [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Requirements
- **Operating System**: Linux (Ubuntu 20.04+), Windows Server 2019+, or macOS 10.15+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher (PHP 8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Memory**: 2GB RAM minimum (4GB+ recommended)
- **Storage**: 10GB available disk space
- **SSL Certificate**: Required for production deployment

### Recommended Requirements
- **Operating System**: Ubuntu 22.04 LTS
- **Web Server**: Apache 2.4.52+ with mod_rewrite enabled
- **PHP**: Version 8.1 with required extensions
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Memory**: 8GB RAM
- **Storage**: 50GB SSD storage
- **CDN**: CloudFlare or similar for static assets

### Required PHP Extensions
```bash
# Check installed extensions
php -m

# Required extensions:
- mysqli
- pdo
- pdo_mysql
- json
- mbstring
- openssl
- curl (required for email functionality)
- gd
- zip (for file handling)
- xml (for PHPMailer)
- session
- filter
- hash
```

## Pre-deployment Checklist

### Code Preparation
- [ ] All code committed to version control
- [ ] Tests passing
- [ ] Documentation updated
- [ ] Environment-specific configurations prepared
- [ ] Database migrations ready
- [ ] SSL certificates obtained
- [ ] Backup strategy planned

### Infrastructure Preparation
- [ ] Server provisioned and accessible
- [ ] Domain name configured
- [ ] DNS records set up
- [ ] Firewall rules configured
- [ ] Database server ready
- [ ] Monitoring tools installed

## Local Development Setup

### Option 1: XAMPP (Windows/macOS/Linux)

#### Step 1: Install XAMPP
```bash
# Download from https://www.apachefriends.org/
# Install with PHP 7.4+ and MySQL
```

#### Step 2: Clone Repository
```bash
# Navigate to XAMPP htdocs directory
cd C:\xampp\htdocs  # Windows
# cd /Applications/XAMPP/htdocs  # macOS
# cd /opt/lampp/htdocs  # Linux

# Clone the repository
git clone <repository-url> VOTESYS
cd VOTESYS
```

#### Step 3: Install Dependencies
```bash
# Install Composer if not already installed
# Download from https://getcomposer.org/

# Install PHP dependencies
composer install
```

#### Step 4: Database Setup
```bash
# Start XAMPP services
# Open XAMPP Control Panel and start Apache and MySQL

# Create database
mysql -u root -p
CREATE DATABASE votesys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema
mysql -u root -p votesys < database_schema.sql
```

#### Step 5: Configuration
```php
// Copy and configure database settings
cp config/database.example.php config/database.php

// Edit config/database.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votesys');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default
define('DB_CHARSET', 'utf8mb4');
?>
```

#### Step 6: Test Installation
```bash
# Access the application
# http://localhost/VOTESYS
# http://localhost/VOTESYS/admin
```

### Option 2: Native LAMP/WAMP Stack

#### Ubuntu/Debian Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# Install PHP
sudo apt install php php-mysql php-mbstring php-xml php-curl php-gd php-zip -y

# Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Configure Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Clone and Setup Application
```bash
# Clone to web directory
sudo git clone <repository-url> /var/www/html/votesys
cd /var/www/html/votesys

# Install dependencies
sudo composer install

# Set permissions
sudo chown -R www-data:www-data /var/www/html/votesys
sudo chmod -R 755 /var/www/html/votesys
sudo chmod -R 777 /var/www/html/votesys/uploads
sudo chmod -R 777 /var/www/html/votesys/logs
```

## Production Deployment

### Step 1: Server Preparation

#### Ubuntu 22.04 LTS Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-gd php8.1-zip php8.1-bcmath php8.1-json \
    unzip git curl certbot python3-certbot-apache

# Enable Apache modules
sudo a2enmod rewrite ssl headers

# Start and enable services
sudo systemctl enable apache2 mysql
sudo systemctl start apache2 mysql
```

### Step 2: Database Setup
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE votesys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'votesys_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON votesys.* TO 'votesys_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Application Deployment
```bash
# Create application directory
sudo mkdir -p /var/www/votesys
cd /var/www/votesys

# Clone repository
sudo git clone <repository-url> .

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install dependencies (production mode)
sudo composer install --no-dev --optimize-autoloader

# Set ownership and permissions
sudo chown -R www-data:www-data /var/www/votesys
sudo chmod -R 755 /var/www/votesys
sudo chmod -R 775 /var/www/votesys/uploads
sudo chmod -R 775 /var/www/votesys/logs

# Create logs directory if it doesn't exist
sudo mkdir -p /var/www/votesys/logs
sudo chown www-data:www-data /var/www/votesys/logs
```

### Step 4: Apache Virtual Host Configuration
```bash
# Create virtual host file
sudo nano /etc/apache2/sites-available/votesys.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/votesys
    
    <Directory /var/www/votesys>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/votesys_error.log
    CustomLog ${APACHE_LOG_DIR}/votesys_access.log combined
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/votesys
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    <Directory /var/www/votesys>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/votesys_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/votesys_ssl_access.log combined
</VirtualHost>
```

```bash
# Enable site and restart Apache
sudo a2ensite votesys.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

### Step 5: SSL Certificate Setup
```bash
# Obtain Let's Encrypt certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Test automatic renewal
sudo certbot renew --dry-run

# Set up automatic renewal
sudo crontab -e
# Add this line:
0 12 * * * /usr/bin/certbot renew --quiet
```

### Step 6: Database Migration
```bash
# Import database schema
mysql -u votesys_user -p votesys < /var/www/votesys/database_schema.sql

# Create initial admin user (if needed)
mysql -u votesys_user -p votesys
```

```sql
INSERT INTO admins (username, email, password, role, created_at) 
VALUES ('admin', 'admin@yourdomain.com', '$2y$10$hash_here', 'super_admin', NOW());
```

## Docker Deployment

### Docker Compose Setup

#### Create docker-compose.yml
```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./uploads:/var/www/html/uploads
      - ./logs:/var/www/html/logs
      - ./ssl:/etc/ssl/certs
    environment:
      - DB_HOST=db
      - DB_NAME=votesys
      - DB_USER=votesys_user
      - DB_PASS=secure_password
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: votesys
      MYSQL_USER: votesys_user
      MYSQL_PASSWORD: secure_password
    volumes:
      - db_data:/var/lib/mysql
      - ./database_schema.sql:/docker-entrypoint-initdb.d/schema.sql
    restart: unless-stopped

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    environment:
      PMA_HOST: db
      PMA_USER: votesys_user
      PMA_PASSWORD: secure_password
    ports:
      - "8080:80"
    depends_on:
      - db
    restart: unless-stopped

volumes:
  db_data:
```

#### Create Dockerfile
```dockerfile
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache modules
RUN a2enmod rewrite ssl headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/logs

# Copy Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80 443

CMD ["apache2-foreground"]
```

#### Deploy with Docker
```bash
# Build and start containers
docker-compose up -d

# View logs
docker-compose logs -f

# Stop containers
docker-compose down

# Update application
git pull
docker-compose build --no-cache
docker-compose up -d
```

## Cloud Deployment

### AWS Deployment

#### EC2 Instance Setup
```bash
# Launch EC2 instance (Ubuntu 22.04 LTS)
# Configure security groups:
# - HTTP (80) from anywhere
# - HTTPS (443) from anywhere
# - SSH (22) from your IP
# - MySQL (3306) from VPC only

# Connect to instance
ssh -i your-key.pem ubuntu@your-instance-ip

# Follow production deployment steps above
```

#### RDS Database Setup
```bash
# Create RDS MySQL instance
# Configure security group to allow access from EC2
# Update database configuration with RDS endpoint
```

#### S3 for File Storage (Optional)
```php
// config/aws.php
require_once 'vendor/autoload.php';

use Aws\S3\S3Client;

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key'    => 'your-access-key',
        'secret' => 'your-secret-key',
    ],
]);
```

### DigitalOcean Deployment

#### Droplet Setup
```bash
# Create Ubuntu 22.04 droplet
# Add SSH key
# Configure firewall

# Connect and deploy
ssh root@your-droplet-ip

# Follow production deployment steps
```

#### Managed Database
```bash
# Create managed MySQL database
# Configure connection string
# Update application configuration
```

## Configuration

### Environment Configuration

#### Create .env file
```bash
# Production environment variables
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=localhost
DB_NAME=votesys
DB_USER=votesys_user
DB_PASS=secure_password
DB_CHARSET=utf8mb4

# Security
SESSION_LIFETIME=7200
CSRF_TOKEN_LIFETIME=3600
PASSWORD_HASH_ALGO=PASSWORD_DEFAULT

# Email Configuration (Required for auto-generated passwords)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=VOTESYS - University Voting System

# File Upload
MAX_UPLOAD_SIZE=5M
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif

# Logging
LOG_LEVEL=error
LOG_FILE=/var/www/votesys/logs/app.log
```

#### Update Configuration Files
```php
// config/database.php
<?php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$name = $_ENV['DB_NAME'] ?? 'votesys';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

define('DB_HOST', $host);
define('DB_NAME', $name);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_CHARSET', $charset);
?>
```

### Election Configuration

VOTESYS supports both single-election and multi-election deployments through the election routing system. The `CURRENT_ELECTION_ID` constant serves as the default election for backward compatibility and fallback scenarios.

#### Setting the Default Election ID

```php
// config/constants.php
<?php
// Default election ID - Update this for your deployment
define('CURRENT_ELECTION_ID', 26);

// Other application constants
define('APP_NAME', 'VOTESYS');
define('APP_VERSION', '2.0.0');
define('SESSION_TIMEOUT', 7200);
?>
```

#### Environment-Specific Configuration

For different deployment environments, you can configure different default elections:

```php
// config/constants.php - Environment-aware configuration
<?php
switch (getenv('APP_ENV')) {
    case 'production':
        define('CURRENT_ELECTION_ID', 26);  // Production election
        break;
    case 'staging':
        define('CURRENT_ELECTION_ID', 12);  // Staging election
        break;
    case 'development':
        define('CURRENT_ELECTION_ID', 1);   // Development election
        break;
    default:
        define('CURRENT_ELECTION_ID', 1);   // Safe fallback
}
?>
```

#### Finding Your Election ID

To determine the correct election ID for your deployment:

1. **Admin Panel Method**:
   - Log into the admin panel
   - Navigate to "Manage Elections"
   - Note the ID number in the elections list
   - Use the ID of your active/primary election

2. **Database Query Method**:
   ```sql
   -- Find all elections with their IDs
   SELECT id, title, is_active, start_date, end_date 
   FROM elections 
   ORDER BY created_at DESC;
   
   -- Find the current active election
   SELECT id, title 
   FROM elections 
   WHERE is_active = 1 
   AND start_date <= NOW() 
   AND end_date >= NOW();
   ```

3. **PHP Script Method**:
   ```php
   // scripts/find_election_id.php
   <?php
   require_once 'config/database.php';
   
   try {
       $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
       $stmt = $pdo->query("SELECT id, title, is_active FROM elections ORDER BY id DESC");
       
       echo "Available Elections:\n";
       while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
           $status = $row['is_active'] ? '[ACTIVE]' : '[INACTIVE]';
           echo "ID: {$row['id']} - {$row['title']} {$status}\n";
       }
   } catch (PDOException $e) {
       echo "Error: " . $e->getMessage();
   }
   ?>
   ```

#### Election Routing Behavior

The election routing system works as follows:

1. **Default Access**: `voter_page.php` uses `CURRENT_ELECTION_ID`
2. **Specific Election**: `voter_page.php?election_id=12` uses election ID 12 if valid
3. **Invalid Election**: Falls back to `CURRENT_ELECTION_ID` if provided ID is invalid
4. **Form Submission**: Hidden `election_id` field maintains context during voting

#### URL Examples

```bash
# Default election access (uses CURRENT_ELECTION_ID)
https://yourdomain.com/voter_page.php

# Specific election access
https://yourdomain.com/voter_page.php?election_id=12
https://yourdomain.com/voter_page.php?election_id=26

# Admin-generated links for specific elections
https://yourdomain.com/voter_page.php?election_id=15
```

#### Deployment Checklist

Before deploying, ensure:

- [ ] `CURRENT_ELECTION_ID` is set to the correct election ID
- [ ] The specified election exists in the database
- [ ] The election is marked as active (`is_active = 1`)
- [ ] Election dates are properly configured
- [ ] Positions and candidates are set up for the election
- [ ] Test both default and specific election URLs

#### Multi-Election Deployment

For organizations running multiple concurrent elections:

1. **Set Primary Election**: Use the most important election as `CURRENT_ELECTION_ID`
2. **Generate Specific Links**: Create election-specific URLs for each election
3. **Admin Training**: Train administrators to use correct election links
4. **Documentation**: Document which election ID corresponds to which election

#### Troubleshooting Election Configuration

**Problem**: Voters see wrong election
- **Solution**: Verify `CURRENT_ELECTION_ID` matches intended election
- **Check**: Ensure election exists and is active in database

**Problem**: Election-specific URLs not working
- **Solution**: Verify election ID exists in database
- **Check**: Test URL parameter validation in `voter_page.php`

**Problem**: Votes going to wrong election
- **Solution**: Check `process_vote.php` election ID handling
- **Check**: Verify hidden form field contains correct election ID

### PHP Configuration

#### Update php.ini
```ini
; Production settings
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; File uploads
file_uploads = On
upload_max_filesize = 5M
post_max_size = 10M
max_file_uploads = 10

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

; Memory and execution
memory_limit = 256M
max_execution_time = 30
max_input_time = 60
```

### Email Configuration

VOTESYS requires email configuration for the auto-generated password functionality. This is essential for voter registration and password reset features.

#### Gmail Configuration (Recommended)

1. **Enable 2-Factor Authentication**:
   - Go to your Google Account settings
   - Navigate to Security → 2-Step Verification
   - Enable 2FA if not already enabled

2. **Generate App Password**:
   - In Security settings, go to "App passwords"
   - Select "Mail" as the app
   - Generate a 16-character app password
   - Use this password in your configuration

3. **Environment Variables**:
```bash
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_16_character_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=VOTESYS - University Voting System
```

#### Alternative Email Providers

**Outlook/Hotmail**:
```bash
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your_email@outlook.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

**Custom SMTP Server**:
```bash
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587  # or 465 for SSL
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls  # or ssl
```

#### Testing Email Configuration

After configuration, test the email functionality:

1. **Admin Panel Test**:
   - Log into the admin panel
   - Add a test voter with your email address
   - Check if the password email is received

2. **Voter Registration Test**:
   - Use the voter registration form
   - Register with a test email
   - Verify email delivery and formatting

3. **Troubleshooting**:
   - Check server logs: `/var/www/votesys/logs/`
   - Verify SMTP credentials
   - Ensure firewall allows outbound SMTP traffic
   - Test with different email providers

#### Security Best Practices

- **Dedicated Email Account**: Use a dedicated email account for system notifications
- **App Passwords**: Always use app passwords instead of main account passwords
- **Environment Variables**: Never hardcode email credentials in source code
- **SSL/TLS**: Always use encrypted connections (TLS/SSL)
- **Rate Limiting**: Implement email rate limiting to prevent abuse
- **Monitoring**: Monitor email delivery rates and failures

## Security Hardening

### Server Security

#### Firewall Configuration
```bash
# Configure UFW (Ubuntu)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Apache Full'
sudo ufw enable

# Check status
sudo ufw status verbose
```

#### Fail2Ban Setup
```bash
# Install Fail2Ban
sudo apt install fail2ban -y

# Configure for Apache
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/*error.log
maxretry = 3

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/*access.log
maxretry = 2
```

```bash
# Start and enable Fail2Ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### Application Security

#### File Permissions
```bash
# Set secure permissions
sudo find /var/www/votesys -type f -exec chmod 644 {} \;
sudo find /var/www/votesys -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/votesys/uploads
sudo chmod -R 775 /var/www/votesys/logs
sudo chmod 600 /var/www/votesys/config/*.php
```

#### .htaccess Security
```apache
# /var/www/votesys/.htaccess
RewriteEngine On

# Prevent access to sensitive files
<FilesMatch "\.(env|log|sql|md)$">
    Require all denied
</FilesMatch>

# Prevent access to config directory
<Directory "config">
    Require all denied
</Directory>

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Hide server information
ServerTokens Prod
ServerSignature Off

# Prevent directory browsing
Options -Indexes

# URL rewriting for clean URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^admin/(.*)$ admin/index.php [QSA,L]
RewriteRule ^voter/(.*)$ voter/index.php [QSA,L]
```

## Monitoring and Maintenance

### Log Monitoring

#### Setup Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/votesys
```

```
/var/www/votesys/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

#### System Monitoring Script
```bash
#!/bin/bash
# /usr/local/bin/votesys-monitor.sh

LOG_FILE="/var/log/votesys-monitor.log"
APP_DIR="/var/www/votesys"
DB_NAME="votesys"
DB_USER="votesys_user"

echo "$(date): Starting VOTESYS monitoring" >> $LOG_FILE

# Check Apache status
if ! systemctl is-active --quiet apache2; then
    echo "$(date): Apache is down, attempting restart" >> $LOG_FILE
    systemctl restart apache2
fi

# Check MySQL status
if ! systemctl is-active --quiet mysql; then
    echo "$(date): MySQL is down, attempting restart" >> $LOG_FILE
    systemctl restart mysql
fi

# Check disk space
DISK_USAGE=$(df /var/www | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): Disk usage is ${DISK_USAGE}%" >> $LOG_FILE
fi

# Check database connectivity
if ! mysql -u $DB_USER -p$DB_PASS -e "USE $DB_NAME;" 2>/dev/null; then
    echo "$(date): Database connection failed" >> $LOG_FILE
fi

echo "$(date): Monitoring completed" >> $LOG_FILE
```

```bash
# Make executable and add to cron
sudo chmod +x /usr/local/bin/votesys-monitor.sh
sudo crontab -e
# Add: */5 * * * * /usr/local/bin/votesys-monitor.sh
```

### Performance Monitoring

#### Install and Configure htop
```bash
sudo apt install htop -y

# Monitor system resources
htop
```

#### MySQL Performance Tuning
```bash
# Install MySQL Tuner
wget http://mysqltuner.pl/ -O mysqltuner.pl
perl mysqltuner.pl

# Apply recommended settings to /etc/mysql/mysql.conf.d/mysqld.cnf
```

## Backup and Recovery

### Database Backup

#### Automated Backup Script
```bash
#!/bin/bash
# /usr/local/bin/votesys-backup.sh

BACKUP_DIR="/var/backups/votesys"
DB_NAME="votesys"
DB_USER="votesys_user"
DB_PASS="secure_password"
DATE=$(date +"%Y%m%d_%H%M%S")

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql
gzip $BACKUP_DIR/db_backup_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz -C /var/www votesys/uploads

# Remove backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable and schedule
sudo chmod +x /usr/local/bin/votesys-backup.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/votesys-backup.sh
```

### Recovery Procedures

#### Database Recovery
```bash
# Stop application
sudo systemctl stop apache2

# Restore database
gunzip -c /var/backups/votesys/db_backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u votesys_user -p votesys

# Restore files
cd /var/www/votesys
sudo tar -xzf /var/backups/votesys/files_backup_YYYYMMDD_HHMMSS.tar.gz

# Fix permissions
sudo chown -R www-data:www-data uploads/

# Start application
sudo systemctl start apache2
```

## Troubleshooting

### Common Issues

#### 500 Internal Server Error
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check PHP error logs
sudo tail -f /var/log/php_errors.log

# Check application logs
sudo tail -f /var/www/votesys/logs/error.log

# Common causes:
# 1. Incorrect file permissions
# 2. PHP syntax errors
# 3. Missing PHP extensions
# 4. Database connection issues
```

#### Database Connection Issues
```bash
# Test database connection
mysql -u votesys_user -p -h localhost votesys

# Check MySQL status
sudo systemctl status mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log

# Verify user permissions
mysql -u root -p
SHOW GRANTS FOR 'votesys_user'@'localhost';
```

#### File Upload Issues
```bash
# Check upload directory permissions
ls -la /var/www/votesys/uploads/

# Check PHP upload settings
php -i | grep upload

# Check Apache error logs for upload errors
sudo grep -i upload /var/log/apache2/error.log
```

#### SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Test SSL configuration
ssl-cert-check -c /etc/letsencrypt/live/yourdomain.com/fullchain.pem

# Renew certificate
sudo certbot renew

# Check Apache SSL configuration
sudo apache2ctl -S
```

### Performance Issues

#### Slow Database Queries
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';

-- Analyze slow queries
-- tail -f /var/log/mysql/slow.log

-- Check for missing indexes
SHOW PROCESSLIST;
EXPLAIN SELECT * FROM votes WHERE election_id = 1;
```

#### High Memory Usage
```bash
# Check memory usage
free -h
top -o %MEM

# Check Apache processes
ps aux | grep apache2

# Optimize Apache configuration
# Edit /etc/apache2/mods-available/mpm_prefork.conf
```

### Emergency Procedures

#### Site Maintenance Mode
```bash
# Create maintenance page
sudo nano /var/www/votesys/maintenance.html
```

```html
<!DOCTYPE html>
<html>
<head>
    <title>VOTESYS - Under Maintenance</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Under Maintenance</h1>
        <p>VOTESYS is currently undergoing scheduled maintenance.</p>
        <p>We'll be back online shortly. Thank you for your patience.</p>
    </div>
</body>
</html>
```

```bash
# Enable maintenance mode
sudo mv /var/www/votesys/index.php /var/www/votesys/index.php.bak
sudo mv /var/www/votesys/maintenance.html /var/www/votesys/index.html

# Disable maintenance mode
sudo mv /var/www/votesys/index.html /var/www/votesys/maintenance.html
sudo mv /var/www/votesys/index.php.bak /var/www/votesys/index.php
```

#### Emergency Rollback
```bash
# Rollback to previous version
cd /var/www/votesys
sudo git log --oneline -10
sudo git checkout <previous-commit-hash>

# Restore database backup if needed
gunzip -c /var/backups/votesys/db_backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u votesys_user -p votesys

# Clear cache and restart services
sudo systemctl restart apache2
```

---

**Note**: This deployment guide should be customized based on your specific infrastructure requirements and security policies. Always test deployment procedures in a staging environment before applying to production.

**Security Reminder**: Never commit sensitive information like passwords or API keys to version control. Use environment variables or secure configuration management tools.