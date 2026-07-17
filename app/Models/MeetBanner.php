<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MeetBanner extends Model
{
    protected string $table = 'meet_banners';
    protected bool $campusScoped = false; // scoped via meet ownership in the controller
    protected array $fillable = ['meet_id', 'image_path', 'sort_order'];

    /** Banners for a meet, in slide order. */
    public function forMeet(int $meetId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM meet_banners WHERE meet_id = ? ORDER BY sort_order ASC, id ASC",
            [$meetId]
        );
    }
}
