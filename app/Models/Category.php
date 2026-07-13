<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table = 'categories';
    protected bool $campusScoped = false;
    protected array $fillable = ['name', 'description', 'meet_id', 'status'];

    public function forMeet(int $meetId): array
    {
        return $this->db->fetchAll("SELECT * FROM categories WHERE meet_id = ? ORDER BY name ASC", [$meetId]);
    }

    public function optionsForMeet(int $meetId): array
    {
        return $this->db->fetchAll("SELECT id, name FROM categories WHERE meet_id = ? AND status = 'active' ORDER BY name ASC", [$meetId]);
    }
}
