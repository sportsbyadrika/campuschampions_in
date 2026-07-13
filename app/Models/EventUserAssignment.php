<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class EventUserAssignment
{
    private Database $db;
    public function __construct() { $this->db = Database::instance(); }

    public function isAssigned(int $userId, int $instanceId): bool
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*) FROM event_user_assignments WHERE user_id = ? AND event_instance_id = ?",
            [$userId, $instanceId]
        ) > 0;
    }

    public function forInstance(int $instanceId): array
    {
        return $this->db->fetchAll(
            "SELECT a.id, a.user_id, u.full_name, u.email
             FROM event_user_assignments a
             JOIN users u ON u.id = a.user_id
             WHERE a.event_instance_id = ?
             ORDER BY u.full_name",
            [$instanceId]
        );
    }

    public function assign(int $userId, int $instanceId, int $assignedBy): void
    {
        $exists = (int) $this->db->scalar(
            "SELECT COUNT(*) FROM event_user_assignments WHERE user_id = ? AND event_instance_id = ?",
            [$userId, $instanceId]
        );
        if ($exists === 0) {
            $this->db->query(
                "INSERT INTO event_user_assignments (user_id, event_instance_id, assigned_by) VALUES (?, ?, ?)",
                [$userId, $instanceId, $assignedBy]
            );
        }
    }

    public function unassign(int $id): void
    {
        $this->db->query("DELETE FROM event_user_assignments WHERE id = ?", [$id]);
    }

    /** Instance ids assigned to a user (for filtering their result-entry list). */
    public function instanceIdsForUser(int $userId): array
    {
        $rows = $this->db->fetchAll("SELECT event_instance_id FROM event_user_assignments WHERE user_id = ?", [$userId]);
        return array_map(fn($r) => (int) $r['event_instance_id'], $rows);
    }
}
