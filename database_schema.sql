-- Heritage Christian University Online Voting System Database Schema
-- Created for PHP 8+ Admin Dashboard

CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- bcrypt hashed
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Elections table
CREATE TABLE IF NOT EXISTS elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    allow_live_results BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(id) ON DELETE SET NULL
);

-- Positions table
CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    order_priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Election Positions table (links positions to specific elections)
CREATE TABLE IF NOT EXISTS election_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    display_order INT DEFAULT 0,
    max_candidates INT DEFAULT 10,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_election_position (election_id, position_id)
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    program VARCHAR(150),
    manifesto TEXT,
    photo MEDIUMBLOB,
    photo_path VARCHAR(500),
    photo_blob MEDIUMBLOB,
    is_active BOOLEAN DEFAULT TRUE,
    vote_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
);

-- Voters table
CREATE TABLE IF NOT EXISTS voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- bcrypt hashed
    year_level ENUM('1st', '2nd', '3rd', '4th', 'Graduate') NOT NULL,
    course VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    has_voted BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Votes table
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id INT NOT NULL,
    candidate_id INT NOT NULL,
    position_id INT NOT NULL,
    election_id INT,
    vote_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (voter_id, position_id)
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'voter') NOT NULL,
    user_id INT,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
);

-- Sessions table for security
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    user_type ENUM('admin', 'voter') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CSRF tokens table
CREATE TABLE IF NOT EXISTS csrf_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    user_id INT,
    user_type ENUM('admin', 'voter') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin (username, email, password, role) VALUES 
('admin', 'admin@hcu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Sample election data
INSERT INTO elections (title, description, start_date, end_date, is_active) VALUES 
('Student Government Election 2025', 'Annual student government election for Heritage Christian University', '2025-03-01 08:00:00', '2025-03-03 18:00:00', TRUE);

-- Sample positions
INSERT INTO positions (name, description, order_priority) VALUES 
('President', 'Student Government President', 1),
('Vice President', 'Student Government Vice President', 2),
('Secretary', 'Student Government Secretary', 3),
('Treasurer', 'Student Government Treasurer', 4);

-- Link positions to the sample election
SET @election_id = (SELECT id FROM elections WHERE title = 'Student Government Election 2025' LIMIT 1);
SET @president_pos_id = (SELECT id FROM positions WHERE name = 'President' LIMIT 1);
SET @vp_pos_id = (SELECT id FROM positions WHERE name = 'Vice President' LIMIT 1);
SET @secretary_pos_id = (SELECT id FROM positions WHERE name = 'Secretary' LIMIT 1);
SET @treasurer_pos_id = (SELECT id FROM positions WHERE name = 'Treasurer' LIMIT 1);

INSERT INTO election_positions (election_id, position_id, display_order, max_candidates) VALUES 
(@election_id, @president_pos_id, 1, 5),
(@election_id, @vp_pos_id, 2, 5),
(@election_id, @secretary_pos_id, 3, 3),
(@election_id, @treasurer_pos_id, 4, 3);

-- Sample candidates
SET @president_pos = (SELECT id FROM positions WHERE name = 'President' LIMIT 1);
SET @vp_pos = (SELECT id FROM positions WHERE name = 'Vice President' LIMIT 1);

INSERT INTO candidates (position_id, name, program, manifesto) VALUES 
(@president_pos, 'John Smith', 'Computer Science', 'I will work to improve student services and create more opportunities for academic and social growth.'),
(@president_pos, 'Sarah Johnson', 'Business Administration', 'My goal is to enhance communication between students and administration while promoting campus unity.'),
(@vp_pos, 'Michael Brown', 'Engineering', 'I will focus on improving campus facilities and supporting student organizations.'),
(@vp_pos, 'Emily Davis', 'Education', 'I am committed to creating inclusive programs that benefit all students regardless of their background.');

-- Sample voters
INSERT INTO voters (student_id, first_name, last_name, email, password, year_level, course) VALUES 
('2021001', 'Alice', 'Wilson', 'alice.wilson@student.hcu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '4th', 'Computer Science'),
('2022002', 'Bob', 'Martinez', 'bob.martinez@student.hcu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3rd', 'Business Administration'),
('2023003', 'Carol', 'Taylor', 'carol.taylor@student.hcu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2nd', 'Engineering'),
('2024004', 'David', 'Anderson', 'david.anderson@student.hcu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1st', 'Education');

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample courses
INSERT INTO courses (name, category) VALUES 
('Computer Science', 'Technology'),
('Information Technology', 'Technology'),
('Software Engineering', 'Technology'),
('Business Administration', 'Business'),
('Marketing', 'Business'),
('Accounting', 'Business'),
('Civil Engineering', 'Engineering'),
('Mechanical Engineering', 'Engineering'),
('Electrical Engineering', 'Engineering'),
('Elementary Education', 'Education'),
('Secondary Education', 'Education'),
('Special Education', 'Education'),
('Nursing', 'Health Sciences'),
('Physical Therapy', 'Health Sciences'),
('Medical Technology', 'Health Sciences'),
('Psychology', 'Social Sciences'),
('Sociology', 'Social Sciences'),
('Political Science', 'Social Sciences');

-- Create indexes for better performance
CREATE INDEX idx_elections_active ON elections(is_active);
CREATE INDEX idx_elections_dates ON elections(start_date, end_date);
CREATE INDEX idx_candidates_election ON candidates(election_id);
CREATE INDEX idx_candidates_position ON candidates(position_id);
CREATE INDEX idx_voters_student_id ON voters(student_id);
CREATE INDEX idx_voters_email ON voters(email);
CREATE INDEX idx_votes_election ON votes(election_id);
CREATE INDEX idx_votes_voter ON votes(voter_id);
CREATE INDEX idx_votes_candidate ON votes(candidate_id);
CREATE INDEX idx_audit_logs_admin ON audit_logs(admin_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);
CREATE INDEX idx_sessions_user ON sessions(user_id, user_type);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);
CREATE INDEX idx_csrf_tokens_expires ON csrf_tokens(expires_at);