-- Create election_positions table if it doesn't exist
CREATE TABLE IF NOT EXISTS election_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    display_order INT DEFAULT 0,
    max_candidates INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_election_position (election_id, position_id)
);

-- Insert sample data only if the table is empty
INSERT IGNORE INTO election_positions (election_id, position_id, display_order, max_candidates)
SELECT 
    e.id as election_id,
    p.id as position_id,
    p.display_order as display_order,
    5 as max_candidates
FROM elections e
CROSS JOIN positions p
WHERE e.election_title = 'Student Government Election 2025'
LIMIT 4;