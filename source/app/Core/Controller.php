<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    // ─── View ────────────────────────────────────────────────────────────────

    /**
     * Render a view file.
     * $view uses dot notation: 'notes.index' → app/Views/notes/index.php
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$view} (looked in {$file})");
        }

        require $file;
    }

    // ─── Redirect ────────────────────────────────────────────────────────────

    protected function redirect(string $path, int $status = 302): never
    {
        $url = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : BASE_URL . '/' . ltrim($path, '/');

        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    // ─── JSON response ───────────────────────────────────────────────────────

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    // ─── Request helpers ─────────────────────────────────────────────────────

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /** Read raw JSON request body (for AJAX POST with Content-Type: application/json). */
    protected function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    // ─── Flash messages ──────────────────────────────────────────────────────

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    protected function flashErrors(array $errors): void
    {
        foreach ($errors as $msg) {
            $this->flash('error', $msg);
        }
    }
}
