-- Migration 002 – Phase 4
-- Adds font_size and note_color columns to user_preferences.
-- Run ONLY if you already imported schema.sql from Phase 1.

USE `noteflow`;

ALTER TABLE `user_preferences`
  ADD COLUMN `font_size`   ENUM('small','medium','large') NOT NULL DEFAULT 'medium'
    COMMENT 'UI font size preference' AFTER `default_view`,
  ADD COLUMN `note_color`  VARCHAR(7) NOT NULL DEFAULT '#ffffff'
    COMMENT 'Custom note card background colour (hex). #ffffff = use theme default' AFTER `font_size`;
