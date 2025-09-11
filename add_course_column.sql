-- Add course column to candidates table
ALTER TABLE candidates ADD COLUMN course VARCHAR(100) NULL AFTER program;

-- Update existing candidates to have empty course initially
UPDATE candidates SET course = '' WHERE course IS NULL;