<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
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

    // ------------------------------------------------------------------
    // Live big-screen dashboard (public — meant to run on a TV for hours,
    // so it must not be subject to the login session timeout).
    // ------------------------------------------------------------------

    /** Fetch a meet directly (no campus scope) so the public display works. */
    private function publicMeet(int $meetId): array
    {
        $meet = Database::instance()->fetch("SELECT * FROM meet_masters WHERE id = ?", [$meetId]);
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        return $meet;
    }

    public function live(string $meetId): void
    {
        $meet = $this->publicMeet((int) $meetId);
        $institution = (string) Database::instance()->scalar(
            "SELECT name FROM institutions WHERE id = ?",
            [(int) $meet['campus_id']]
        );
        $this->view('standings/live', [
            'meetId'           => (int) $meetId,
            'meetTitle'        => $meet['title'],
            'institution'      => $institution ?: '',
            'meetLogo'         => !empty($meet['logo_path']) ? asset($meet['logo_path']) : '',
            'institutionLogo'  => !empty($meet['institution_logo_path']) ? asset($meet['institution_logo_path']) : '',
            'bannerImage'      => !empty($meet['banner_path']) ? asset($meet['banner_path']) : '',
            'scrollSpeed'      => (int) ($meet['winners_scroll_speed'] ?? 28),
        ], null);
    }

    /**
     * Pivot the raw house×category point rows into:
     *   ['categories' => ['Cat A', ...], 'rows' => [ ['name','color','cells'=>[..],'total'] ]]
     * Rows ordered by total points desc; columns follow the raw row order.
     */
    private function categoryPivot(array $rows): array
    {
        $cats = [];       // category_name (preserves first-seen order = alphabetical from query)
        $houses = [];     // house_id => ['name','color','cells'=>[cat=>pts],'total'=>float]
        foreach ($rows as $r) {
            $cat = (string) $r['category_name'];
            if (!in_array($cat, $cats, true)) {
                $cats[] = $cat;
            }
            $hid = (int) $r['house_id'];
            if (!isset($houses[$hid])) {
                $houses[$hid] = [
                    'name'  => $r['house_name'],
                    'color' => $r['color_code'] ?: '#2563EB',
                    'cells' => [],
                    'total' => 0.0,
                ];
            }
            $pts = (float) $r['points'];
            $houses[$hid]['cells'][$cat] = $pts;
            $houses[$hid]['total'] += $pts;
        }
        usort($houses, fn($a, $b) => $b['total'] <=> $a['total'] ?: strcmp($a['name'], $b['name']));
        // Flatten cells to a positional array matching $cats for compact JSON.
        $out = array_map(function ($h) use ($cats) {
            return [
                'name'   => $h['name'],
                'color'  => $h['color'],
                'points' => array_map(fn($c) => $h['cells'][$c] ?? 0.0, $cats),
                'total'  => $h['total'],
            ];
        }, array_values($houses));
        return ['categories' => $cats, 'rows' => $out];
    }

    /** JSON data for the live dashboard (polled every minute). */
    public function liveData(string $meetId): void
    {
        $this->json($this->livePayload((int) $meetId));
    }

    /** Build the live dashboard payload (separated for testability). */
    private function livePayload(int $meetId): array
    {
        $meet = $this->publicMeet($meetId);
        $institution = (string) Database::instance()->scalar(
            "SELECT name FROM institutions WHERE id = ?",
            [(int) $meet['campus_id']]
        );
        $st = new Standing();

        $byInst = [];
        foreach ($st->eventResults($meetId) as $r) {
            $key = (int) $r['instance_id'];
            if (!isset($byInst[$key])) {
                $byInst[$key] = [
                    'label'  => $r['instance_label'],
                    'sub'    => $r['discipline_name'] . ' · ' . $r['event_name'] . ' · ' . $r['category_name'],
                    'first'  => [], 'second' => [], 'third' => [],
                ];
            }
            $cls = trim(($r['course_name'] ?? '') . ' / ' . ($r['division_name'] ?? ''), ' /');
            $meta = array_filter([$r['house_name'] ?? '', $cls]);
            if (isset($byInst[$key][$r['position']])) {
                $byInst[$key][$r['position']][] = ['name' => $r['contestant_name'], 'meta' => implode(' · ', $meta)];
            }
        }

        return [
            'meet'        => [
                'title'             => $meet['title'],
                'scrollSpeed'       => (int) ($meet['winners_scroll_speed'] ?? 28),
            ],
            'institution' => $institution ?: '',
            'houses'      => array_map(fn($h) => [
                'name' => $h['name'], 'color' => $h['color_code'] ?: '#2563EB',
                'points' => (float) $h['total_points'], 'golds' => (int) $h['golds'],
                'silvers' => (int) $h['silvers'], 'bronzes' => (int) $h['bronzes'],
            ], $st->houses($meetId)),
            'categoryPivot' => $this->categoryPivot($st->houseCategoryPoints($meetId)),
            'events'        => array_values($byInst),
        ];
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
