<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class NoteImage
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Find an image record by its own ID. */
    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT ni.*, n.user_id AS note_owner_id
             FROM note_images ni
             JOIN notes n ON n.id = ni.note_id
             WHERE ni.id = ?',
            [$id]
        );
    }

    /** All images for a note, ordered by sort_order (if column exists) then upload time. */
    public function getByNote(int $noteId): array
    {
        // sort_order column is added by migrate_images_position.sql
        // Fall back to created_at order if the column does not yet exist
        try {
            return $this->db->fetchAll(
                'SELECT * FROM note_images WHERE note_id = ? ORDER BY sort_order ASC, created_at ASC',
                [$noteId]
            );
        } catch (\Throwable) {
            return $this->db->fetchAll(
                'SELECT * FROM note_images WHERE note_id = ? ORDER BY created_at ASC',
                [$noteId]
            );
        }
    }

    /** Persist drag position and sort order for an image. */
    public function updatePosition(int $id, float $xPct, float $yPct, int $sortOrder): void
    {
        $this->db->execute(
            'UPDATE note_images SET x_pct = ?, y_pct = ?, sort_order = ? WHERE id = ?',
            [round($xPct, 2), round($yPct, 2), $sortOrder, $id]
        );
    }

    /**
     * Insert a new image record.
     * Returns the new row ID.
     */
    public function add(
        int    $noteId,
        string $filename,
        string $originalName,
        string $mimeType,
        int    $size
    ): int {
        $this->db->execute(
            'INSERT INTO note_images (note_id, filename, original_name, mime_type, size)
             VALUES (?, ?, ?, ?, ?)',
            [$noteId, $filename, $originalName, $mimeType, $size]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Delete an image record and return the row (for disk cleanup).
     * Returns false if the image was not found.
     */
    public function delete(int $id): array|false
    {
        $img = $this->findById($id);
        if (!$img) {
            return false;
        }
        $this->db->execute('DELETE FROM note_images WHERE id = ?', [$id]);
        return $img;
    }

    /** Delete all image records for a note (used when permanently deleting a note). */
    public function deleteByNote(int $noteId): array
    {
        $images = $this->getByNote($noteId);
        if (!empty($images)) {
            $this->db->execute('DELETE FROM note_images WHERE note_id = ?', [$noteId]);
        }
        return $images;
    }

    /** Check that an image belongs to a note owned by the given user. */
    public function isOwnedByUser(int $imageId, int $userId): bool
    {
        return (bool) $this->db->fetchColumn(
            'SELECT 1
             FROM note_images ni
             JOIN notes n ON n.id = ni.note_id
             WHERE ni.id = ? AND n.user_id = ?',
            [$imageId, $userId]
        );
    }
}
