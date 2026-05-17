<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Note
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Ownership / access ───────────────────────────────────────────────────

    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM notes WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
    }

    /** Find note by ID including trashed. */
    public function findByIdWithTrashed(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM notes WHERE id = ?', [$id]);
    }

    /** Returns note only if user owns it OR has a share record. */
    public function findAccessible(int $noteId, int $userId): array|false
    {
        return $this->db->fetch(
            'SELECT n.*,
                    COALESCE(ns.permission, "owner") AS access_permission,
                    (n.user_id = ?) AS is_owner
             FROM notes n
             LEFT JOIN note_shares ns ON ns.note_id = n.id AND ns.shared_with_id = ?
             WHERE n.id = ?
               AND n.deleted_at IS NULL
               AND (n.user_id = ? OR ns.id IS NOT NULL)',
            [$userId, $userId, $noteId, $userId]
        );
    }

    // ─── Lists ────────────────────────────────────────────────────────────────

    public function getAllByUser(int $userId, ?string $filter = null): array
    {
        $base = 'SELECT n.*
                 FROM notes n
                 WHERE n.user_id = ? AND n.deleted_at IS NULL';

        if ($filter === 'pinned') {
            $base .= ' AND n.is_pinned = 1';
        }

        // sort_order column is added by migrate_notes_sort_order.sql
        // Fall back gracefully if the column does not yet exist
        try {
            return $this->db->fetchAll(
                $base . ' ORDER BY n.is_pinned DESC, n.pinned_at DESC, n.sort_order ASC, n.updated_at DESC',
                [$userId]
            );
        } catch (\Throwable) {
            return $this->db->fetchAll(
                $base . ' ORDER BY n.is_pinned DESC, n.pinned_at DESC, n.updated_at DESC',
                [$userId]
            );
        }
    }

    /**
     * Persist drag-reorder positions for a user's notes.
     * Assigns sort_order = 1..N based on the given $ids slice.
     */
    public function reorder(int $userId, array $ids): void
    {
        foreach ($ids as $i => $id) {
            $this->db->execute(
                'UPDATE notes SET sort_order = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL',
                [$i + 1, (int) $id, $userId]
            );
        }
    }

    public function getSharedWithUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT n.*, ns.permission AS access_permission,
                    u.display_name AS owner_name
             FROM note_shares ns
             JOIN notes n  ON n.id = ns.note_id
             JOIN users u  ON u.id = n.user_id
             WHERE ns.shared_with_id = ? AND n.deleted_at IS NULL
             ORDER BY n.updated_at DESC',
            [$userId]
        );
    }

    public function getTrashed(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM notes WHERE user_id = ? AND deleted_at IS NOT NULL
             ORDER BY deleted_at DESC',
            [$userId]
        );
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    public function search(int $userId, string $query): array
    {
        $like = '%' . $query . '%';
        return $this->db->fetchAll(
            'SELECT n.*,
                    CASE WHEN n.user_id = ? THEN "owner" ELSE ns.permission END AS access_permission,
                    GROUP_CONCAT(DISTINCT nl.label_id ORDER BY nl.label_id SEPARATOR ",") AS label_ids_csv
             FROM notes n
             LEFT JOIN note_shares ns ON ns.note_id = n.id AND ns.shared_with_id = ?
             LEFT JOIN note_labels nl ON nl.note_id = n.id
             WHERE n.deleted_at IS NULL
               AND (n.user_id = ? OR ns.id IS NOT NULL)
               AND (n.is_locked = 0)
               AND (n.title LIKE ? OR n.content LIKE ?)
             GROUP BY n.id
             ORDER BY n.is_pinned DESC, n.pinned_at DESC, n.updated_at DESC
             LIMIT 50',
            [$userId, $userId, $userId, $like, $like]
        );
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function create(int $userId, string $title, string $content): int
    {
        $this->db->execute(
            'INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)',
            [$userId, $title, $content]
        );
        return $this->db->lastInsertId();
    }

    public function update(int $id, string $title, string $content): void
    {
        $this->db->execute(
            'UPDATE notes SET title = ?, content = ? WHERE id = ?',
            [$title, $content, $id]
        );
    }

    public function softDelete(int $id): void
    {
        $this->db->execute(
            'UPDATE notes SET deleted_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    public function restore(int $id): void
    {
        $this->db->execute(
            'UPDATE notes SET deleted_at = NULL WHERE id = ?',
            [$id]
        );
    }

    public function permanentDelete(int $id): void
    {
        $this->db->execute('DELETE FROM notes WHERE id = ?', [$id]);
    }

    // ─── Pin / Lock ────────────────────────────────────────────────────────────

    public function togglePin(int $id): bool
    {
        $note = $this->findById($id);
        if (!$note) return false;
        $pinned = !$note['is_pinned'];
        $this->db->execute(
            'UPDATE notes SET is_pinned = ?, pinned_at = ? WHERE id = ?',
            [$pinned ? 1 : 0, $pinned ? date('Y-m-d H:i:s') : null, $id]
        );
        return $pinned;
    }

    public function setLock(int $id, bool $locked, ?string $passwordHash = null): void
    {
        $this->db->execute(
            'UPDATE notes SET is_locked = ?, note_password_hash = ? WHERE id = ?',
            [$locked ? 1 : 0, $locked ? $passwordHash : null, $id]
        );
    }

    // ─── Shares ───────────────────────────────────────────────────────────────

    public function getShares(int $noteId): array
    {
        return $this->db->fetchAll(
            'SELECT ns.*, u.email, u.display_name
             FROM note_shares ns
             JOIN users u ON u.id = ns.shared_with_id
             WHERE ns.note_id = ?',
            [$noteId]
        );
    }

    public function share(int $noteId, int $ownerId, int $recipientId, string $permission): void
    {
        $this->db->execute(
            'INSERT INTO note_shares (note_id, owner_id, shared_with_id, permission)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE permission = VALUES(permission)',
            [$noteId, $ownerId, $recipientId, $permission]
        );
    }

    public function unshare(int $noteId, int $recipientId): void
    {
        $this->db->execute(
            'DELETE FROM note_shares WHERE note_id = ? AND shared_with_id = ?',
            [$noteId, $recipientId]
        );
    }

    // ─── Labels ────────────────────────────────────────────────────────────────

    public function getLabels(int $noteId): array
    {
        return $this->db->fetchAll(
            'SELECT l.* FROM labels l JOIN note_labels nl ON nl.label_id = l.id WHERE nl.note_id = ?',
            [$noteId]
        );
    }

    public function attachLabel(int $noteId, int $labelId): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO note_labels (note_id, label_id) VALUES (?, ?)',
            [$noteId, $labelId]
        );
    }

    public function detachLabel(int $noteId, int $labelId): void
    {
        $this->db->execute(
            'DELETE FROM note_labels WHERE note_id = ? AND label_id = ?',
            [$noteId, $labelId]
        );
    }

    // ─── Images ────────────────────────────────────────────────────────────────

    public function addImage(int $noteId, string $filename, string $originalName, string $mimeType, int $size): int
    {
        $this->db->execute(
            'INSERT INTO note_images (note_id, filename, original_name, mime_type, size)
             VALUES (?, ?, ?, ?, ?)',
            [$noteId, $filename, $originalName, $mimeType, $size]
        );
        return $this->db->lastInsertId();
    }

    public function getImages(int $noteId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM note_images WHERE note_id = ? ORDER BY created_at ASC',
            [$noteId]
        );
    }

    public function deleteImage(int $imageId): array|false
    {
        $img = $this->db->fetch('SELECT * FROM note_images WHERE id = ?', [$imageId]);
        if ($img) {
            $this->db->execute('DELETE FROM note_images WHERE id = ?', [$imageId]);
        }
        return $img;
    }

    // ─── Label bulk fetch ──────────────────────────────────────────────────────

    /** Returns [noteId => [[id,name,color], ...]] for the given note IDs. */
    public function getLabelsByNoteIds(array $noteIds): array
    {
        if (empty($noteIds)) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($noteIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT nl.note_id, l.id, l.name, l.color
             FROM note_labels nl
             JOIN labels l ON l.id = nl.label_id
             WHERE nl.note_id IN ({$in})
             ORDER BY l.name",
            $noteIds
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['note_id']][] = [
                'id'    => $row['id'],
                'name'  => $row['name'],
                'color' => $row['color'],
            ];
        }
        return $map;
    }

    /** Get notes for a specific label owned by the user. */
    public function getByLabel(int $userId, int $labelId): array
    {
        return $this->db->fetchAll(
            'SELECT n.* FROM notes n
             JOIN note_labels nl ON nl.note_id = n.id
             WHERE n.user_id = ? AND nl.label_id = ? AND n.deleted_at IS NULL
             ORDER BY n.is_pinned DESC, n.pinned_at DESC, n.updated_at DESC',
            [$userId, $labelId]
        );
    }

    // ─── Image helpers ─────────────────────────────────────────────────────────

    public function findImageById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM note_images WHERE id = ?', [$id]);
    }

    public function removeImage(int $id): void
    {
        $this->db->execute('DELETE FROM note_images WHERE id = ?', [$id]);
    }

    public function isOwner(int $noteId, int $userId): bool
    {
        return (bool) $this->db->fetchColumn(
            'SELECT 1 FROM notes WHERE id = ? AND user_id = ?',
            [$noteId, $userId]
        );
    }

    // ─── Authorization helpers ─────────────────────────────────────────────────

    /** User owns the note OR has a share record (read or edit). */
    public function canView(int $noteId, int $userId): bool
    {
        return $this->findAccessible($noteId, $userId) !== false;
    }

    /** User owns or has edit-permission share. */
    public function canEdit(int $noteId, int $userId): bool
    {
        $note = $this->findAccessible($noteId, $userId);
        return $note !== false && $note['access_permission'] !== 'read';
    }

    /** Only the owner may manage sharing settings. */
    public function canManageShare(int $noteId, int $userId): bool
    {
        return $this->isOwner($noteId, $userId);
    }

    public function getEditPermission(int $noteId, int $userId): bool
    {
        return (bool) $this->db->fetchColumn(
            'SELECT 1 FROM note_shares WHERE note_id = ? AND shared_with_id = ? AND permission = "edit"',
            [$noteId, $userId]
        );
    }
}
