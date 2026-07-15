<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class CertificateTemplate extends Model
{
    protected string $table = 'certificate_templates';
    protected bool $campusScoped = true;
    protected array $fillable = [
        'name', 'body_html', 'orientation',
        'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
        'number_top', 'number_left', 'date_top', 'date_left',
        'number_prefix', 'number_suffix', 'number_next',
        'campus_id', 'is_default', 'status',
    ];

    /** A ready-to-use sample body for the "Certificate of Merit" layout. */
    public static function sampleBody(): string
    {
        return <<<'HTML'
<div style="font-family:'DejaVu Sans',sans-serif; color:#12395e; text-align:center;">
    <p style="font-size:18px; line-height:2;">
        This is to certify that <b>{{contestant_name}}</b> of class <b>{{course}}</b> Division <b>{{division}}</b>
        won <b>{{position}}</b> place in <b>{{event_label}}</b> competition held in connection with the
        School Annual Day Celebrations <b>{{meet_title}}</b>.
    </p>
</div>
HTML;
    }

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
