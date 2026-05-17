<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class NoteShare
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Single-share lookups ─────────────────────────────────────────────────

    public function find(int $noteId, int $recipientId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM note_shares WHERE note_id = ? AND shared_with_id = ?',
            [$noteId, $recipientId]
        );
    }

    // ─── Shares for a note (owner's view) ────────────────────────────────────

    /** All recipients for a note, with their user info. */
    public function getByNote(int $noteId): array
    {
        return $this->db->fetchAll(
            'SELECT ns.id, ns.note_id, ns.shared_with_id, ns.permission,
                    ns.created_at AS shared_at,
                    u.email, u.display_name
             FROM note_shares ns
             JOIN users u ON u.id = ns.shared_with_id
             WHERE ns.note_id = ?
             ORDER BY ns.created_at DESC',
            [$noteId]
        );
    }

    /** Share counts keyed by note_id, for a set of note IDs. */
    public function getCountByNoteIds(array $noteIds): array
    {
        if (empty($noteIds)) return [];
        $in   = implode(',', array_fill(0, count($noteIds), '?'));
        $rows = $this->db->fetchAll(
            "SELECT note_id, COUNT(*) AS cnt FROM note_shares WHERE note_id IN ({$in}) GROUP BY note_id",
            $noteIds
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['note_id']] = (int) $row['cnt'];
        }
        return $map;
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /** Insert or update a share record. */
    public function upsert(int $noteId, int $ownerId, int $recipientId, string $permission): void
    {
        $this->db->execute(
            'INSERT INTO note_shares (note_id, owner_id, shared_with_id, permission)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = NOW()',
            [$noteId, $ownerId, $recipientId, $permission]
        );
    }

    /** Revoke a single recipient's access. */
    public function revoke(int $noteId, int $recipientId): void
    {
        $this->db->execute(
            'DELETE FROM note_shares WHERE note_id = ? AND shared_with_id = ?',
            [$noteId, $recipientId]
        );
    }

    // ─── Recipient's view ─────────────────────────────────────────────────────

    /** Notes shared with a user, including owner info and shared_at. */
    public function getSharedWithUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT n.*, ns.permission AS access_permission,
                    ns.created_at     AS shared_at,
                    u.display_name    AS owner_name,
                    u.email           AS owner_email
             FROM note_shares ns
             JOIN notes n ON n.id  = ns.note_id
             JOIN users u ON u.id  = n.user_id
             WHERE ns.shared_with_id = ? AND n.deleted_at IS NULL
             ORDER BY ns.created_at DESC',
            [$userId]
        );
    }
}
