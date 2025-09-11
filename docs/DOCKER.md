# VOTESYS Docker Setup Guide

This guide explains how to run VOTESYS using Docker containers for easy deployment and development.

## Overview

The Docker setup includes:
- **Web Server**: Apache with PHP 8.1
- **Database**: MySQL 8.0
- **Database Management**: phpMyAdmin
- **Caching**: Redis (optional)
- **Networking**: Isolated Docker network

## Prerequisites

### Install Docker

**Windows:**
1. Download Docker Desktop from [https://www.docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop)
2. Run the installer and follow the setup wizard
3. Restart your computer when prompted
4. Start Docker Desktop from the Start menu
5. Verify installation: Open PowerShell and run `docker --version`

**macOS:**
1. Download Docker Desktop for Mac
2. Drag Docker to Applications folder
3. Launch Docker from Applications
4. Verify installation: Open Terminal and run `docker --version`

**Linux (Ubuntu/Debian):**
```bash
# Update package index
sudo apt-get update

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER

# Start Docker service
sudo systemctl start docker
sudo systemctl enable docker

# Verify installation
docker --version
```

### System Requirements
- Docker Desktop (Windows/Mac) or Docker Engine (Linux)
- Docker Compose v2.0+ (included with Docker Desktop)
- At least 2GB RAM available
- Ports 8080, 8081, 3307, 6380 available

The following ports will be used:
- `8080`: VOTESYS web application
- `8081`: phpMyAdmin
- `3307`: MySQL database (changed from 3306 to avoid XAMPP conflicts)
- `6380`: Redis cache (changed from 6379 to avoid conflicts)

**Note**: The default MySQL (3306) and Redis (6379) ports have been changed to avoid conflicts with existing XAMPP installations.

## Quick Start

### 1. Verify Docker Installation

**Windows (PowerShell):**
```powershell
# Navigate to VOTESYS directory
cd C:\path\to\VOTESYS

# Run verification script
.\docker\check-docker.ps1
```

**Linux/macOS (Bash):**
```bash
# Navigate to VOTESYS directory
cd /path/to/VOTESYS

# Make script executable and run
chmod +x docker/check-docker.sh
./docker/check-docker.sh
```

### 2. Clone and Navigate
```bash
cd /path/to/VOTESYS
```

### 3. Build and Start

**Using Docker Compose v2 (recommended):**
```bash
# Build and start all services
docker compose up -d --build

# View logs
docker compose logs -f
```

**Using Docker Compose v1 (legacy):**
```bash
# Build and start all services
docker-compose up -d --build

# View logs
docker-compose logs -f
```

### 3. Access Applications
- **VOTESYS Web**: http://localhost:8080
- **Admin Panel**: http://localhost:8080/admin
- **Voter Portal**: http://localhost:8080/voter
- **phpMyAdmin**: http://localhost:8081

### 4. Default Credentials
- **Admin Login**: `admin` / `admin123`
- **Database**: `votesys_user` / `votesys_password`
- **Root DB**: `root` / `root_password`

## Detailed Setup

### Environment Configuration

Create a `.env` file for custom configuration:

```env
# Database Configuration
DB_HOST=db
DB_NAME=votesys
DB_USER=votesys_user
DB_PASS=your_secure_password
DB_CHARSET=utf8mb4

# Application Settings
APP_ENV=production
APP_DEBUG=false

# MySQL Root Password
MYSQL_ROOT_PASSWORD=your_root_password

# Email Configuration (Required for auto-generated passwords)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=VOTESYS - University Voting System

# Election Configuration
CURRENT_ELECTION_ID=1

# Security Configuration
ENCRYPTION_KEY=HCU_VOTING_2024_SECURE_KEY_CHANGE_IN_PRODUCTION
DEVELOPMENT_MODE=false
VALIDATE_SESSION_IP=false
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900
CSRF_TOKEN_EXPIRE=3600

# Session Configuration
VOTER_SESSION_TIMEOUT=3600
ADMIN_SESSION_TIMEOUT=7200
SESSION_TIMEOUT=7200
RATE_LIMIT_WINDOW=900

# File Upload Configuration
MAX_FILE_SIZE=5242880
UPLOAD_PATH=uploads/
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif

# Timezone Configuration
TIMEZONE=UTC

# Voting Configuration
VOTE_HASH_ALGORITHM=sha256

# Port Configuration
WEB_PORT=8080
WEB_SSL_PORT=8443
DB_PORT=3307
PHPMYADMIN_PORT=8081
REDIS_PORT=6380
```

### Election Configuration

VOTESYS supports multiple elections through the `CURRENT_ELECTION_ID` environment variable. This setting determines which election is used as the default when voters access the system without specifying an election ID.

#### Setting the Default Election

1. **Find Your Election ID**:
   - Access phpMyAdmin at http://localhost:8081
   - Navigate to the `elections` table
   - Note the `id` of your target election

2. **Configure Environment Variable**:
```env
# Set to your primary election ID
CURRENT_ELECTION_ID=12
```

3. **Restart Docker Services**:
```bash
docker-compose down
docker-compose up -d
```

#### Election Routing Behavior

The Docker deployment supports flexible election access:

- **Default Access**: `http://localhost:8080/voter_page.php` uses `CURRENT_ELECTION_ID`
- **Specific Election**: `http://localhost:8080/voter_page.php?election_id=12` uses election ID 12
- **Fallback Logic**: Invalid election IDs fall back to `CURRENT_ELECTION_ID`

#### Multi-Election Docker Setup

For organizations running multiple elections:

1. **Set Primary Election**: Use the most important election as `CURRENT_ELECTION_ID`
2. **Generate Specific URLs**: Create election-specific links in the admin panel
3. **Environment-Specific Configuration**:

```env
# Development environment
CURRENT_ELECTION_ID=1
DEVELOPMENT_MODE=true

# Production environment
CURRENT_ELECTION_ID=26
DEVELOPMENT_MODE=false
```

#### Troubleshooting Election Configuration

**Problem**: Voters see wrong election
- **Solution**: Verify `CURRENT_ELECTION_ID` in `.env` file
- **Check**: Ensure election exists in database

**Problem**: Election-specific URLs not working
- **Solution**: Verify election ID exists in `elections` table
- **Check**: Test URL: `http://localhost:8080/voter_page.php?election_id=YOUR_ID`

### Email Configuration Setup

VOTESYS requires email configuration for auto-generated password functionality. Follow these steps:

#### 1. Gmail Configuration (Recommended)

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password in `MAIL_PASSWORD`

3. **Configure Environment Variables**:
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_16_character_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=VOTESYS - University Voting System
```

#### 2. Outlook/Hotmail Configuration

```env
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your_email@outlook.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

#### 3. Custom SMTP Server

```env
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587  # or 465 for SSL
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls  # or ssl
```

#### 4. Testing Email Configuration

After setup, test email functionality:

1. Access admin panel: http://localhost:8080/admin
2. Add a test voter with your email
3. Check if password email is received
4. Verify email formatting and delivery

**Important Security Notes**:
- Never commit real email credentials to version control
- Use environment variables or Docker secrets
- Enable 2FA and app passwords for Gmail
- Use dedicated email accounts for system notifications

### Custom docker-compose Override

Create `docker-compose.override.yml` for development:

```yaml
version: '3.8'

services:
  web:
    environment:
      - APP_DEBUG=true
    volumes:
      - .:/var/www/html
    ports:
      - "80:80"
      - "443:443"

  db:
    ports:
      - "3307:3306"  # Changed from 3306 to avoid XAMPP conflicts
```

## Service Details

### Web Service (Apache + PHP)

**Container**: `votesys_web`
**Ports**: 8080 (HTTP), 8443 (HTTPS)
**Volumes**:
- `./uploads:/var/www/html/uploads` - File uploads
- `./logs:/var/www/html/logs` - Application logs
- `./config:/var/www/html/config` - Configuration files

**Features**:
- PHP 8.1 with required extensions
- Apache with mod_rewrite enabled
- Security headers configured
- File upload restrictions
- Error logging

### Database Service (MySQL)

**Container**: `votesys_db`
**Port**: 3307 (changed from 3306 to avoid XAMPP conflicts)
**Volume**: `db_data` (persistent storage)

**Features**:
- MySQL 8.0 with utf8mb4 charset
- Automatic schema initialization
- Health checks
- Performance optimizations

### phpMyAdmin Service

**Container**: `votesys_phpmyadmin`
**Port**: 8081

**Features**:
- Web-based database management
- Pre-configured connection
- Import/export capabilities

### Redis Service (Optional)

**Container**: `votesys_redis`
**Port**: 6380 (changed from 6379 to avoid conflicts)
**Volume**: `redis_data` (persistent storage)

**Features**:
- Session storage
- Application caching
- Data persistence

## Management Commands

### Basic Operations

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart services
docker-compose restart

# View logs
docker-compose logs -f [service_name]

# Execute commands in container
docker-compose exec web bash
docker-compose exec db mysql -u root -p
```

### Database Operations

```bash
# Access MySQL CLI
docker-compose exec db mysql -u votesys_user -p votesys

# Backup database
docker-compose exec db mysqldump -u root -p votesys > backup.sql

# Restore database
docker-compose exec -T db mysql -u root -p votesys < backup.sql

# Reset database
docker-compose down -v
docker-compose up -d
```

### Application Management

```bash
# View application logs
docker-compose exec web tail -f /var/www/html/logs/app.log

# Clear application cache
docker-compose exec web rm -rf /var/www/html/cache/*

# Update file permissions
docker-compose exec web chown -R www-data:www-data /var/www/html

# Install Composer dependencies
docker-compose exec web composer install
```

## Development Workflow

### 1. Development Setup

```bash
# Use development override
cp docker-compose.override.yml.example docker-compose.override.yml

# Start with development settings
docker-compose up -d

# Enable debug mode
docker-compose exec web sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' /var/www/html/config/database.php
```

### 2. Code Changes

- Edit files directly on host
- Changes reflect immediately (volume mounted)
- Check logs: `docker-compose logs -f web`

### 3. Database Changes

```bash
# Apply schema changes
docker-compose exec web php admin/scripts/migrate.php

# Seed test data
docker-compose exec web php admin/scripts/seed.php
```

## Production Deployment

### 1. Security Hardening

```bash
# Change default passwords
docker-compose exec db mysql -u root -p
# UPDATE admins SET password = PASSWORD('new_secure_password') WHERE username = 'admin';

# Update environment variables
vim .env
```

### 2. SSL Configuration

```bash
# Add SSL certificates
mkdir -p ./docker/ssl
cp your-cert.pem ./docker/ssl/
cp your-key.pem ./docker/ssl/

# Update docker-compose.yml to mount SSL certificates
```

### 3. Performance Optimization

```bash
# Enable production optimizations
docker-compose exec web composer install --no-dev --optimize-autoloader

# Configure opcache
docker-compose exec web sed -i 's/opcache.enable=0/opcache.enable=1/' /usr/local/etc/php/conf.d/votesys.ini
```

## Monitoring and Maintenance

### Health Checks

```bash
# Check service health
docker-compose ps

# Check container resources
docker stats

# Check logs for errors
docker-compose logs --tail=100 web | grep -i error
```

### Backup Strategy

```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
docker-compose exec -T db mysqldump -u root -p$MYSQL_ROOT_PASSWORD votesys > "backup_db_$DATE.sql"

# Files backup
tar -czf "backup_files_$DATE.tar.gz" uploads/ logs/ config/

echo "Backup completed: $DATE"
```

### Log Rotation

```bash
# Configure logrotate
sudo tee /etc/logrotate.d/votesys << EOF
/var/lib/docker/containers/*/*-json.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 0644 root root
}
EOF
```

## Troubleshooting

### Common Issues

**Port Conflicts**
```bash
# Check port usage
netstat -tulpn | grep :8080

# Use different ports
vim docker-compose.yml
# Change "8080:80" to "8081:80"
```

**Permission Issues**
```bash
# Fix file permissions
docker-compose exec web chown -R www-data:www-data /var/www/html
docker-compose exec web chmod -R 755 /var/www/html
```

**Database Connection Issues**
```bash
# Check database status
docker-compose exec db mysqladmin ping -h localhost -u root -p

# Restart database
docker-compose restart db
```

**Memory Issues**
```bash
# Check memory usage
docker stats

# Increase memory limits in docker-compose.yml
```

### Debug Mode

```bash
# Enable debug logging
docker-compose exec web sed -i 's/display_errors = Off/display_errors = On/' /usr/local/etc/php/conf.d/votesys.ini

# Restart web service
docker-compose restart web

# View PHP errors
docker-compose exec web tail -f /var/log/apache2/php_errors.log
```

## Advanced Configuration

### Custom PHP Configuration

Edit `docker/php.ini` and rebuild:

```bash
docker-compose down
docker-compose build --no-cache web
docker-compose up -d
```

### Custom Apache Configuration

Edit `docker/apache.conf` and rebuild:

```bash
docker-compose restart web
```

### Multi-Environment Setup

```bash
# Development
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Production
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Testing
docker-compose -f docker-compose.yml -f docker-compose.test.yml up -d
```

## Security Considerations

1. **Change Default Passwords**: Update all default credentials
2. **Use Environment Variables**: Store sensitive data in `.env`
3. **Enable HTTPS**: Configure SSL certificates
4. **Network Security**: Use Docker networks for isolation
5. **Regular Updates**: Keep Docker images updated
6. **File Permissions**: Ensure proper file ownership
7. **Backup Strategy**: Implement regular backups
8. **Monitoring**: Set up log monitoring and alerts

## Performance Tuning

### Database Optimization

```sql
-- Add to docker/mysql/init/03-performance.sql
SET GLOBAL innodb_buffer_pool_size = 256M;
SET GLOBAL query_cache_size = 64M;
SET GLOBAL max_connections = 200;
```

### PHP Optimization

```ini
# Add to docker/php.ini
opcache.memory_consumption = 256
opcache.max_accelerated_files = 10000
realpath_cache_size = 8192K
```

### Apache Optimization

```apache
# Add to docker/apache.conf
ServerLimit 16
MaxRequestWorkers 400
ThreadsPerChild 25
```

## Support

For issues and questions:
1. Check the logs: `docker-compose logs`
2. Review this documentation
3. Check Docker and system requirements
4. Consult the main VOTESYS documentation

---

**Note**: This Docker setup is designed for development and testing. For production deployment, additional security hardening and performance optimization may be required.