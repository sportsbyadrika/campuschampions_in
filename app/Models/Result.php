<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Result extends Model
{
    protected string $table = 'results';
    protected bool $campusScoped = false; // scoped via event instance ownership
    protected array $fillable = ['event_instance_id', 'contestant_id', 'position', 'points', 'remarks', 'entered_by'];

    public function forInstance(int $instanceId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT contestant_id, position, points, remarks FROM results WHERE event_instance_id = ?",
            [$instanceId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['contestant_id']] = $r;
        }
        return $map;
    }

    /** Portable upsert of a single result row. */
    public function upsert(int $instanceId, int $contestantId, string $position, float $points, ?string $remarks, int $enteredBy): void
    {
        $exists = (int) $this->db->scalar(
            "SELECT COUNT(*) FROM results WHERE event_instance_id = ? AND contestant_id = ?",
            [$instanceId, $contestantId]
        );
        if ($exists > 0) {
            $this->db->query(
                "UPDATE results SET position = ?, points = ?, remarks = ?, entered_by = ? WHERE event_instance_id = ? AND contestant_id = ?",
                [$position, $points, $remarks, $enteredBy, $instanceId, $contestantId]
            );
        } else {
            $this->db->query(
                "INSERT INTO results (event_instance_id, contestant_id, position, points, remarks, entered_by) VALUES (?, ?, ?, ?, ?, ?)",
                [$instanceId, $contestantId, $position, $points, $remarks, $enteredBy]
            );
        }
    }

    public function deleteForContestant(int $instanceId, int $contestantId): void
    {
        $this->db->query("DELETE FROM results WHERE event_instance_id = ? AND contestant_id = ?", [$instanceId, $contestantId]);
    }
}
