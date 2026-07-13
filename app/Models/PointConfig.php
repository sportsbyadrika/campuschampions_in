<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class PointConfig
{
    private Database $db;
    public function __construct() { $this->db = Database::instance(); }

    private const DEFAULTS = ['first' => 5, 'second' => 3, 'third' => 1, 'participant' => 0];

    /** Return position => points for a meet, filling defaults where unset. */
    public function forMeet(int $meetId): array
    {
        $rows = $this->db->fetchAll("SELECT position, points FROM point_configs WHERE meet_id = ?", [$meetId]);
        $map = self::DEFAULTS;
        foreach ($rows as $r) {
            $map[$r['position']] = (float) $r['points'];
        }
        return $map;
    }

    public function save(int $meetId, array $positionPoints): void
    {
        foreach ($positionPoints as $position => $points) {
            if (!array_key_exists($position, self::DEFAULTS)) {
                continue;
            }
            // Portable upsert (avoids MySQL-only ON DUPLICATE KEY syntax)
            $exists = (int) $this->db->scalar(
                "SELECT COUNT(*) FROM point_configs WHERE meet_id = ? AND position = ?",
                [$meetId, $position]
            );
            if ($exists > 0) {
                $this->db->query(
                    "UPDATE point_configs SET points = ? WHERE meet_id = ? AND position = ?",
                    [(float) $points, $meetId, $position]
                );
            } else {
                $this->db->query(
                    "INSERT INTO point_configs (meet_id, position, points) VALUES (?, ?, ?)",
                    [$meetId, $position, (float) $points]
                );
            }
        }
    }

    public function pointsFor(int $meetId, string $position): float
    {
        return $this->forMeet($meetId)[$position] ?? 0.0;
    }
}
