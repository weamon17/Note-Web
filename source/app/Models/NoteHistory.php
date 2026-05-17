<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class NoteHistory
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Record an action. Edits are throttled to once per 5 minutes per user
     * to avoid flooding the table from autosave.
     */
    public function record(int $noteId, int $userId, string $displayName, ?string $avatar, string $action): void
    {
        if ($action === 'edited') {
            $recent = $this->db->fetchColumn(
                'SELECT id FROM note_history
                 WHERE note_id = ? AND user_id = ? AND action = "edited"
                   AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 LIMIT 1',
                [$noteId, $userId]
            );
            if ($recent !== null) {
                return;
            }
        }

        $this->db->execute(
            'INSERT INTO note_history (note_id, user_id, display_name, avatar, action)
             VALUES (?, ?, ?, ?, ?)',
            [$noteId, $userId, $displayName, $avatar, $action]
        );
    }

    public function getByNoteId(int $noteId, int $limit = 100): array
    {
        return $this->db->fetchAll(
            'SELECT id, note_id, user_id, display_name, avatar, action, created_at
             FROM note_history
             WHERE note_id = ?
             ORDER BY created_at DESC
             LIMIT ?',
            [$noteId, $limit]
        );
    }
}
