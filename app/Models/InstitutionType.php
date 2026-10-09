<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class InstitutionType extends Model
{
    protected string $table = 'institution_types';
    protected bool $campusScoped = false; // global master
    protected array $fillable = ['name', 'status'];

    /** id => name options for dropdowns (active types). */
    public function options(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name FROM institution_types WHERE status = 'active' ORDER BY name ASC"
        );
    }
}
