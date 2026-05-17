-- Run once to add draggable-position columns to note_images
-- Safe to run multiple times (uses IF NOT EXISTS logic via column check)

ALTER TABLE note_images
  ADD COLUMN IF NOT EXISTS x_pct       DECIMAL(5,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS y_pct       DECIMAL(5,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS sort_order  INT          NOT NULL DEFAULT 0;
