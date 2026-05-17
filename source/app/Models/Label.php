<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Label
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllByUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM labels WHERE user_id = ? ORDER BY name ASC',
            [$userId]
        );
    }

    public function findById(int $id, int $userId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM labels WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function create(int $userId, string $name, string $color = '#6c757d'): int
    {
        $this->db->execute(
            'INSERT INTO labels (user_id, name, color) VALUES (?, ?, ?)',
            [$userId, $name, $color]
        );
        return $this->db->lastInsertId();
    }

    public function update(int $id, int $userId, string $name, string $color): void
    {
        $this->db->execute(
            'UPDATE labels SET name = ?, color = ? WHERE id = ? AND user_id = ?',
            [$name, $color, $id, $userId]
        );
    }

    public function delete(int $id, int $userId): void
    {
        $this->db->execute(
            'DELETE FROM labels WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function nameExists(int $userId, string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            return (bool) $this->db->fetchColumn(
                'SELECT 1 FROM labels WHERE user_id = ? AND name = ? AND id != ?',
                [$userId, $name, $excludeId]
            );
        }
        return (bool) $this->db->fetchColumn(
            'SELECT 1 FROM labels WHERE user_id = ? AND name = ?',
            [$userId, $name]
        );
    }
}
