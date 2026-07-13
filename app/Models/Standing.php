<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Championship standings aggregated from results for a meet.
 */
class Standing
{
    private Database $db;
    public function __construct() { $this->db = Database::instance(); }

    /** House-wise total points for a meet. */
    public function houses(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT h.id, h.name, h.color_code,
                    COALESCE(SUM(r.points), 0) AS total_points,
                    COUNT(r.id) AS result_count
             FROM houses h
             JOIN contestant_masters cm ON cm.house_id = h.id
             JOIN results r ON r.contestant_id = cm.id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             GROUP BY h.id, h.name, h.color_code
             ORDER BY total_points DESC, h.name ASC",
            [$meetId]
        );
    }

    /** Individual contestant total points for a meet. */
    public function individuals(int $meetId, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            "SELECT cm.id, cm.unique_number, cm.name, h.name AS house_name,
                    COALESCE(SUM(r.points), 0) AS total_points,
                    SUM(CASE WHEN r.position='first' THEN 1 ELSE 0 END) AS golds,
                    SUM(CASE WHEN r.position='second' THEN 1 ELSE 0 END) AS silvers,
                    SUM(CASE WHEN r.position='third' THEN 1 ELSE 0 END) AS bronzes
             FROM contestant_masters cm
             LEFT JOIN houses h ON h.id = cm.house_id
             JOIN results r ON r.contestant_id = cm.id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             GROUP BY cm.id, cm.unique_number, cm.name, h.name
             ORDER BY total_points DESC, golds DESC, cm.name ASC
             LIMIT {$limit}",
            [$meetId]
        );
    }
}
