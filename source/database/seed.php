<?php
/**
 * NoteFlow – Seed Script
 *
 * Run from the source/ directory:
 *   php database/seed.php
 *
 * This script inserts demo users, notes, labels, shares, and notifications.
 * Passwords are hashed at runtime using password_hash() (bcrypt, cost 12).
 *
 * Demo credentials:
 *   owner@example.com   / Password123
 *   editor@example.com  / Password123
 *   viewer@example.com  / Password123
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Load DB config (no full app bootstrap needed)
$dbCfg = require BASE_PATH . '/config/database.php';

$dsn = sprintf(
    '%s:host=%s;port=%s;dbname=%s;charset=%s',
    $dbCfg['driver'],
    $dbCfg['host'],
    $dbCfg['port'],
    $dbCfg['database'],
    $dbCfg['charset']
);

try {
    $pdo = new PDO($dsn, $dbCfg['username'], $dbCfg['password'], $dbCfg['options']);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database.\n";

// ─── Helper ───────────────────────────────────────────────────────────────────
$insert = static function (string $sql, array $params = []) use ($pdo): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $pdo->lastInsertId();
};

// ─── Clear existing seed data (preserve schema) ───────────────────────────────
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['offline_queue','collaboration_events','notifications','note_shares',
          'note_labels','note_images','labels','notes',
          'user_preferences','users'] as $tbl) {
    $pdo->exec("TRUNCATE TABLE `{$tbl}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Tables truncated.\n";

// ─── Users ────────────────────────────────────────────────────────────────────
$now = date('Y-m-d H:i:s');

$hash = static fn(string $pw): string => password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);

$ownerId = $insert(
    'INSERT INTO users (email, display_name, password_hash, is_verified, created_at, updated_at)
     VALUES (?, ?, ?, 1, ?, ?)',
    ['owner@example.com', 'Alice Owner', $hash('Password123'), $now, $now]
);

$editorId = $insert(
    'INSERT INTO users (email, display_name, password_hash, is_verified, created_at, updated_at)
     VALUES (?, ?, ?, 1, ?, ?)',
    ['editor@example.com', 'Bob Editor', $hash('Password123'), $now, $now]
);

$viewerId = $insert(
    'INSERT INTO users (email, display_name, password_hash, is_verified, created_at, updated_at)
     VALUES (?, ?, ?, 0, ?, ?)',   // viewer is NOT verified (tests verification banner)
    ['viewer@example.com', 'Carol Viewer', $hash('Password123'), $now, $now]
);

echo "Users created: IDs {$ownerId}, {$editorId}, {$viewerId}\n";

// ─── User preferences ─────────────────────────────────────────────────────────
foreach ([$ownerId => 'light', $editorId => 'dark', $viewerId => 'light'] as $uid => $theme) {
    $insert(
        'INSERT INTO user_preferences (user_id, theme, default_view) VALUES (?, ?, "grid")',
        [$uid, $theme]
    );
}

// ─── Labels ───────────────────────────────────────────────────────────────────
$labelWork = $insert(
    'INSERT INTO labels (user_id, name, color) VALUES (?, "Work", "#4f46e5")',
    [$ownerId]
);
$labelPersonal = $insert(
    'INSERT INTO labels (user_id, name, color) VALUES (?, "Personal", "#10b981")',
    [$ownerId]
);
$labelIdeas = $insert(
    'INSERT INTO labels (user_id, name, color) VALUES (?, "Ideas", "#f59e0b")',
    [$ownerId]
);

echo "Labels created.\n";

// ─── Notes ────────────────────────────────────────────────────────────────────

// 1. Regular note (owner)
$note1 = $insert(
    'INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
    [
        $ownerId,
        'Welcome to NoteFlow',
        "NoteFlow is your personal note management workspace.\n\n"
        . "Key features:\n"
        . "- Create and edit notes with auto-save\n"
        . "- Organise with labels\n"
        . "- Pin important notes\n"
        . "- Lock notes with a password\n"
        . "- Share notes with other users\n"
        . "- Real-time collaboration\n"
        . "- Works offline (PWA)\n\n"
        . "Start by creating your first note!",
        $now, $now,
    ]
);

// 2. Pinned note (owner)
$note2 = $insert(
    'INSERT INTO notes (user_id, title, content, is_pinned, pinned_at, created_at, updated_at)
     VALUES (?, ?, ?, 1, ?, ?, ?)',
    [
        $ownerId,
        'Meeting Notes – Q2 Planning',
        "Attendees: Alice, Bob, Carol\n\n"
        . "Agenda:\n"
        . "1. Review Q1 results\n"
        . "2. Set Q2 OKRs\n"
        . "3. Resource allocation\n\n"
        . "Action items:\n"
        . "- Alice: prepare dashboard by Friday\n"
        . "- Bob: update the roadmap doc\n"
        . "- Carol: schedule follow-up call",
        $now, $now, $now,
    ]
);

// 3. Locked note (owner)
$note3 = $insert(
    'INSERT INTO notes (user_id, title, content, is_locked, note_password_hash, created_at, updated_at)
     VALUES (?, ?, ?, 1, ?, ?, ?)',
    [
        $ownerId,
        'Private Thoughts',
        'This note is protected by a password. Note password: Secret123',
        $hash('Secret123'),
        $now, $now,
    ]
);

// 4. Note shared with editor (edit permission)
$note4 = $insert(
    'INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
    [
        $ownerId,
        'Project Specification',
        "## NoteFlow Specification\n\n"
        . "### Overview\n"
        . "NoteFlow is a collaborative note management application built with PHP 8 MVC.\n\n"
        . "### Tech Stack\n"
        . "- Backend: Native PHP 8 (no framework)\n"
        . "- Database: MySQL / MariaDB\n"
        . "- Frontend: Bootstrap 5, Vanilla JS\n"
        . "- WebSocket: Ratchet (real-time collaboration)\n"
        . "- Email: PHPMailer\n"
        . "- PWA: Service Worker + IndexedDB\n\n"
        . "### Collaboration\n"
        . "This note is shared with Bob Editor (edit permission). Changes sync in real-time.",
        $now, $now,
    ]
);

// 5. Note shared with viewer (read permission)
$note5 = $insert(
    'INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
    [
        $ownerId,
        'Team Announcement',
        "Hi team,\n\n"
        . "Just a quick heads-up: the deployment window is this Saturday 02:00–04:00 UTC.\n\n"
        . "Please ensure all pending PRs are merged by Friday EOD.\n\n"
        . "Cheers,\nAlice",
        $now, $now,
    ]
);

// 6. Note in trash (soft-deleted)
$note6 = $insert(
    'INSERT INTO notes (user_id, title, content, deleted_at, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?)',
    [
        $ownerId,
        'Draft – delete me',
        'This note was moved to trash.',
        $now, $now, $now,
    ]
);

// 7. Editor's own note
$note7 = $insert(
    'INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
    [
        $editorId,
        'My Personal Note',
        "Reminder to self: review the NoteFlow codebase tonight.\n\nAlso pick up groceries.",
        $now, $now,
    ]
);

echo "Notes created: IDs {$note1}, {$note2}, {$note3}, {$note4}, {$note5}, {$note6}, {$note7}\n";

// ─── Note labels ──────────────────────────────────────────────────────────────
$pdo->prepare('INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)')
    ->execute([$note1, $labelPersonal]);
$pdo->prepare('INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)')
    ->execute([$note2, $labelWork]);
$pdo->prepare('INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)')
    ->execute([$note4, $labelWork]);
$pdo->prepare('INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)')
    ->execute([$note4, $labelIdeas]);

// ─── Note shares ──────────────────────────────────────────────────────────────

// note4 shared with editor (edit)
$insert(
    'INSERT INTO note_shares (note_id, owner_id, shared_with_id, permission, created_at, updated_at)
     VALUES (?, ?, ?, "edit", ?, ?)',
    [$note4, $ownerId, $editorId, $now, $now]
);

// note5 shared with viewer (read) and editor (read)
$insert(
    'INSERT INTO note_shares (note_id, owner_id, shared_with_id, permission, created_at, updated_at)
     VALUES (?, ?, ?, "read", ?, ?)',
    [$note5, $ownerId, $viewerId, $now, $now]
);
$insert(
    'INSERT INTO note_shares (note_id, owner_id, shared_with_id, permission, created_at, updated_at)
     VALUES (?, ?, ?, "read", ?, ?)',
    [$note5, $ownerId, $editorId, $now, $now]
);

echo "Shares created.\n";

// ─── Notifications ────────────────────────────────────────────────────────────
$insert(
    'INSERT INTO notifications (user_id, type, message, is_read, data, created_at)
     VALUES (?, "share", ?, 0, ?, ?)',
    [
        $editorId,
        'Alice Owner shared "Project Specification" with you (edit permission).',
        json_encode(['note_id' => $note4, 'permission' => 'edit']),
        $now,
    ]
);
$insert(
    'INSERT INTO notifications (user_id, type, message, is_read, data, created_at)
     VALUES (?, "share", ?, 0, ?, ?)',
    [
        $viewerId,
        'Alice Owner shared "Team Announcement" with you (read permission).',
        json_encode(['note_id' => $note5, 'permission' => 'read']),
        $now,
    ]
);
$insert(
    'INSERT INTO notifications (user_id, type, message, is_read, data, created_at)
     VALUES (?, "share", ?, 0, ?, ?)',
    [
        $editorId,
        'Alice Owner shared "Team Announcement" with you (read permission).',
        json_encode(['note_id' => $note5, 'permission' => 'read']),
        $now,
    ]
);

echo "Notifications created.\n";

echo "\n✓ Seed complete!\n";
echo "─────────────────────────────────\n";
echo "Demo credentials (password: Password123):\n";
echo "  owner@example.com   — Alice Owner  (verified)\n";
echo "  editor@example.com  — Bob Editor   (verified)\n";
echo "  viewer@example.com  — Carol Viewer (NOT verified, tests banner)\n";
echo "─────────────────────────────────\n";
echo "Note passwords:\n";
echo "  'Private Thoughts' note password: Secret123\n";
