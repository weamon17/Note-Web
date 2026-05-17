<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetch('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findByActivationToken(string $token): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM users WHERE activation_token = ? AND activation_token_expires_at > NOW()',
            [$token]
        );
    }

    public function findByResetToken(string $token): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()',
            [$token]
        );
    }

    public function create(string $email, string $displayName, string $passwordHash, string $activationToken): int
    {
        $this->db->execute(
            'INSERT INTO users (email, display_name, password_hash, activation_token, activation_token_expires_at)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))',
            [$email, $displayName, $passwordHash, $activationToken]
        );
        $id = $this->db->lastInsertId();
        $this->db->execute(
            'INSERT INTO user_preferences (user_id) VALUES (?)',
            [$id]
        );
        return $id;
    }

    public function verify(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET is_verified = 1, activation_token = NULL, activation_token_expires_at = NULL
             WHERE id = ?',
            [$id]
        );
    }

    /**
     * Replace the activation token with a fresh one (new 24-hour expiry).
     * Only updates rows that are still unverified, so calling this on an
     * already-verified account is a safe no-op.
     */
    public function refreshActivationToken(int $id, string $token): void
    {
        $this->db->execute(
            'UPDATE users
             SET    activation_token            = ?,
                    activation_token_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
             WHERE  id = ? AND is_verified = 0',
            [$token, $id]
        );
    }

    public function updateProfile(int $id, string $displayName, ?string $avatar = null): void
    {
        if ($avatar !== null) {
            $this->db->execute(
                'UPDATE users SET display_name = ?, avatar = ? WHERE id = ?',
                [$displayName, $avatar, $id]
            );
        } else {
            $this->db->execute(
                'UPDATE users SET display_name = ? WHERE id = ?',
                [$displayName, $id]
            );
        }
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->db->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public function setResetOtp(int $id, string $otp, string $token): void
    {
        $this->db->execute(
            'UPDATE users
             SET reset_otp = ?, reset_otp_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                 reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
             WHERE id = ?',
            [password_hash($otp, PASSWORD_BCRYPT), $token, $id]
        );
    }

    public function clearResetCredentials(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET reset_otp = NULL, reset_otp_expires_at = NULL,
                             reset_token = NULL, reset_token_expires_at = NULL
             WHERE id = ?',
            [$id]
        );
    }

    public function updatePreferences(int $userId, string $theme, string $defaultView): void
    {
        $this->db->execute(
            'INSERT INTO user_preferences (user_id, theme, default_view)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE theme = VALUES(theme), default_view = VALUES(default_view)',
            [$userId, $theme, $defaultView]
        );
    }

    public function emailExists(string $email): bool
    {
        return (bool) $this->db->fetchColumn('SELECT 1 FROM users WHERE email = ?', [$email]);
    }

    /** Safe lookup — returns only public fields (no password hash). */
    public function findPublicByEmail(string $email): array|false
    {
        return $this->db->fetch(
            'SELECT id, email, display_name FROM users WHERE email = ?',
            [$email]
        );
    }
}
