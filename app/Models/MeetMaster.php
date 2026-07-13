<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MeetMaster extends Model
{
    protected string $table = 'meet_masters';
    protected bool $campusScoped = true;
    protected array $fillable = ['title', 'start_date', 'end_date', 'location', 'details', 'campus_id', 'status'];

    public function options(): array
    {
        $sql = "SELECT id, title AS name FROM meet_masters";
        $params = [];
        $this->applyCampusScope($sql, $params);
        $sql .= " ORDER BY start_date DESC, title ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /** Verify a meet belongs to the current campus (ownership check). */
    public function ownedOrFail(int $meetId): array
    {
        $meet = $this->find($meetId);
        if (!$meet) {
            throw new \RuntimeException('Meet not found or not in your campus.');
        }
        return $meet;
    }
}
