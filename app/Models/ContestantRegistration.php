<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ContestantRegistration extends Model
{
    protected string $table = 'contestant_registrations';
    protected bool $campusScoped = false;
    protected array $fillable = ['contestant_id', 'event_instance_id', 'registration_date', 'status'];

    /** Registrations for an event instance, with contestant details. */
    public function forInstance(int $instanceId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, cm.name AS contestant_name, cm.unique_number, h.name AS house_name
             FROM contestant_registrations r
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             LEFT JOIN houses h ON h.id = cm.house_id
             WHERE r.event_instance_id = ?
             ORDER BY cm.name",
            [$instanceId]
        );
    }

    public function isRegistered(int $contestantId, int $instanceId): bool
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*) FROM contestant_registrations WHERE contestant_id = ? AND event_instance_id = ?",
            [$contestantId, $instanceId]
        ) > 0;
    }
}
