<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Tracks login attempts for rate limiting (5 attempts / 15-min lockout).
 */
class LoginAttempt
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::instance();
    }

    public function record(string $email, string $ip, bool $successful): void
    {
        $this->db->query(
            "INSERT INTO login_attempts (email, ip_address, successful) VALUES (?, ?, ?)",
            [$email, $ip, $successful ? 1 : 0]
        );
    }

    /** Count failed attempts for an email or IP within the lockout window. */
    public function recentFailures(string $email, string $ip, int $minutes): int
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*) FROM login_attempts
             WHERE successful = 0
               AND (email = ? OR ip_address = ?)
               AND attempted_at > (NOW() - INTERVAL ? MINUTE)",
            [$email, $ip, $minutes]
        );
    }

    /** Clear failed attempts after a successful login. */
    public function clearFailures(string $email, string $ip): void
    {
        $this->db->query(
            "DELETE FROM login_attempts WHERE successful = 0 AND (email = ? OR ip_address = ?)",
            [$email, $ip]
        );
    }
}
