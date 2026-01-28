-- Database Migration: Remove Expiration Year from Items
-- Date: 2026-01-26
-- Description: Drops the obsolete expiration_year column from the items table

USE `modernwarehouse`;

-- Drop the expiration_year column if it exists
-- Note: MySQL doesn't support 'IF EXISTS' for columns directly in ALTER TABLE 
-- unless using a procedure, but for a standard migration script, this is the way:
ALTER TABLE `items` 
DROP COLUMN `expiration_year`;

-- Verification query
-- DESCRIBE items;