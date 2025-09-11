# VOTESYS Database Schema Documentation

## Overview

This document provides comprehensive documentation for the VOTESYS database schema. The system uses MySQL/MariaDB as the primary database management system.

## Database Configuration

**Database Name**: `votesys`
**Character Set**: `utf8mb4`
**Collation**: `utf8mb4_unicode_ci`

## Tables Overview

| Table | Purpose | Records |
|-------|---------|----------|
| `admins` | System administrators | Low |
| `elections` | Election definitions | Medium |
| `positions` | Election positions | Medium |
| `candidates` | Election candidates | Medium |
| `voters` | Registered voters | High |
| `votes` | Cast votes | High |
| `audit_logs` | System audit trail | High |
| `sessions` | User sessions | Medium |
| `settings` | System configuration | Low |

## Table Schemas

### 1. admins
Stores system administrator accounts.

```sql
CREATE TABLE `admins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
    `is_active` tinyint(1) DEFAULT 1,
    `last_login` datetime NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `username`: Unique administrator username
- `email`: Unique administrator email
- `password`: Hashed password (bcrypt)
- `full_name`: Administrator's full name
- `role`: Administrator role level
- `is_active`: Account status (1=active, 0=inactive)
- `last_login`: Last login timestamp
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

### 2. elections
Stores election definitions and configurations.

```sql
CREATE TABLE `elections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(200) NOT NULL,
    `description` text,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `status` enum('draft','active','paused','completed','cancelled') DEFAULT 'draft',
    `allow_multiple_votes` tinyint(1) DEFAULT 0,
    `require_verification` tinyint(1) DEFAULT 1,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE RESTRICT,
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `title`: Election title
- `description`: Detailed election description
- `start_date`: Election start date and time
- `end_date`: Election end date and time
- `status`: Current election status
- `allow_multiple_votes`: Whether voters can vote multiple times
- `require_verification`: Whether votes require verification
- `created_by`: Admin who created the election
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

### 3. positions
Stores positions available in elections.

```sql
CREATE TABLE `positions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `election_id` int(11) NOT NULL,
    `title` varchar(100) NOT NULL,
    `description` text,
    `max_candidates` int(11) DEFAULT 10,
    `max_votes_per_voter` int(11) DEFAULT 1,
    `order_position` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    INDEX `idx_election` (`election_id`),
    INDEX `idx_order` (`order_position`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `election_id`: Reference to parent election
- `title`: Position title (e.g., "President", "Secretary")
- `description`: Position description and responsibilities
- `max_candidates`: Maximum number of candidates for this position
- `max_votes_per_voter`: Maximum votes a voter can cast for this position
- `order_position`: Display order in ballot
- `is_active`: Position status
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

### 4. candidates
Stores candidate information for elections.

```sql
CREATE TABLE `candidates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `election_id` int(11) NOT NULL,
    `position_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `student_id` varchar(50),
    `course` varchar(100),
    `year_level` varchar(20),
    `bio` text,
    `photo` varchar(255),
    `platform` text,
    `is_active` tinyint(1) DEFAULT 1,
    `vote_count` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
    INDEX `idx_election` (`election_id`),
    INDEX `idx_position` (`position_id`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_vote_count` (`vote_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `election_id`: Reference to election
- `position_id`: Reference to position
- `name`: Candidate's full name
- `student_id`: Student identification number
- `course`: Academic course/program
- `year_level`: Academic year level
- `bio`: Candidate biography
- `photo`: Profile photo filename
- `platform`: Campaign platform/manifesto
- `is_active`: Candidate status
- `vote_count`: Cached vote count for performance
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

### 5. voters
Stores registered voter information.

```sql
CREATE TABLE `voters` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` varchar(50) NOT NULL UNIQUE,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `course` varchar(100),
    `year_level` varchar(20),
    `department` varchar(100),
    `phone` varchar(20),
    `is_active` tinyint(1) DEFAULT 1,
    `email_verified` tinyint(1) DEFAULT 0,
    `verification_token` varchar(255),
    `last_login` datetime NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_student_id` (`student_id`),
    UNIQUE KEY `uk_email` (`email`),
    INDEX `idx_course` (`course`),
    INDEX `idx_year_level` (`year_level`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_verified` (`email_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `student_id`: Unique student identification
- `name`: Voter's full name
- `email`: Unique email address
- `password`: Hashed password (bcrypt)
- `course`: Academic course/program
- `year_level`: Academic year level
- `department`: Academic department
- `phone`: Contact phone number
- `is_active`: Account status
- `email_verified`: Email verification status
- `verification_token`: Email verification token
- `last_login`: Last login timestamp
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

### 6. votes
Stores cast votes with anonymity protection.

```sql
CREATE TABLE `votes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `election_id` int(11) NOT NULL,
    `position_id` int(11) NOT NULL,
    `candidate_id` int(11) NOT NULL,
    `voter_hash` varchar(255) NOT NULL,
    `ip_address` varchar(45),
    `user_agent` text,
    `vote_timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    `is_verified` tinyint(1) DEFAULT 0,
    `verification_code` varchar(10),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE,
    INDEX `idx_election` (`election_id`),
    INDEX `idx_position` (`position_id`),
    INDEX `idx_candidate` (`candidate_id`),
    INDEX `idx_voter_hash` (`voter_hash`),
    INDEX `idx_timestamp` (`vote_timestamp`),
    INDEX `idx_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `election_id`: Reference to election
- `position_id`: Reference to position
- `candidate_id`: Reference to candidate
- `voter_hash`: Anonymized voter identifier
- `ip_address`: Voter's IP address (for security)
- `user_agent`: Browser user agent
- `vote_timestamp`: When vote was cast
- `is_verified`: Vote verification status
- `verification_code`: SMS/Email verification code

### 7. audit_logs
Stores system audit trail for security and compliance.

```sql
CREATE TABLE `audit_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11),
    `voter_id` int(11),
    `action` varchar(100) NOT NULL,
    `table_name` varchar(50),
    `record_id` int(11),
    `old_values` json,
    `new_values` json,
    `details` text,
    `ip_address` varchar(45),
    `user_agent` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`voter_id`) REFERENCES `voters`(`id`) ON DELETE SET NULL,
    INDEX `idx_admin` (`admin_id`),
    INDEX `idx_voter` (`voter_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table` (`table_name`),
    INDEX `idx_timestamp` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `admin_id`: Admin who performed action (nullable)
- `voter_id`: Voter who performed action (nullable)
- `action`: Action performed (e.g., CREATE, UPDATE, DELETE)
- `table_name`: Database table affected
- `record_id`: ID of affected record
- `old_values`: Previous values (JSON)
- `new_values`: New values (JSON)
- `details`: Additional action details
- `ip_address`: User's IP address
- `user_agent`: Browser user agent
- `created_at`: Action timestamp

### 8. sessions
Stores user session information.

```sql
CREATE TABLE `sessions` (
    `id` varchar(128) NOT NULL,
    `admin_id` int(11),
    `voter_id` int(11),
    `data` text,
    `ip_address` varchar(45),
    `user_agent` text,
    `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`voter_id`) REFERENCES `voters`(`id`) ON DELETE CASCADE,
    INDEX `idx_admin` (`admin_id`),
    INDEX `idx_voter` (`voter_id`),
    INDEX `idx_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Session ID (primary key)
- `admin_id`: Associated admin (nullable)
- `voter_id`: Associated voter (nullable)
- `data`: Serialized session data
- `ip_address`: Session IP address
- `user_agent`: Browser user agent
- `last_activity`: Last session activity
- `created_at`: Session creation time

### 9. settings
Stores system configuration settings.

```sql
CREATE TABLE `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(100) NOT NULL UNIQUE,
    `value` text,
    `type` enum('string','integer','boolean','json') DEFAULT 'string',
    `description` text,
    `is_public` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`),
    INDEX `idx_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
- `id`: Primary key, auto-increment
- `key`: Setting key/name
- `value`: Setting value
- `type`: Data type of value
- `description`: Setting description
- `is_public`: Whether setting is publicly accessible
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

## Relationships

### One-to-Many Relationships
- `admins` → `elections` (created_by)
- `elections` → `positions`
- `elections` → `candidates`
- `elections` → `votes`
- `positions` → `candidates`
- `positions` → `votes`
- `candidates` → `votes`
- `admins` → `audit_logs`
- `voters` → `audit_logs`
- `admins` → `sessions`
- `voters` → `sessions`

### Many-to-Many Relationships
- `voters` ↔ `elections` (through `votes`)
- `voters` ↔ `candidates` (through `votes`)

## Indexes and Performance

### Primary Indexes
- All tables have primary key indexes on `id` fields
- Unique indexes on `username`, `email`, `student_id`

### Secondary Indexes
- Foreign key indexes for referential integrity
- Status and active field indexes for filtering
- Timestamp indexes for date-based queries
- Vote count indexes for result calculations

### Query Optimization
- Use composite indexes for multi-column WHERE clauses
- Partition large tables (votes, audit_logs) by date if needed
- Consider read replicas for reporting queries

## Data Integrity

### Constraints
- Foreign key constraints maintain referential integrity
- Unique constraints prevent duplicate records
- NOT NULL constraints ensure required data
- CHECK constraints validate data ranges

### Triggers
```sql
-- Update vote count when vote is inserted
CREATE TRIGGER update_candidate_vote_count 
AFTER INSERT ON votes 
FOR EACH ROW 
UPDATE candidates 
SET vote_count = vote_count + 1 
WHERE id = NEW.candidate_id;

-- Update vote count when vote is deleted
CREATE TRIGGER decrease_candidate_vote_count 
AFTER DELETE ON votes 
FOR EACH ROW 
UPDATE candidates 
SET vote_count = vote_count - 1 
WHERE id = OLD.candidate_id;
```

## Security Considerations

### Data Protection
- Passwords are hashed using bcrypt
- Voter anonymity protected through hashing
- Sensitive data encrypted at rest
- Regular security audits

### Access Control
- Role-based access control (RBAC)
- Session-based authentication
- IP address logging for security
- Audit trail for all actions

## Backup and Recovery

### Backup Strategy
- Daily full database backups
- Hourly incremental backups during elections
- Transaction log backups every 15 minutes
- Offsite backup storage

### Recovery Procedures
- Point-in-time recovery capability
- Automated backup verification
- Disaster recovery testing
- Data retention policies

## Maintenance

### Regular Tasks
- Index optimization and rebuilding
- Statistics updates for query optimization
- Old session cleanup
- Audit log archival
- Performance monitoring

### Monitoring
- Database performance metrics
- Query execution times
- Lock contention monitoring
- Storage usage tracking
- Connection pool monitoring

---

**Note**: This database schema documentation is maintained alongside system development. Always refer to the latest version and actual database structure for accurate information.