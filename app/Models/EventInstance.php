<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class EventInstance extends Model
{
    protected string $table = 'event_instances';
    protected bool $campusScoped = false;
    protected array $fillable = ['event_id', 'category_id', 'label', 'instance_date', 'instance_time', 'venue', 'status', 'results_published'];

    /** Instances for a meet with resolved event/discipline/category names. */
    public function forMeet(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT ei.*, e.name AS event_name, d.name AS discipline_name, c.name AS category_name,
                    (SELECT COUNT(*) FROM contestant_registrations r WHERE r.event_instance_id = ei.id) AS reg_count
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN categories c ON c.id = ei.category_id
             WHERE d.meet_id = ?
             ORDER BY ei.instance_date, ei.instance_time, e.name",
            [$meetId]
        );
    }

    /** Full detail incl. meet + campus for a single instance (ownership checks). */
    public function detail(int $instanceId): ?array
    {
        return $this->db->fetch(
            "SELECT ei.*, e.name AS event_name, e.event_type, d.name AS discipline_name,
                    d.meet_id, c.name AS category_name, m.title AS meet_title, m.campus_id
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN categories c ON c.id = ei.category_id
             JOIN meet_masters m ON m.id = d.meet_id
             WHERE ei.id = ?",
            [$instanceId]
        );
    }
}
