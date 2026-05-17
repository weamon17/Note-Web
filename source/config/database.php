<?php
declare(strict_types=1);

$env = static fn(string $k, string $d = ''): string =>
    (string) ($_ENV[$k] ?? (getenv($k) ?: $d));

return [
    'driver'   => 'mysql',
    'host'     => $env('DB_HOST', '127.0.0.1'),
    'port'     => $env('DB_PORT', '3306'),
    'database' => $env('DB_NAME', 'weanote'),
    'username' => $env('DB_USER', 'root'),
    'password' => $env('DB_PASS', ''),
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
    ],
];
