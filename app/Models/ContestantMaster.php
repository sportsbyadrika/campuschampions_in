<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ContestantMaster extends Model
{
    protected string $table = 'contestant_masters';
    protected bool $campusScoped = true;
    protected array $fillable = [
        'unique_number', 'name', 'dob', 'gender', 'photo_path',
        'course_id', 'division_id', 'house_id', 'course_category_group_id',
        'mobile', 'email', 'guardian_name', 'campus_id', 'status',
    ];

    public function findByUniqueNumber(string $number): ?array
    {
        return $this->findBy('unique_number', $number);
    }
}
