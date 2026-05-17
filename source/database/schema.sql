-- ═══════════════════════════════════════════════════════════════════════════
-- NoteFlow – Database Schema
-- Engine : InnoDB | Charset: utf8mb4_unicode_ci
-- ═══════════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET TIME_ZONE = '+00:00';

CREATE DATABASE IF NOT EXISTS `noteflow`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `noteflow`;

-- ─── 1. users ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`                       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `email`                    VARCHAR(255)     NOT NULL,
  `display_name`             VARCHAR(100)     NOT NULL,
  `password_hash`            VARCHAR(255)     NOT NULL,
  `avatar`                   VARCHAR(255)     DEFAULT NULL,

  -- Email verification
  `is_verified`              TINYINT(1)       NOT NULL DEFAULT 0,
  `activation_token`         VARCHAR(64)      DEFAULT NULL,
  `activation_token_expires_at` DATETIME      DEFAULT NULL,

  -- Password reset
  `reset_token`              VARCHAR(64)      DEFAULT NULL,
  `reset_token_expires_at`   DATETIME         DEFAULT NULL,
  `reset_otp`                VARCHAR(255)     DEFAULT NULL,  -- stores bcrypt hash of the 6-digit OTP
  `reset_otp_expires_at`     DATETIME         DEFAULT NULL,

  `created_at`               DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_activation_token` (`activation_token`),
  KEY `idx_users_reset_token`      (`reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 2. user_preferences ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `theme`        ENUM('light','dark')           NOT NULL DEFAULT 'light',
  `default_view` ENUM('grid','list')            NOT NULL DEFAULT 'grid',
  `font_size`    ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
  `note_color`   VARCHAR(7)                     NOT NULL DEFAULT '#ffffff',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prefs_user` (`user_id`),
  CONSTRAINT `fk_prefs_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 3. notes ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notes` (
  `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED  NOT NULL,
  `title`              VARCHAR(255)  NOT NULL DEFAULT 'Untitled',
  `content`            LONGTEXT,

  -- Pin
  `is_pinned`          TINYINT(1)    NOT NULL DEFAULT 0,
  `pinned_at`          DATETIME      DEFAULT NULL,

  -- Lock (optional note-level password)
  `is_locked`          TINYINT(1)    NOT NULL DEFAULT 0,
  `note_password_hash` VARCHAR(255)  DEFAULT NULL,

  -- Soft delete
  `deleted_at`         DATETIME      DEFAULT NULL,

  `created_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_notes_user`       (`user_id`),
  KEY `idx_notes_deleted_at` (`deleted_at`),
  KEY `idx_notes_pinned`     (`user_id`, `is_pinned`),
  FULLTEXT KEY `ft_notes_search` (`title`, `content`),
  CONSTRAINT `fk_notes_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 4. note_images ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `note_images` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `note_id`       INT UNSIGNED  NOT NULL,
  `filename`      VARCHAR(255)  NOT NULL,          -- stored filename (hashed)
  `original_name` VARCHAR(255)  NOT NULL,          -- original upload name
  `mime_type`     VARCHAR(100)  NOT NULL,
  `size`          INT UNSIGNED  NOT NULL DEFAULT 0, -- bytes
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_note_images_note` (`note_id`),
  CONSTRAINT `fk_note_images_note`
    FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 5. labels ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `labels` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED  NOT NULL,
  `name`       VARCHAR(100)  NOT NULL,
  `color`      VARCHAR(7)    NOT NULL DEFAULT '#6c757d',  -- hex color
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_labels_user_name` (`user_id`, `name`),   -- unique per user
  KEY `idx_labels_user` (`user_id`),
  CONSTRAINT `fk_labels_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 6. note_labels ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `note_labels` (
  `note_id`  INT UNSIGNED NOT NULL,
  `label_id` INT UNSIGNED NOT NULL,

  PRIMARY KEY (`note_id`, `label_id`),
  KEY `idx_note_labels_label` (`label_id`),
  CONSTRAINT `fk_note_labels_note`
    FOREIGN KEY (`note_id`)  REFERENCES `notes`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_labels_label`
    FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 7. note_shares ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `note_shares` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id`        INT UNSIGNED NOT NULL,
  `owner_id`       INT UNSIGNED NOT NULL,     -- note owner who shared
  `shared_with_id` INT UNSIGNED NOT NULL,     -- recipient
  `permission`     ENUM('read','edit') NOT NULL DEFAULT 'read',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_share_note_recipient` (`note_id`, `shared_with_id`),
  KEY `idx_shares_note`        (`note_id`),
  KEY `idx_shares_shared_with` (`shared_with_id`),
  KEY `idx_shares_owner`       (`owner_id`),
  CONSTRAINT `fk_shares_note`
    FOREIGN KEY (`note_id`)        REFERENCES `notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_shares_owner`
    FOREIGN KEY (`owner_id`)       REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_shares_recipient`
    FOREIGN KEY (`shared_with_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 8. notifications ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       VARCHAR(50)  NOT NULL,              -- 'share', 'collab', etc.
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `data`       JSON         DEFAULT NULL,           -- extra context
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_notif_user`     (`user_id`),
  KEY `idx_notif_is_read`  (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 9. collaboration_events ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `collaboration_events` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `note_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `event_type` VARCHAR(50)  NOT NULL,   -- 'edit', 'cursor', 'join', 'leave'
  `payload`    JSON         DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_collab_note`    (`note_id`),
  KEY `idx_collab_user`    (`user_id`),
  KEY `idx_collab_created` (`note_id`, `created_at`),
  CONSTRAINT `fk_collab_note`
    FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─── 10. offline_queue ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `offline_queue` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `action`     VARCHAR(50)  NOT NULL,   -- 'create_note', 'update_note', 'delete_note'
  `payload`    JSON         NOT NULL,
  `synced_at`  DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_offline_user`   (`user_id`),
  KEY `idx_offline_synced` (`user_id`, `synced_at`),
  CONSTRAINT `fk_offline_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
