<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Request;
use App\Models\MeetMaster;

/**
 * Reports hub. The four meet-based reports are available to campus admins,
 * event users and campus staff (and super admins). System-wide reports remain
 * super-admin only.
 */
class ReportsController extends Controller
{
    private const REPORT_ROLES = ['super_admin', 'campus_admin', 'event_user', 'campus_staff'];

    // ------------------------------------------------------------------
    // Hub
    // ------------------------------------------------------------------
    public function index(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        $this->view('reports/hub', ['title' => 'Reports']);
    }

    // ------------------------------------------------------------------
    // System overview (super admin only) — unchanged content
    // ------------------------------------------------------------------
    public function system(): void
    {
        $this->authorize('super_admin');
        $db = Database::instance();

        $totals = [
            'Institutions' => (int) $db->scalar("SELECT COUNT(*) FROM institutions"),
            'Users'        => (int) $db->scalar("SELECT COUNT(*) FROM users"),
            'Contestants'  => (int) $db->scalar("SELECT COUNT(*) FROM contestant_masters"),
            'Meets'        => (int) $db->scalar("SELECT COUNT(*) FROM meet_masters"),
            'Results'      => (int) $db->scalar("SELECT COUNT(*) FROM results"),
            'Certificates' => (int) $db->scalar("SELECT COUNT(*) FROM certificates"),
        ];
        $perCampus = $db->fetchAll(
            "SELECT i.id, i.name, i.status, i.subscription_end_date,
                    (SELECT COUNT(*) FROM users u WHERE u.campus_id = i.id) AS users,
                    (SELECT COUNT(*) FROM contestant_masters c WHERE c.campus_id = i.id) AS contestants,
                    (SELECT COUNT(*) FROM meet_masters m WHERE m.campus_id = i.id) AS meets
             FROM institutions i ORDER BY i.name"
        );
        $exports = [
            'Institutions' => 'institutions/export', 'Users' => 'users/export',
            'Courses' => 'courses/export', 'Divisions' => 'divisions/export',
            'Houses' => 'houses/export', 'Contestants' => 'contestants/export',
            'Meets' => 'meets/export', 'Audit Logs' => 'audit-logs/export',
        ];
        $this->view('reports/system', [
            'title' => 'System Reports', 'totals' => $totals, 'perCampus' => $perCampus, 'exports' => $exports,
        ]);
    }

    // ------------------------------------------------------------------
    // Shared: resolve the selected meet (campus-scoped) or null
    // ------------------------------------------------------------------
    private function selectedMeet(): ?array
    {
        $meetId = (int) Request::get('meet_id', 0);
        if ($meetId <= 0) {
            return null;
        }
        $meet = (new MeetMaster())->find($meetId); // campus-scoped ownership
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        return $meet;
    }

    /** Houses (columns) for a campus. */
    private function houseColumns(int $campusId): array
    {
        return Database::instance()->fetchAll(
            "SELECT id, name FROM houses WHERE campus_id = ? ORDER BY name",
            [$campusId]
        );
    }

    private function wantsCsv(): bool
    {
        return Request::get('export') === 'csv';
    }

    // ==================================================================
    // Report 1: Event Instances (rows) x House (cols) -> contestant count
    // ==================================================================
    public function instancesHouse(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        $meet = $this->selectedMeet();
        $meets = (new MeetMaster())->options();

        $houses = [];
        $rows = [];
        $totals = [];
        if ($meet) {
            [$houses, $rows, $totals] = $this->buildInstanceHousePivot((int) $meet['id'], (int) $meet['campus_id']);
            if ($this->wantsCsv()) {
                $this->exportPivotCsv('instances_house', ['Event Instance'], $houses, $rows, $totals);
            }
        }

        $this->view('reports/pivot', [
            'title'       => 'Instances × House — Contestant Count',
            'reportKey'   => 'instances-house',
            'leadHeaders' => ['Event Instance'],
            'meets'       => $meets, 'meet' => $meet,
            'houses'      => $houses, 'rows' => $rows, 'totals' => $totals,
        ]);
    }

    private function buildInstanceHousePivot(int $meetId, int $campusId): array
    {
        $db = Database::instance();
        $houses = $this->houseColumns($campusId);

        // All instances of the meet (so rows with zero still appear)
        $instances = $db->fetchAll(
            "SELECT ei.id, ei.label
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ?
             ORDER BY ei.label",
            [$meetId]
        );

        $counts = $db->fetchAll(
            "SELECT ei.id AS instance_id, cm.house_id, COUNT(DISTINCT r.contestant_id) AS cnt
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN contestant_registrations r ON r.event_instance_id = ei.id
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             WHERE d.meet_id = ?
             GROUP BY ei.id, cm.house_id",
            [$meetId]
        );

        return $this->pivot($houses, $instances, 'id', 'label', $counts, 'instance_id');
    }

    // ==================================================================
    // Report 2: Course/Division (rows) x House (cols) -> contestant count
    //           (contestants participating in the meet)
    // ==================================================================
    public function courseHouse(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        $meet = $this->selectedMeet();
        $meets = (new MeetMaster())->options();

        $houses = [];
        $rows = [];
        $totals = [];
        if ($meet) {
            [$houses, $rows, $totals] = $this->buildCourseHousePivot((int) $meet['id'], (int) $meet['campus_id']);
            if ($this->wantsCsv()) {
                $this->exportPivotCsv('course_division_house', ['Course', 'Division'], $houses, $rows, $totals);
            }
        }

        $this->view('reports/pivot', [
            'title'       => 'Course / Division × House — Contestant Count',
            'reportKey'   => 'course-house',
            'leadHeaders' => ['Course', 'Division'],
            'meets'       => $meets, 'meet' => $meet,
            'houses'      => $houses, 'rows' => $rows, 'totals' => $totals,
        ]);
    }

    private function buildCourseHousePivot(int $meetId, int $campusId): array
    {
        $db = Database::instance();
        $houses = $this->houseColumns($campusId);

        $data = $db->fetchAll(
            "SELECT cm.course_id, co.name AS course_name, cm.division_id, dv.name AS division_name,
                    cm.house_id, COUNT(DISTINCT cm.id) AS cnt
             FROM contestant_masters cm
             JOIN contestant_registrations r ON r.contestant_id = cm.id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             LEFT JOIN courses co ON co.id = cm.course_id
             LEFT JOIN divisions dv ON dv.id = cm.division_id
             WHERE d.meet_id = ?
             GROUP BY cm.course_id, cm.division_id, cm.house_id
             ORDER BY co.name, dv.name",
            [$meetId]
        );

        // Build rows keyed by course|division
        $houseIds = array_map(fn($h) => (int) $h['id'], $houses);
        $rowMap = [];
        $totals = array_fill_keys($houseIds, 0);
        $totals['__un'] = 0;
        $totals['__row'] = 0;

        foreach ($data as $d) {
            $key = ($d['course_id'] ?? '0') . '|' . ($d['division_id'] ?? '0');
            if (!isset($rowMap[$key])) {
                $rowMap[$key] = [
                    'labels' => [$d['course_name'] ?? '—', $d['division_name'] ?? '—'],
                    'counts' => array_fill_keys($houseIds, 0),
                    'unassigned' => 0, 'total' => 0,
                ];
            }
            $cnt = (int) $d['cnt'];
            $hid = $d['house_id'] !== null ? (int) $d['house_id'] : null;
            if ($hid !== null && isset($rowMap[$key]['counts'][$hid])) {
                $rowMap[$key]['counts'][$hid] += $cnt;
                $totals[$hid] += $cnt;
            } else {
                $rowMap[$key]['unassigned'] += $cnt;
                $totals['__un'] += $cnt;
            }
            $rowMap[$key]['total'] += $cnt;
            $totals['__row'] += $cnt;
        }
        return [$houses, array_values($rowMap), $totals];
    }

    /**
     * Generic pivot builder for "entity rows x house columns".
     * Returns [houses, rows, totals].
     */
    private function pivot(array $houses, array $entities, string $idKey, string $labelKey, array $counts, string $countEntityKey): array
    {
        $houseIds = array_map(fn($h) => (int) $h['id'], $houses);

        // index counts by entity id
        $byEntity = [];
        foreach ($counts as $c) {
            $eid = (int) $c[$countEntityKey];
            $hid = $c['house_id'] !== null ? (int) $c['house_id'] : null;
            $byEntity[$eid][$hid === null ? '__un' : $hid] = (int) $c['cnt'];
        }

        $totals = array_fill_keys($houseIds, 0);
        $totals['__un'] = 0;
        $totals['__row'] = 0;

        $rows = [];
        foreach ($entities as $ent) {
            $eid = (int) $ent[$idKey];
            $counts_ = array_fill_keys($houseIds, 0);
            $un = 0; $rowTotal = 0;
            foreach (($byEntity[$eid] ?? []) as $hk => $cnt) {
                if ($hk === '__un') { $un += $cnt; $totals['__un'] += $cnt; }
                elseif (isset($counts_[$hk])) { $counts_[$hk] += $cnt; $totals[$hk] += $cnt; }
                $rowTotal += $cnt;
            }
            $totals['__row'] += $rowTotal;
            $rows[] = ['labels' => [$ent[$labelKey]], 'counts' => $counts_, 'unassigned' => $un, 'total' => $rowTotal];
        }
        return [$houses, $rows, $totals];
    }

    private function exportPivotCsv(string $file, array $leadHeaders, array $houses, array $rows, array $totals): void
    {
        $headers = array_merge($leadHeaders, array_map(fn($h) => $h['name'], $houses), ['Unassigned', 'Total']);
        $data = [];
        foreach ($rows as $r) {
            $line = $r['labels'];
            foreach ($houses as $h) { $line[] = $r['counts'][(int) $h['id']] ?? 0; }
            $line[] = $r['unassigned'];
            $line[] = $r['total'];
            $data[] = $line;
        }
        // Totals row
        $totalLine = array_pad([], count($leadHeaders) - 1, '');
        $totalLine[] = 'TOTAL';
        foreach ($houses as $h) { $totalLine[] = $totals[(int) $h['id']] ?? 0; }
        $totalLine[] = $totals['__un'] ?? 0;
        $totalLine[] = $totals['__row'] ?? 0;
        $data[] = $totalLine;

        Csv::download($file, $headers, $data);
    }

    // ==================================================================
    // Report 3: Event Instances -> list of contestants
    // ==================================================================
    public function instanceContestants(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        $meet = $this->selectedMeet();
        $meets = (new MeetMaster())->options();

        $groups = [];
        if ($meet) {
            $rows = Database::instance()->fetchAll(
                "SELECT ei.id AS instance_id, ei.label AS instance_label,
                        cm.unique_number, cm.name, co.name AS course_name, dv.name AS division_name, cm.gender
                 FROM event_instances ei
                 JOIN event_masters e ON e.id = ei.event_id
                 JOIN discipline_masters d ON d.id = e.discipline_id
                 JOIN contestant_registrations r ON r.event_instance_id = ei.id
                 JOIN contestant_masters cm ON cm.id = r.contestant_id
                 LEFT JOIN courses co ON co.id = cm.course_id
                 LEFT JOIN divisions dv ON dv.id = cm.division_id
                 WHERE d.meet_id = ?
                 ORDER BY ei.label, cm.name",
                [(int) $meet['id']]
            );
            foreach ($rows as $r) {
                $groups[$r['instance_label']][] = $r;
            }
            if ($this->wantsCsv()) {
                $data = [];
                foreach ($rows as $r) {
                    $data[] = [$r['instance_label'], $r['unique_number'], $r['name'],
                        trim(($r['course_name'] ?? '') . ' / ' . ($r['division_name'] ?? ''), ' /'), $this->gender($r['gender'])];
                }
                Csv::download('instance_contestants', ['Event Instance', 'Unique #', 'Name', 'Course/Division', 'Gender'], $data);
            }
        }

        $this->view('reports/list_instances', [
            'title'  => 'Event Instance — Contestant List',
            'meets'  => $meets, 'meet' => $meet, 'groups' => $groups,
        ]);
    }

    // ==================================================================
    // Report 4: Class/Division -> list of contestants + participating instances
    // ==================================================================
    public function classContestants(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        $meet = $this->selectedMeet();
        $meets = (new MeetMaster())->options();

        $rows = [];
        if ($meet) {
            $flat = Database::instance()->fetchAll(
                "SELECT cm.id, cm.unique_number, cm.name, co.name AS course_name, dv.name AS division_name,
                        cm.gender, ei.label AS instance_label
                 FROM contestant_masters cm
                 JOIN contestant_registrations r ON r.contestant_id = cm.id
                 JOIN event_instances ei ON ei.id = r.event_instance_id
                 JOIN event_masters e ON e.id = ei.event_id
                 JOIN discipline_masters d ON d.id = e.discipline_id
                 LEFT JOIN courses co ON co.id = cm.course_id
                 LEFT JOIN divisions dv ON dv.id = cm.division_id
                 WHERE d.meet_id = ?
                 ORDER BY co.name, dv.name, cm.name, ei.label",
                [(int) $meet['id']]
            );
            // Group instances per contestant (portable, no GROUP_CONCAT)
            $map = [];
            foreach ($flat as $f) {
                $id = (int) $f['id'];
                if (!isset($map[$id])) {
                    $map[$id] = [
                        'unique_number' => $f['unique_number'], 'name' => $f['name'],
                        'course_name' => $f['course_name'], 'division_name' => $f['division_name'],
                        'gender' => $f['gender'], 'instances' => [],
                    ];
                }
                $map[$id]['instances'][] = $f['instance_label'];
            }
            $rows = array_values($map);
            usort($rows, fn($a, $b) => strcasecmp($a['name'], $b['name']));

            if ($this->wantsCsv()) {
                $data = [];
                foreach ($rows as $r) {
                    $data[] = [$r['unique_number'], $r['name'],
                        trim(($r['course_name'] ?? '') . ' / ' . ($r['division_name'] ?? ''), ' /'),
                        $this->gender($r['gender']), implode(', ', $r['instances'])];
                }
                Csv::download('class_division_contestants', ['Unique #', 'Name', 'Course/Division', 'Gender', 'Participating Event Instances'], $data);
            }
        }

        $this->view('reports/list_class', [
            'title'  => 'Class / Division — Contestant List',
            'meets'  => $meets, 'meet' => $meet, 'rows' => $rows,
        ]);
    }

    private function gender(?string $g): string
    {
        return ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '';
    }
}
