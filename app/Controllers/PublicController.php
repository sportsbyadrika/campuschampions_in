<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Standing;

/**
 * Public results portal (no login required), with light caching.
 * Landing shows active meets that have published results; selecting a meet
 * shows its published prize winners in the same layout as the live display.
 */
class PublicController extends Controller
{
    /** Active meets that have at least one published prize result. */
    public function meets(): void
    {
        $db = Database::instance();
        $meets = Cache::remember('public_active_meets', 120, fn() => $db->fetchAll(
            "SELECT m.id, m.title, m.start_date, m.end_date, m.location,
                    m.banner_path, m.logo_path, inst.name AS institution_name
             FROM meet_masters m
             JOIN institutions inst ON inst.id = m.campus_id
             WHERE m.status = 'active'
               AND EXISTS (
                   SELECT 1 FROM event_instances ei
                   JOIN event_masters e ON e.id = ei.event_id
                   JOIN discipline_masters d ON d.id = e.discipline_id
                   JOIN results r ON r.event_instance_id = ei.id
                   WHERE d.meet_id = m.id AND ei.results_published = 1
                     AND r.position IN ('first','second','third')
               )
             ORDER BY m.start_date DESC, m.title"
        ));

        $this->view('public/meets', [
            'title' => 'Active Meets',
            'meets' => $meets,
        ], 'layouts/public');
    }

    /** Published prize winners for a single meet, grouped by event instance. */
    public function meetResults(string $meetId): void
    {
        $meetId = (int) $meetId;
        $db = Database::instance();

        $meet = $db->fetch(
            "SELECT m.id, m.title, m.start_date, m.end_date, m.location, m.status,
                    m.logo_path, m.banner_path, inst.name AS institution_name
             FROM meet_masters m
             JOIN institutions inst ON inst.id = m.campus_id
             WHERE m.id = ? AND m.status = 'active'",
            [$meetId]
        );
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }

        $events = Cache::remember('public_meet_results:' . $meetId, 60, function () use ($meetId) {
            $byInst = [];
            foreach ((new Standing())->eventResults($meetId, true) as $r) {
                $key = (int) $r['instance_id'];
                if (!isset($byInst[$key])) {
                    $byInst[$key] = [
                        'label' => $r['instance_label'],
                        'sub'   => $r['discipline_name'] . ' · ' . $r['event_name'],
                        'first' => [], 'second' => [], 'third' => [],
                    ];
                }
                $cls = trim(($r['course_name'] ?? '') . ' / ' . ($r['division_name'] ?? ''), ' /');
                if (isset($byInst[$key][$r['position']])) {
                    $byInst[$key][$r['position']][] = [
                        'name'   => $r['contestant_name'],
                        'unique' => $r['unique_number'],
                        'house'  => $r['house_name'] ?? '',
                        'cls'    => $cls,
                    ];
                }
            }
            return array_values($byInst);
        });

        $this->view('public/meet_results', [
            'title'  => $meet['title'] . ' — Results',
            'meet'   => $meet,
            'events' => $events,
        ], 'layouts/public');
    }
}
