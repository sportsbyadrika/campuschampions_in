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

    /** House-wise total points + gold/silver/bronze counts for a meet. */
    public function houses(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT h.id, h.name, h.color_code,
                    COALESCE(SUM(r.points), 0) AS total_points,
                    COUNT(r.id) AS result_count,
                    SUM(CASE WHEN r.position='first'  THEN 1 ELSE 0 END) AS golds,
                    SUM(CASE WHEN r.position='second' THEN 1 ELSE 0 END) AS silvers,
                    SUM(CASE WHEN r.position='third'  THEN 1 ELSE 0 END) AS bronzes
             FROM houses h
             JOIN contestant_masters cm ON cm.house_id = h.id
             JOIN results r ON r.contestant_id = cm.id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             GROUP BY h.id, h.name, h.color_code
             ORDER BY total_points DESC, golds DESC, h.name ASC",
            [$meetId]
        );
    }

    /**
     * Prize winners (1st/2nd/3rd) for every event instance in a meet,
     * with each contestant's house and course/division. Ordered by instance
     * then position. Group by instance_id in the controller.
     */
    public function eventResults(int $meetId, bool $publishedOnly = false): array
    {
        $publishSql = $publishedOnly ? ' AND ei.results_published = 1' : '';
        return $this->db->fetchAll(
            "SELECT ei.id AS instance_id, ei.label AS instance_label,
                    d.name AS discipline_name, e.name AS event_name, c.name AS category_name,
                    r.position, r.points,
                    cm.unique_number, cm.name AS contestant_name,
                    h.name AS house_name, co.name AS course_name, dv.name AS division_name
             FROM results r
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN categories c ON c.id = ei.category_id
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             LEFT JOIN houses h ON h.id = cm.house_id
             LEFT JOIN courses co ON co.id = cm.course_id
             LEFT JOIN divisions dv ON dv.id = cm.division_id
             WHERE d.meet_id = ? AND r.position IN ('first','second','third')" . $publishSql . "
             ORDER BY d.name, ei.label,
                      CASE r.position WHEN 'first' THEN 1 WHEN 'second' THEN 2 WHEN 'third' THEN 3 ELSE 4 END,
                      cm.name",
            [$meetId]
        );
    }

    /** Course/Division-wise medal counts + total points, ordered by points. */
    public function courseDivisions(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT co.name AS course_name, dv.name AS division_name,
                    SUM(CASE WHEN r.position='first'  THEN 1 ELSE 0 END) AS golds,
                    SUM(CASE WHEN r.position='second' THEN 1 ELSE 0 END) AS silvers,
                    SUM(CASE WHEN r.position='third'  THEN 1 ELSE 0 END) AS bronzes,
                    COALESCE(SUM(r.points), 0) AS total_points
             FROM results r
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             LEFT JOIN courses co ON co.id = cm.course_id
             LEFT JOIN divisions dv ON dv.id = cm.division_id
             WHERE d.meet_id = ?
             GROUP BY cm.course_id, cm.division_id
             ORDER BY total_points DESC, golds DESC, co.name ASC",
            [$meetId]
        );
    }

    /**
     * House × Course-Category-Group total points (raw rows for pivoting).
     * One row per (house, category group) that has at least one scored result.
     * Columns are the contestant's course category group (not the event
     * instance's category). Aliased as category_* so the pivot builder is shared.
     */
    public function houseCategoryGroupPoints(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT h.id AS house_id, h.name AS house_name, h.color_code,
                    g.id AS category_id, g.name AS category_name,
                    COALESCE(SUM(r.points), 0) AS points
             FROM results r
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             JOIN houses h ON h.id = cm.house_id
             JOIN course_category_groups g ON g.id = cm.course_category_group_id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             GROUP BY h.id, h.name, h.color_code, g.id, g.name
             ORDER BY g.name ASC, h.name ASC",
            [$meetId]
        );
    }

    /** Discipline-wise medal counts + total points, ordered by points. */
    public function disciplines(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT d.id, d.name AS discipline_name,
                    SUM(CASE WHEN r.position='first'  THEN 1 ELSE 0 END) AS golds,
                    SUM(CASE WHEN r.position='second' THEN 1 ELSE 0 END) AS silvers,
                    SUM(CASE WHEN r.position='third'  THEN 1 ELSE 0 END) AS bronzes,
                    COALESCE(SUM(r.points), 0) AS total_points
             FROM results r
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             GROUP BY d.id, d.name
             ORDER BY total_points DESC, golds DESC, d.name ASC",
            [$meetId]
        );
    }
}
