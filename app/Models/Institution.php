<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Institution extends Model
{
    protected string $table = 'institutions';
    protected bool $campusScoped = false; // managed by super admin across all campuses
    protected array $fillable = [
        'name', 'address', 'contact_email', 'contact_phone',
        'subscription_start_date', 'subscription_end_date', 'status',
    ];

    /** Options for select dropdowns. */
    public function options(): array
    {
        return $this->db->fetchAll("SELECT id, name FROM institutions ORDER BY name ASC");
    }

    /** Refresh derived subscription status from dates. */
    public function refreshStatus(int $id): void
    {
        $this->db->query(
            "UPDATE institutions
             SET status = CASE
                 WHEN subscription_end_date IS NOT NULL AND subscription_end_date < CURDATE() THEN 'expired'
                 ELSE status END
             WHERE id = ?",
            [$id]
        );
    }
}
