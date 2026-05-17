<?php
declare(strict_types=1);

namespace App\Core;

class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    // ─── Token management ────────────────────────────────────────────────────

    public static function generate(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function token(): string
    {
        return static::generate();
    }

    public static function verify(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    // ─── HTML helpers ────────────────────────────────────────────────────────

    /** Returns a hidden <input> field with the CSRF token. */
    public static function field(): string
    {
        $name  = htmlspecialchars(CSRF_TOKEN_NAME, ENT_QUOTES);
        $value = htmlspecialchars(static::token(), ENT_QUOTES);
        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }

    // ─── Request validation ──────────────────────────────────────────────────

    /**
     * Validate CSRF on POST requests.
     * Checks $_POST[CSRF_TOKEN_NAME] first, then HTTP header X-CSRF-Token (for AJAX).
     * Terminates with 403 on failure.
     */
    public static function validateRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        $token = $_POST[CSRF_TOKEN_NAME]
              ?? $_SERVER['HTTP_X_CSRF_TOKEN']
              ?? null;

        if (!static::verify($token)) {
            http_response_code(403);
            $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch.']);
            } else {
                echo '<h1>403 Forbidden</h1><p>CSRF token mismatch. Please go back and try again.</p>';
            }
            exit;
        }
    }
}
