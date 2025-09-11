-- Update voters table to include additional fields from registration form
-- This script adds missing columns to support the voter registration requirements

USE voting_system;

-- Add phone column to voters table
ALTER TABLE voters ADD COLUMN phone VARCHAR(20) NULL AFTER course;

-- Add gender column to voters table
ALTER TABLE voters ADD COLUMN gender ENUM('Male', 'Female') NULL AFTER phone;

-- Add department column (separate from course) if needed
-- Note: The registration form uses 'department' field name but maps to 'course' column
-- If you want a separate department field, uncomment the line below:
-- ALTER TABLE voters ADD COLUMN department VARCHAR(100) NULL AFTER course;

-- Update existing voters to have NULL values for new fields initially
UPDATE voters SET phone = NULL WHERE phone IS NULL;
UPDATE voters SET gender = NULL WHERE gender IS NULL;

-- Add indexes for better performance on new columns
CREATE INDEX idx_voters_phone ON voters(phone);
CREATE INDEX idx_voters_gender ON voters(gender);

-- Display updated table structure
DESCRIBE voters;