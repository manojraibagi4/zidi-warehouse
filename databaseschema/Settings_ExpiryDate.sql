-- Database Migration: Add Expiry Days Setting
-- Date: 2026-01-26
-- Description: Adds expiry_days setting to the settings table

USE `modernwarehouse`;

-- Insert expiry_days setting with default value of 10 days
INSERT INTO `settings` (`setting_key`, `setting_value`, `updated_at`)
VALUES ('expiry_days', '10', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Verification query (optional - for testing)
-- SELECT * FROM settings WHERE setting_key = 'expiry_days';
