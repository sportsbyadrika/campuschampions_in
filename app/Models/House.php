<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class House extends Model
{
    protected string $table = 'houses';
    protected bool $campusScoped = true;
    protected array $fillable = ['name', 'color_code', 'campus_id', 'status'];

    /** id => name options for dropdowns (campus-scoped). */
    public function options(): array
    {
        $sql = "SELECT id, name FROM `houses`";
        $params = [];
        $this->applyCampusScope($sql, $params);
        $sql .= " ORDER BY name ASC";
        return $this->db->fetchAll($sql, $params);
    }
}
