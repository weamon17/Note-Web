-- ═══════════════════════════════════════════════════════════════════════════
-- WeaNote – Demo Seed Data
-- ─────────────────────────────────────────────────────────────────────────
-- Import AFTER schema.sql:
--   mysql -u root -p weanote < database/seed.sql
--
-- OR run the PHP seeder (preferred – generates fresh bcrypt hashes):
--   php database/seed.php
--
-- Demo credentials  (password: Password123)
--   owner@example.com   → Alice Owner  (verified, note owner)
--   editor@example.com  → Bob Editor   (verified, shared editor)
--   viewer@example.com  → Carol Viewer (NOT verified, shared viewer)
--
-- Note password for "Private Thoughts": Secret123
-- ═══════════════════════════════════════════════════════════════════════════

USE `weanote`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `offline_queue`;
TRUNCATE TABLE `collaboration_events`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `note_shares`;
TRUNCATE TABLE `note_labels`;
TRUNCATE TABLE `note_images`;
TRUNCATE TABLE `labels`;
TRUNCATE TABLE `notes`;
TRUNCATE TABLE `user_preferences`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ─── Users ────────────────────────────────────────────────────────────────────
-- Passwords are bcrypt cost-10 hashes of "Password123"
-- If login fails, run `php database/seed.php` instead to regenerate hashes.
INSERT INTO `users`
  (`id`, `email`, `display_name`, `password_hash`, `is_verified`, `created_at`, `updated_at`)
VALUES
  (1, 'owner@example.com',  'Alice Owner',  '$2y$10$fhYbxPurEDrNTNHZrIN9heSjGN.1f5edEQv3p15ciICsCw/3E616e', 1, NOW(), NOW()),
  (2, 'editor@example.com', 'Bob Editor',   '$2y$10$fhYbxPurEDrNTNHZrIN9heSjGN.1f5edEQv3p15ciICsCw/3E616e', 1, NOW(), NOW()),
  (3, 'viewer@example.com', 'Carol Viewer', '$2y$10$fhYbxPurEDrNTNHZrIN9heSjGN.1f5edEQv3p15ciICsCw/3E616e', 0, NOW(), NOW());
  -- viewer (id=3) is_verified=0 → demonstrates the "unverified account" banner

-- ─── User preferences ─────────────────────────────────────────────────────────
INSERT INTO `user_preferences`
  (`user_id`, `theme`, `default_view`, `font_size`, `note_color`)
VALUES
  (1, 'light', 'grid', 'medium', '#ffffff'),
  (2, 'dark',  'grid', 'medium', '#ffffff'),
  (3, 'light', 'grid', 'medium', '#ffffff');

-- ─── Labels (owned by Alice) ──────────────────────────────────────────────────
INSERT INTO `labels`
  (`id`, `user_id`, `name`, `color`, `created_at`, `updated_at`)
VALUES
  (1, 1, 'Work',     '#4f46e5', NOW(), NOW()),
  (2, 1, 'Personal', '#10b981', NOW(), NOW()),
  (3, 1, 'School',   '#f59e0b', NOW(), NOW());

-- ─── Notes ────────────────────────────────────────────────────────────────────
INSERT INTO `notes`
  (`id`, `user_id`, `title`, `content`,
   `is_pinned`, `pinned_at`,
   `is_locked`, `note_password_hash`,
   `deleted_at`, `created_at`, `updated_at`)
VALUES

-- Note 1: Regular welcome note
(1, 1,
 'Welcome to NoteFlow',
 'NoteFlow is your personal note management workspace.\n\nKey features:\n- Create and edit notes with auto-save\n- Organise with labels\n- Pin important notes to the top\n- Lock notes with a password\n- Share notes with other users\n- Real-time collaboration\n- Works offline (PWA)\n\nStart by creating your first note!',
 0, NULL, 0, NULL, NULL, NOW(), NOW()),

-- Note 2: Pinned note
(2, 1,
 'Meeting Notes – Q2 Planning',
 'Attendees: Alice, Bob, Carol\n\nAgenda:\n1. Review Q1 results\n2. Set Q2 OKRs\n3. Resource allocation\n\nAction items:\n- Alice: prepare dashboard by Friday\n- Bob: update the roadmap doc\n- Carol: schedule follow-up call',
 1, NOW(), 0, NULL, NULL, NOW(), NOW()),

-- Note 3: Locked note  (password = Secret123, bcrypt cost-10)
(3, 1,
 'Private Thoughts',
 'This note is password-protected.\nNote password: Secret123\n\nThis content is only visible after unlocking.',
 0, NULL,
 1, '$2y$10$w.axb2nLnYRsK5S6ddDLTugoyYMgdf4XNYq7/ufe8tUJu5S0QBtLS',
 NULL, NOW(), NOW()),

-- Note 4: Shared with editor (edit permission) + multi-label
(4, 1,
 'Project Specification',
 '## NoteFlow Specification\n\n### Overview\nNoteFlow is a collaborative note management application built with PHP 8 MVC.\n\n### Tech Stack\n- Backend: Native PHP 8 (no framework)\n- Database: MySQL / MariaDB\n- Frontend: Bootstrap 5, Vanilla JS\n- WebSocket: Ratchet (real-time collaboration)\n- Email: PHPMailer\n- PWA: Service Worker + IndexedDB\n\n### Collaboration\nThis note is shared with Bob Editor (edit permission).\nOpen this note in two browser tabs to test real-time collaboration.',
 0, NULL, 0, NULL, NULL, NOW(), NOW()),

-- Note 5: Shared with viewer (read-only) and editor (read-only)
(5, 1,
 'Team Announcement',
 'Hi team,\n\nJust a quick heads-up: the deployment window is this Saturday 02:00-04:00 UTC.\n\nPlease ensure all pending PRs are merged by Friday EOD.\n\nCheers,\nAlice',
 0, NULL, 0, NULL, NULL, NOW(), NOW()),

-- Note 6: Note with labels for label-filter demo
(6, 1,
 'Study Schedule – Final Exams',
 'Week 1:\n- Monday: Web Programming review\n- Tuesday: Database Systems\n- Wednesday: Algorithms\n\nWeek 2:\n- Monday: Software Engineering\n- Tuesday: Project presentations\n\nReminder: Submit final project by end of week 2!',
 0, NULL, 0, NULL, NULL, NOW(), NOW()),

-- Note 7: In trash (soft-deleted)
(7, 1,
 'Draft – delete me',
 'This note was moved to trash. Restore it or permanently delete it from the Trash page.',
 0, NULL, 0, NULL, NOW(), NOW(), NOW()),

-- Note 8: Bob Editor's own note
(8, 2,
 'My Personal Note',
 'Reminder to self: review the NoteFlow codebase tonight.\n\nAlso pick up groceries:\n- Milk\n- Bread\n- Coffee',
 0, NULL, 0, NULL, NULL, NOW(), NOW());

-- ─── Note labels ──────────────────────────────────────────────────────────────
INSERT INTO `note_labels` (`note_id`, `label_id`) VALUES
  (1, 2),   -- Welcome note → Personal
  (2, 1),   -- Meeting Notes → Work
  (4, 1),   -- Project Spec  → Work
  (4, 3),   -- Project Spec  → School  (multi-label demo)
  (6, 3);   -- Study Schedule → School

-- ─── Note shares ──────────────────────────────────────────────────────────────
INSERT INTO `note_shares`
  (`note_id`, `owner_id`, `shared_with_id`, `permission`, `created_at`, `updated_at`)
VALUES
  (4, 1, 2, 'edit', NOW(), NOW()),   -- Project Spec → Bob can edit (collaboration demo)
  (5, 1, 3, 'read', NOW(), NOW()),   -- Team Announcement → Carol view-only
  (5, 1, 2, 'read', NOW(), NOW());   -- Team Announcement → Bob view-only

-- ─── Notifications ────────────────────────────────────────────────────────────
INSERT INTO `notifications`
  (`user_id`, `type`, `message`, `is_read`, `data`, `created_at`)
VALUES
  (2, 'share',
   'Alice Owner shared "Project Specification" with you (edit permission).',
   0,
   '{"note_id":4,"permission":"edit"}',
   NOW()),
  (2, 'share',
   'Alice Owner shared "Team Announcement" with you (read permission).',
   0,
   '{"note_id":5,"permission":"read"}',
   NOW()),
  (3, 'share',
   'Alice Owner shared "Team Announcement" with you (read permission).',
   0,
   '{"note_id":5,"permission":"read"}',
   NOW());

SET FOREIGN_KEY_CHECKS = 1;