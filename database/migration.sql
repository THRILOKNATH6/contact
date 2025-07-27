-- Migration script to add new columns to existing forms table
-- Run this if you already have the database set up

ALTER TABLE forms 
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER created_by,
ADD COLUMN allowed_roles TEXT DEFAULT 'ie,ie_incharge,ie_manager' AFTER status;

-- Update existing forms to have default values
UPDATE forms SET status = 'active', allowed_roles = 'ie,ie_incharge,ie_manager' WHERE status IS NULL;