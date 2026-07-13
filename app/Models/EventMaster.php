<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class EventMaster extends Model
{
    protected string $table = 'event_masters';
    protected bool $campusScoped = false;
    protected array $fillable = ['name', 'discipline_id', 'event_type', 'status'];

    /** Events for a meet, joined with discipline name. */
    public function forMeet(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT e.*, d.name AS discipline_name
             FROM event_masters e
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             ORDER BY d.name, e.name",
            [$meetId]
        );
    }
}
