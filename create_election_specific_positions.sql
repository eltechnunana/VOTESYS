-- Create election-specific positions table
CREATE TABLE IF NOT EXISTS election_specific_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    position_title VARCHAR(255) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    max_candidates INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    INDEX idx_election_positions (election_id),
    INDEX idx_display_order (display_order)
);

-- Update candidates table to reference election_specific_positions
-- First, add a new column for election_specific_position_id
ALTER TABLE candidates ADD COLUMN election_specific_position_id INT NULL AFTER position_id;

-- Add foreign key constraint
ALTER TABLE candidates ADD CONSTRAINT fk_candidates_election_specific_position 
    FOREIGN KEY (election_specific_position_id) REFERENCES election_specific_positions(id) ON DELETE CASCADE;

-- Create index for better performance
CREATE INDEX idx_candidates_election_specific_position ON candidates(election_specific_position_id);

-- Update votes table to reference election_specific_positions
ALTER TABLE votes ADD COLUMN election_specific_position_id INT NULL AFTER position_id;

-- Add foreign key constraint for votes
ALTER TABLE votes ADD CONSTRAINT fk_votes_election_specific_position 
    FOREIGN KEY (election_specific_position_id) REFERENCES election_specific_positions(id) ON DELETE CASCADE;

-- Create index for better performance
CREATE INDEX idx_votes_election_specific_position ON votes(election_specific_position_id);