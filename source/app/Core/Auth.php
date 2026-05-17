<?php
declare(strict_types=1);

namespace App\Core;

class Auth
{
    private static array|null|false $cached = false; // false = not yet loaded

    // ─── Check ───────────────────────────────────────────────────────────────

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function guest(): bool
    {
        return !static::check();
    }

    // ─── Current user ────────────────────────────────────────────────────────

    public static function user(): ?array
    {
        if (!static::check()) {
            return null;
        }

        if (static::$cached !== false) {
            return static::$cached ?: null;
        }

        $db = Database::getInstance();
        $row = $db->fetch(
            'SELECT u.id, u.email, u.display_name, u.avatar, u.is_verified, u.created_at,
                    COALESCE(p.theme, "light")    AS theme,
                    COALESCE(p.default_view, "grid") AS default_view,
                    COALESCE(p.font_size, "medium")  AS font_size,
                    COALESCE(p.note_color, "#ffffff") AS note_color
             FROM users u
             LEFT JOIN user_preferences p ON p.user_id = u.id
             WHERE u.id = ?',
            [$_SESSION['user_id']]
        );

        static::$cached = $row ?: null;
        return static::$cached;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function isVerified(): bool
    {
        $u = static::user();
        return $u !== null && (bool) $u['is_verified'];
    }

    // ─── Login / logout ──────────────────────────────────────────────────────

    public static function login(int|array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = is_array($user) ? (int) $user['id'] : $user;
        static::$cached = false; // bust cache
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();
        static::$cached = false;
    }

    // ─── Guards ──────────────────────────────────────────────────────────────

    /** Redirect to login if not authenticated. */
    public static function requireLogin(): void
    {
        if (!static::check()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . BASE_URL . '/login?redirect=' . $redirect);
            exit;
        }
    }

    /** Return 401 JSON if not authenticated (for API routes). */
    public static function requireLoginApi(): void
    {
        if (!static::check()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }
    }

    /** Flush cached user (call after profile update). */
    public static function refreshUser(): void
    {
        static::$cached = false;
    }
}
