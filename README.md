# VOTESYS - University Online Voting System

![VOTESYS Logo](assets/images/hcu-logo.svg)

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Security Features](#security-features)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

VOTESYS is a secure, responsive, and robust University Online Voting System built with PHP 8+ and MySQL. The system follows MVC architecture and implements strict security best practices to ensure transparent, tamper-proof elections for university governance.

### Key Objectives
- **Fully responsive** design for mobile, tablet, and desktop
- **Optimized performance** with page loads under 2 seconds
- **Transparent and tamper-proof** voting process
- **Scalable** to handle thousands of concurrent users
- **Secure** with comprehensive security measures

## ✨ Features

### Admin Features
- 🗳️ **Election Management**: Create and manage elections with start/end dates
- 👥 **Candidate Management**: Add candidates with photos and position details
- 📊 **Real-time Monitoring**: Live voting progress and statistics
- 📈 **Results Publishing**: Automated result calculation and publishing
- 📋 **Audit Logs**: Comprehensive activity tracking
- 📤 **Export Capabilities**: Results export in PDF/Excel formats
- 👤 **User Management**: Admin user creation and permission management
- 🔐 **Auto-Generated Passwords**: Secure password generation and email delivery
- 📧 **Email Notifications**: Automated email system for voter credentials

### Voter Features
- 🔐 **Secure Login**: Individual voter authentication with auto-generated passwords
- 🗳️ **Intuitive Voting**: Clean, accessible voting interface
- 👨‍🎓 **Candidate Profiles**: View candidate details and photos
- ✅ **Vote Confirmation**: Secure vote submission with confirmation
- 📱 **Mobile Responsive**: Vote from any device
- 📧 **Email Integration**: Receive login credentials via secure email

### Security Features
- 🔒 **HTTPS Enforcement**: All communications encrypted
- 🛡️ **CSRF Protection**: Cross-site request forgery prevention
- 💉 **SQL Injection Prevention**: Prepared statements throughout
- 🔐 **Password Security**: bcrypt hashing with salt and auto-generation
- 🚫 **Brute Force Protection**: Login attempt limiting
- 🍪 **Secure Sessions**: HttpOnly and Secure cookie flags
- 🔐 **Vote Encryption**: All votes encrypted in database
- 👤 **Voter Anonymity**: No direct link between vote and voter
- 📧 **Secure Email Delivery**: Encrypted email transmission for credentials
- 🎲 **Cryptographically Secure Passwords**: Auto-generated using secure random functions

## 🖥️ System Requirements

### Server Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL Certificate**: Required for production

### PHP Extensions
- `mysqli` or `pdo_mysql`
- `openssl`
- `session`
- `json`
- `mbstring`
- `gd` or `imagick` (for image processing)
- `curl` (for email functionality)

### Email Configuration
- **SMTP Server**: Gmail, Outlook, or custom SMTP server
- **SSL/TLS Support**: Required for secure email transmission
- **Authentication**: SMTP username and password or OAuth2
- **PHPMailer**: Included via Composer for email functionality

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🚀 Installation

### Option 1: Docker Setup (Recommended)

**Prerequisites:**
- Docker Desktop (Windows/Mac) or Docker Engine (Linux)
- Docker Compose v2.0+
- At least 2GB RAM available

**Quick Start:**
```bash
# Clone the repository
cd /path/to/VOTESYS

# Build and start containers
docker-compose up -d --build

# Access the application
# Web: http://localhost:8080
# Admin: http://localhost:8080/admin
# phpMyAdmin: http://localhost:8081
```

**Default Credentials:**
- Admin: `admin` / `admin123`
- Database: `votesys_user` / `votesys_password`

For detailed Docker setup, see [Docker Documentation](docs/DOCKER.md).

### Option 2: Traditional XAMPP Setup

**Prerequisites:**
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

**Setup Steps:**

1. **Download and Install XAMPP**
   - Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Install and start Apache and MySQL services

2. **Clone/Download VOTESYS**
   ```bash
   cd C:\xampp\htdocs
   # Place VOTESYS folder here
   ```

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `votesys`
   - Import the `database_schema.sql` file

4. **Configuration**
   - Navigate to the `config` folder
   - Update database connection settings
   - Configure application settings as needed

5. **Create Admin User**
   - Access the application via web browser
   - Use the admin interface to create your first admin account
   - Or insert directly into the database following the schema

6. **Access the Application**
   - Admin Panel: `http://localhost/VOTESYS/admin`
   - Voter Portal: `http://localhost/VOTESYS/voter`

### Option 3: Manual Installation

1. **Clone Repository**
```bash
git clone https://github.com/your-repo/votesys.git
cd votesys
```

2. **Install Dependencies**
```bash
composer install
```

3. **Database Setup**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE votesys;"

# Import schema
mysql -u root -p votesys < database_schema.sql
```

4. **Configuration**
1. Copy `config/database.php.example` to `config/database.php`
2. Update database credentials
3. Configure other settings in `config/` directory

5. **Set Permissions**
```bash
chmod 755 uploads/
chmod 755 logs/
```

6. **Create Admin User**
Use the admin interface or database to create your first admin user.

## 📁 Project Structure

```
VOTESYS/
├── admin/                  # Admin panel
│   ├── api/               # API endpoints
│   ├── *.php             # Admin pages
│   └── rules             # System requirements
├── assets/               # Static assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Images and icons
├── config/              # Configuration files
│   ├── database.php     # Database configuration
│   ├── security.php     # Security settings
│   └── session.php      # Session configuration
├── logs/                # Application logs
├── uploads/             # File uploads
│   └── candidates/      # Candidate photos
├── vendor/              # Composer dependencies
├── voter/               # Voter-specific files
├── *.php               # Main application files
└── database_schema.sql  # Database schema
```

## ⚙️ Configuration

### Database Configuration
Edit `config/database.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votesys');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
?>
```

### Security Configuration
Edit `config/security.php` for security settings:
- Session timeout
- Password requirements
- Login attempt limits
- CSRF token settings

## 📖 Usage

### For Administrators
1. Access admin panel at `/admin/login.php`
2. Login with admin credentials
3. Create elections and add candidates
4. Monitor voting progress
5. Publish results when voting ends

### For Voters
1. Access voter login at `/voter_login.php`
2. Login with voter credentials
3. View available elections
4. Cast votes for preferred candidates
5. Receive confirmation of vote submission

## 📚 API Documentation

Detailed API documentation is available in the following files:
- [Admin API Documentation](docs/API.md)
- [Database Schema Documentation](docs/DATABASE.md)
- [User Guides](docs/USER_GUIDES.md)

## 🔒 Security Features

### Authentication & Authorization
- Separate login systems for admins and voters
- Session-based authentication
- Role-based access control
- Secure password hashing

### Data Protection
- All votes encrypted in database
- Voter anonymity maintained
- CSRF protection on all forms
- SQL injection prevention
- XSS protection

### Infrastructure Security
- HTTPS enforcement
- Secure session configuration
- Login attempt limiting
- Comprehensive audit logging

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive comments
- Include security considerations
- Test all functionality thoroughly
- Update documentation for changes

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Contact the development team
- Check the documentation in the `docs/` folder

---

**Note**: This system is designed for university elections and implements strict security measures. Always test thoroughly before deploying to production.