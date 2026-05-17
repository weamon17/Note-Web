<?php
declare(strict_types=1);

// ─── Load .env (never commit .env to version control) ────────────────────────
(static function () {
    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
})();

// ─── Base paths ──────────────────────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));          // .../source

// ─── Dynamic BASE_URL — no hardcoded localhost ───────────────────────────────
(static function () {
    $https    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $protocol = $https ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // SCRIPT_NAME may point to a router script; normalise to index.php path
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    if (!str_ends_with($scriptName, 'index.php')) {
        $scriptName = '/index.php';
    }
    $scriptDir = rtrim(dirname($scriptName), '/\\');
    $basePath  = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

    define('BASE_URL',  $protocol . '://' . $host . $basePath);
    define('BASE_PATH_WEB', $basePath);         // e.g. '' or '/noteflow/public'
})();

// ─── Application ─────────────────────────────────────────────────────────────
define('APP_NAME',    'WeaNote');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');
define('APP_DEBUG',   APP_ENV === 'development');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'noteflow_sess');
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);  // 7 days

// ─── CSRF ─────────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', '_csrf');

// ─── WebSocket ───────────────────────────────────────────────────────────────
define('WS_PORT', (int) (getenv('WS_PORT') ?: 8080));

// ─── Uploads ─────────────────────────────────────────────────────────────────
define('UPLOAD_PATH',          BASE_PATH . '/public/uploads');
define('UPLOAD_MAX_SIZE',      5 * 1024 * 1024); // 5 MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// ─── Error display ───────────────────────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
