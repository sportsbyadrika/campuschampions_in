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
<div style="font-family:'DejaVu Sans',sans-serif; color:#12395e; text-align:center; line-height:1.7;">
    <p style="font-size:16px; margin:0 0 16px;">This is to certify that</p>
    <p style="font-size:28px; font-weight:bold; color:#0f3a63; margin:0 0 4px; letter-spacing:.5px;">{{contestant_name}}</p>
    <p style="font-size:15px; margin:0 0 16px;">of class <b>{{course}}</b> Division <b>{{division}}</b></p>
    <p style="font-size:16px; margin:0 0 6px;">won <b style="color:#b8860b;">{{position}} place</b> in <b>{{event_label}}</b> competition</p>
    <p style="font-size:15px; margin:0;">held in connection with the School Annual Day Celebrations</p>
    <p style="font-size:17px; font-weight:bold; margin:8px 0 0;">{{meet_title}}</p>
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
