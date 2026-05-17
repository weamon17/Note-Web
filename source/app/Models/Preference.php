<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Preference
{
    private Database $db;

    private const THEMES      = ['light', 'dark'];
    private const VIEWS       = ['grid', 'list'];
    private const FONT_SIZES  = ['small', 'medium', 'large'];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    public function getByUserId(int $userId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM user_preferences WHERE user_id = ?',
            [$userId]
        );
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public function createDefault(int $userId): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO user_preferences (user_id, theme, default_view, font_size, note_color)
             VALUES (?, "light", "grid", "medium", "#ffffff")',
            [$userId]
        );
    }

    /** Save all four preferences at once (used by the Preferences page form). */
    public function updateAll(
        int    $userId,
        string $theme,
        string $defaultView,
        string $fontSize,
        string $noteColor
    ): void {
        $theme       = in_array($theme,       self::THEMES,      true) ? $theme       : 'light';
        $defaultView = in_array($defaultView, self::VIEWS,       true) ? $defaultView : 'grid';
        $fontSize    = in_array($fontSize,    self::FONT_SIZES,  true) ? $fontSize    : 'medium';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $noteColor)) {
            $noteColor = '#ffffff';
        }

        $this->db->execute(
            'INSERT INTO user_preferences (user_id, theme, default_view, font_size, note_color)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 theme        = VALUES(theme),
                 default_view = VALUES(default_view),
                 font_size    = VALUES(font_size),
                 note_color   = VALUES(note_color)',
            [$userId, $theme, $defaultView, $fontSize, $noteColor]
        );
    }

    public function updateTheme(int $userId, string $theme): void
    {
        $theme = in_array($theme, self::THEMES, true) ? $theme : 'light';
        $this->db->execute(
            'UPDATE user_preferences SET theme = ? WHERE user_id = ?',
            [$theme, $userId]
        );
    }

    public function updateView(int $userId, string $view): void
    {
        $view = in_array($view, self::VIEWS, true) ? $view : 'grid';
        $this->db->execute(
            'UPDATE user_preferences SET default_view = ? WHERE user_id = ?',
            [$view, $userId]
        );
    }
}
