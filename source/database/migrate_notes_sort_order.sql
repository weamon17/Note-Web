-- Add sort_order column to notes for drag-to-reorder feature
-- Safe to run multiple times (uses IF NOT EXISTS)

ALTER TABLE notes
  ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0;

-- Index to speed up ORDER BY user_id + sort_order queries
CREATE INDEX IF NOT EXISTS idx_notes_user_sort
  ON notes (user_id, sort_order);
