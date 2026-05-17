-- Migration 001 – Phase 2
-- Fixes reset_otp column to hold bcrypt hash (60+ chars) instead of plain 6-digit OTP.
-- Run ONLY if you already imported schema.sql from Phase 1.

USE `noteflow`;

ALTER TABLE `users`
  MODIFY COLUMN `reset_otp` VARCHAR(255) DEFAULT NULL
    COMMENT 'bcrypt hash of the 6-digit OTP';
