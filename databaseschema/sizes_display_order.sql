-- Database Migration: Add Display Order to Sizes Table
-- Date: 2026-02-26
-- Description: Adds display_order column to sizes table for custom ordering

USE `modernwarehouse`;

-- Add display_order column after name column
-- Using INT type with default value of 1
-- This ensures existing records get a valid display_order value
ALTER TABLE `sizes`
ADD COLUMN `display_order` INT NOT NULL DEFAULT 1 AFTER `name`;

-- Verification query (optional - for testing)
-- SELECT id, name, display_order FROM sizes ORDER BY display_order, name;
