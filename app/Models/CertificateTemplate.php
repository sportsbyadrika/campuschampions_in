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
    <p style="font-size:16px; margin:0 0 14px;">This is to certify that</p>
    <p style="font-size:30px; font-weight:bold; color:#0f3a63; margin:0 0 6px; letter-spacing:.5px;">{{contestant_name}}</p>
    <p style="font-size:14px; color:#5b6b7d; margin:0 0 18px;">{{course}} / {{division}}</p>
    <p style="font-size:16px; margin:0 0 8px;">has been awarded</p>
    <p style="font-size:24px; font-weight:bold; color:#b8860b; margin:0 0 8px;">{{position}} Place</p>
    <p style="font-size:16px; margin:0 0 4px;">in <b>{{event_name}}</b></p>
    <p style="font-size:14px; color:#5b6b7d; margin:0 0 16px;">({{event_label}})</p>
    <p style="font-size:15px; margin:0;">at <b>{{meet_title}}</b></p>
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
