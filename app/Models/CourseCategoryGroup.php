<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class CourseCategoryGroup extends Model
{
    protected string $table = 'course_category_groups';
    protected bool $campusScoped = true;
    protected array $fillable = ['name', 'description', 'campus_id', 'status'];

    /** id => name options for dropdowns (campus-scoped). */
    public function options(): array
    {
        $sql = "SELECT id, name FROM `course_category_groups`";
        $params = [];
        $this->applyCampusScope($sql, $params);
        $sql .= " ORDER BY name ASC";
        return $this->db->fetchAll($sql, $params);
    }
}
