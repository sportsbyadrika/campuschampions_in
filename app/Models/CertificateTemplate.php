<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class CertificateTemplate extends Model
{
    protected string $table = 'certificate_templates';
    protected bool $campusScoped = true;
    protected array $fillable = ['name', 'body_html', 'campus_id', 'is_default', 'status'];

    /** Templates usable by the current user: their campus templates + global ones. */
    public function usable(): array
    {
        $campusId = Auth::campusId();
        if ($campusId === null) {
            return $this->db->fetchAll("SELECT id, name, is_default FROM certificate_templates WHERE status = 'active' ORDER BY name");
        }
        return $this->db->fetchAll(
            "SELECT id, name, is_default FROM certificate_templates
             WHERE status = 'active' AND (campus_id = ? OR campus_id IS NULL)
             ORDER BY is_default DESC, name",
            [$campusId]
        );
    }

    /** A usable template by id (ownership-aware). */
    public function usableById(int $id): ?array
    {
        $campusId = Auth::campusId();
        if ($campusId === null) {
            return $this->db->fetch("SELECT * FROM certificate_templates WHERE id = ?", [$id]);
        }
        return $this->db->fetch(
            "SELECT * FROM certificate_templates WHERE id = ? AND (campus_id = ? OR campus_id IS NULL)",
            [$id, $campusId]
        );
    }
}
