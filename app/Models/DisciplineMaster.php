<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DisciplineMaster extends Model
{
    protected string $table = 'discipline_masters';
    protected bool $campusScoped = false; // scoped via meet_id ownership in controller
    protected array $fillable = ['name', 'description', 'meet_id', 'status'];

    public function forMeet(int $meetId): array
    {
        return $this->db->fetchAll("SELECT * FROM discipline_masters WHERE meet_id = ? ORDER BY name ASC", [$meetId]);
    }

    public function optionsForMeet(int $meetId): array
    {
        return $this->db->fetchAll("SELECT id, name FROM discipline_masters WHERE meet_id = ? AND status = 'active' ORDER BY name ASC", [$meetId]);
    }
}
