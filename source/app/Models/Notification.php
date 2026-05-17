<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Notification
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $type, string $message, ?array $data = null): int
    {
        $this->db->execute(
            'INSERT INTO notifications (user_id, type, message, data) VALUES (?, ?, ?, ?)',
            [$userId, $type, $message, $data !== null ? json_encode($data) : null]
        );
        return $this->db->lastInsertId();
    }

    public function getRecent(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ?
             ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public function markRead(int $id, int $userId): void
    {
        $this->db->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?',
            [$userId]
        );
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
    }
}
