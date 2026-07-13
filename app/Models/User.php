<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    // Campus-scoped so campus admins only see/manage their own campus users.
    // Super admin (campus_id = null) is unscoped and sees everyone.
    protected bool $campusScoped = true;
    protected array $fillable = [
        'email', 'password', 'full_name', 'role', 'campus_id',
        'status', 'last_login', 'reset_token', 'reset_token_expiry',
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    public function findByResetToken(string $token): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1",
            [$token]
        );
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$id]);
    }

    public function setResetToken(int $id, string $token, string $expiry): void
    {
        $this->db->query(
            "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?",
            [$token, $expiry, $id]
        );
    }

    public function clearResetToken(int $id): void
    {
        $this->db->query(
            "UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE id = ?",
            [$id]
        );
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->db->query("UPDATE users SET password = ? WHERE id = ?", [$hash, $id]);
    }
}
