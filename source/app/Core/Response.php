<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): never
    {
        static::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        static::json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    public static function notFound(string $message = 'Not found'): never
    {
        static::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        static::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): never
    {
        static::error($message, 403);
    }

    public static function unprocessable(array $errors, string $message = 'Validation failed'): never
    {
        static::error($message, 422, $errors);
    }

    public static function redirect(string $path, int $status = 302): never
    {
        $url = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : BASE_URL . '/' . ltrim($path, '/');

        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }
}
