<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Certificate extends Model
{
    protected string $table = 'certificates';
    protected bool $campusScoped = false; // scoped via event instance ownership
    protected array $fillable = ['event_instance_id', 'contestant_id', 'certificate_number', 'template_used', 'issue_date', 'file_path', 'status'];

    public function forInstance(int $instanceId): array
    {
        return $this->db->fetchAll(
            "SELECT ce.*, cm.name AS contestant_name, cm.unique_number
             FROM certificates ce
             JOIN contestant_masters cm ON cm.id = ce.contestant_id
             WHERE ce.event_instance_id = ?
             ORDER BY cm.name",
            [$instanceId]
        );
    }

    public function existsFor(int $instanceId, int $contestantId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM certificates WHERE event_instance_id = ? AND contestant_id = ?",
            [$instanceId, $contestantId]
        );
    }

    /** Generate a unique certificate number. */
    public function nextNumber(): string
    {
        $year = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $candidate = sprintf('CC-%s-%s', $year, strtoupper(bin2hex(random_bytes(4))));
            $exists = (int) $this->db->scalar("SELECT COUNT(*) FROM certificates WHERE certificate_number = ?", [$candidate]);
            if ($exists === 0) {
                return $candidate;
            }
        }
        return 'CC-' . $year . '-' . strtoupper(bin2hex(random_bytes(6)));
    }
}
