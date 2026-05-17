<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $cfg = require BASE_PATH . '/config/database.php';
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['driver'],
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw $e;
            }
            http_response_code(503);
            die('Database connection failed. Please try again later.');
        }
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ─── Query helpers ───────────────────────────────────────────────────────

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch all rows as associative arrays. */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Fetch a single row. Returns false if not found. */
    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /** Fetch a single column value. Returns null if not found. */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $result = $this->query($sql, $params)->fetchColumn();
        return $result === false ? null : $result;
    }

    /** Execute a write query (INSERT/UPDATE/DELETE). Returns affected row count. */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    // ─── Transaction helpers ─────────────────────────────────────────────────

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /** Run a callable inside a transaction; rolls back automatically on exception. */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // Prevent cloning / unserialization of the singleton
    private function __clone() {}
    public function __wakeup(): never
    {
        throw new RuntimeException('Cannot unserialize a singleton.');
    }
}
