-- VOTESYS MySQL Initialization Script
-- This script sets up proper permissions and initial configuration

USE votesys;

-- Grant proper permissions to votesys_user
GRANT SELECT, INSERT, UPDATE, DELETE ON votesys.* TO 'votesys_user'@'%';
GRANT CREATE, DROP, ALTER, INDEX ON votesys.* TO 'votesys_user'@'%';
FLUSH PRIVILEGES;

-- Create indexes for better performance
CREATE INDEX idx_voters_email ON voters(email);
CREATE INDEX idx_voters_student_id ON voters(student_id);
CREATE INDEX idx_votes_election ON votes(election_id);
CREATE INDEX idx_votes_voter ON votes(voter_id);
CREATE INDEX idx_candidates_position ON candidates(position_id);
CREATE INDEX idx_positions_election ON positions(election_id);
CREATE INDEX idx_audit_logs_timestamp ON audit_logs(timestamp);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_sessions_user ON sessions(user_id);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);

-- Insert default admin user (password: admin123 - should be changed in production)
INSERT INTO admins (username, password, email, full_name, role, created_at) 
VALUES (
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'admin@votesys.local',
    'System Administrator',
    'super_admin',
    NOW()
) ON DUPLICATE KEY UPDATE username=username;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description, created_at) VALUES
('system_name', 'VOTESYS', 'Name of the voting system', NOW()),
('system_version', '1.0.0', 'Current system version', NOW()),
('maintenance_mode', '0', 'System maintenance mode (0=off, 1=on)', NOW()),
('max_login_attempts', '5', 'Maximum login attempts before lockout', NOW()),
('session_timeout', '7200', 'Session timeout in seconds', NOW()),
('password_min_length', '8', 'Minimum password length', NOW()),
('file_upload_max_size', '10485760', 'Maximum file upload size in bytes', NOW()),
('timezone', 'UTC', 'System timezone', NOW()),
('date_format', 'Y-m-d H:i:s', 'Default date format', NOW()),
('email_notifications', '1', 'Enable email notifications (0=off, 1=on)', NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Create a sample election for testing (optional)
INSERT INTO elections (title, description, start_date, end_date, status, created_by, created_at) 
VALUES (
    'Sample Student Council Election',
    'This is a sample election for testing purposes. Please delete this in production.',
    DATE_ADD(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    'upcoming',
    1,
    NOW()
) ON DUPLICATE KEY UPDATE title=title;

-- Log the initialization
INSERT INTO audit_logs (user_id, action, table_name, record_id, details, ip_address, user_agent, timestamp)
VALUES (
    1,
    'SYSTEM_INIT',
    'system',
    0,
    'Docker container initialized with default data',
    'docker-init',
    'Docker Container',
    NOW()
);

-- Display initialization complete message
SELECT 'VOTESYS Database Initialization Complete!' as message;
SELECT CONCAT('Default admin user created: admin / admin123') as credentials;
SELECT 'Please change the default password immediately!' as security_warning;