<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csv;
use App\Core\Request;
use App\Models\MeetMaster;
use App\Models\Standing;

class StandingsController extends Controller
{
    private const ROLES = ['super_admin', 'campus_admin', 'event_user', 'campus_staff'];

    private function posLabel(string $p): string
    {
        return ['first' => '1st', 'second' => '2nd', 'third' => '3rd'][$p] ?? $p;
    }

    public function index(): void
    {
        $this->authorize(...self::ROLES);

        $meets = (new MeetMaster())->options();
        $meetId = (int) Request::get('meet_id', 0);

        $houses = [];
        $events = [];
        $disciplines = [];
        $courseDivisions = [];
        $meet = null;

        if ($meetId > 0) {
            $meet = (new MeetMaster())->find($meetId); // campus-scoped
            if (!$meet) {
                $this->abort(404, 'Meet not found.');
            }
            $standing = new Standing();
            $houses = $standing->houses($meetId);
            $disciplines = $standing->disciplines($meetId);
            $courseDivisions = $standing->courseDivisions($meetId);

            // Group prize winners by event instance, then by position (for the table)
            foreach ($standing->eventResults($meetId) as $r) {
                $key = (int) $r['instance_id'];
                if (!isset($events[$key])) {
                    $events[$key] = [
                        'label'      => $r['instance_label'],
                        'discipline' => $r['discipline_name'],
                        'event'      => $r['event_name'],
                        'category'   => $r['category_name'],
                        'first'      => [],
                        'second'     => [],
                        'third'      => [],
                    ];
                }
                if (isset($events[$key][$r['position']])) {
                    $events[$key][$r['position']][] = $r;
                }
            }
            $events = array_values($events);
        }

        $this->view('standings/index', [
            'title'           => 'Championship Standings',
            'meets'           => $meets,
            'meetId'          => $meetId,
            'meet'            => $meet,
            'houses'          => $houses,
            'events'          => $events,
            'disciplines'     => $disciplines,
            'courseDivisions' => $courseDivisions,
        ]);
    }

    public function export(string $type): void
    {
        $this->authorize(...self::ROLES);
        $meetId = (int) Request::get('meet_id', 0);
        $meet = (new MeetMaster())->find($meetId);
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        $standing = new Standing();

        if ($type === 'houses') {
            $rows = array_map(fn($h) => [
                $h['name'], (int) $h['golds'], (int) $h['silvers'], (int) $h['bronzes'],
                rtrim(rtrim(number_format((float) $h['total_points'], 2), '0'), '.'),
            ], $standing->houses($meetId));
            Csv::download('house_standings', ['House', 'First', 'Second', 'Third', 'Total Points'], $rows);
        }

        if ($type === 'disciplines') {
            $rows = array_map(fn($d) => [
                $d['discipline_name'], (int) $d['golds'], (int) $d['silvers'], (int) $d['bronzes'],
                rtrim(rtrim(number_format((float) $d['total_points'], 2), '0'), '.'),
            ], $standing->disciplines($meetId));
            Csv::download('discipline_standings', ['Discipline', 'First', 'Second', 'Third', 'Total Points'], $rows);
        }

        if ($type === 'course-divisions') {
            $rows = array_map(fn($d) => [
                trim(($d['course_name'] ?? '—') . ' / ' . ($d['division_name'] ?? '—'), ' /'),
                (int) $d['golds'], (int) $d['silvers'], (int) $d['bronzes'],
                rtrim(rtrim(number_format((float) $d['total_points'], 2), '0'), '.'),
            ], $standing->courseDivisions($meetId));
            Csv::download('course_division_standings', ['Course/Division', 'First', 'Second', 'Third', 'Total Points'], $rows);
        }

        // Prize winners by event instance
        $rows = array_map(fn($r) => [
            $r['discipline_name'], $r['instance_label'], $r['category_name'],
            $this->posLabel($r['position']), $r['unique_number'], $r['contestant_name'],
            $r['house_name'] ?? '', trim(($r['course_name'] ?? '') . ' / ' . ($r['division_name'] ?? ''), ' /'),
        ], $standing->eventResults($meetId));
        Csv::download('event_winners', ['Discipline', 'Event Instance', 'Category', 'Position', 'Unique #', 'Contestant', 'House', 'Course/Division'], $rows);
    }
}
