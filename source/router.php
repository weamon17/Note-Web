<?php
/**
 * PHP Built-in Server Router
 *
 * Run from the source/ directory:
 *   php -S localhost:8000 -t public router.php
 *
 * This file intercepts every request:
 *  - If the requested path maps to a real file in public/, serve it statically.
 *  - Otherwise, hand off to public/index.php.
 */

declare(strict_types=1);

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($file)) {
    return false; // Serve static file (CSS, JS, images, etc.)
}

// Fix SCRIPT_NAME so config/config.php computes BASE_URL correctly
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';

require __DIR__ . '/public/index.php';
