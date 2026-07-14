<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Pdf;
use App\Core\Request;
use App\Core\View;
use App\Models\EventInstance;
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

        $instances = [];
        if ($meet) {
            $instances = Database::instance()->fetchAll(
                "SELECT ei.id, ei.label, e.name AS event_name, d.name AS discipline_name, c.name AS category_name,
                        (SELECT COUNT(*) FROM contestant_registrations r WHERE r.event_instance_id = ei.id) AS participants
                 FROM event_instances ei
                 JOIN event_masters e ON e.id = ei.event_id
                 JOIN discipline_masters d ON d.id = e.discipline_id
                 JOIN categories c ON c.id = ei.category_id
                 WHERE d.meet_id = ?
                 ORDER BY d.name, ei.label",
                [(int) $meet['id']]
            );
            if ($this->wantsCsv()) {
                $data = array_map(fn($i) => [$i['discipline_name'], $i['event_name'], $i['category_name'], $i['label'], (int) $i['participants']], $instances);
                Csv::download('event_instances_summary', ['Discipline', 'Event', 'Category', 'Instance', 'Participants'], $data);
            }
        }

        $this->view('reports/list_instances', [
            'title'  => 'Event Instance — Contestant List',
            'meets'  => $meets, 'meet' => $meet, 'instances' => $instances,
        ]);
    }

    // ---- Printable / PDF / CSV participant list for a single instance ----

    /** Load an instance (campus-checked) with its institution + participants. */
    private function instanceForReport(int $instanceId): array
    {
        $this->authorize(...self::REPORT_ROLES);
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail) {
            $this->abort(404, 'Event instance not found.');
        }
        if (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId()) {
            $this->abort(403, 'This event is not in your campus.');
        }
        $institution = (string) Database::instance()->scalar(
            "SELECT name FROM institutions WHERE id = ?",
            [(int) $detail['campus_id']]
        );
        $participants = Database::instance()->fetchAll(
            "SELECT cm.unique_number, cm.name, co.name AS course_name, dv.name AS division_name, cm.gender
             FROM contestant_registrations r
             JOIN contestant_masters cm ON cm.id = r.contestant_id
             LEFT JOIN courses co ON co.id = cm.course_id
             LEFT JOIN divisions dv ON dv.id = cm.division_id
             WHERE r.event_instance_id = ?
             ORDER BY cm.name",
            [$instanceId]
        );
        return ['detail' => $detail, 'institution' => $institution ?: '', 'participants' => $participants];
    }

    /** Build the generic $report structure for an event instance. */
    private function instanceReportData(int $instanceId): array
    {
        $d = $this->instanceForReport($instanceId);
        $det = $d['detail'];
        $line = '<strong>' . e($det['discipline_name']) . '</strong> &mdash; '
            . e($det['event_name']) . ' &middot; ' . e($det['category_name']) . ' &middot; ' . e($det['label']);
        $rows = array_map(fn($p) => [
            'unique_number' => $p['unique_number'],
            'name'          => $p['name'],
            'class'         => trim(($p['course_name'] ?? '') . ' / ' . ($p['division_name'] ?? ''), ' /'),
            'gender'        => $this->gender($p['gender']),
        ], $d['participants']);

        return [
            'title'    => 'Participants — ' . $det['label'],
            'main'     => $det['meet_title'],
            'sub'      => $d['institution'],
            'line'     => $line,
            'pdfBase'  => url('reports/instance-contestants/' . $instanceId . '/pdf'),
            'filename' => 'participants_' . $instanceId,
            'columns'  => [
                ['label' => 'Sl No', 'type' => 'sl'],
                ['label' => 'Unique #', 'key' => 'unique_number', 'cls' => 'num'],
                ['label' => 'Name', 'key' => 'name'],
                ['label' => 'Class/Division', 'key' => 'class'],
                ['label' => 'Gender', 'key' => 'gender', 'cls' => 'gen'],
                ['label' => 'Remarks', 'type' => 'blank', 'cls' => 'remarks'],
            ],
            'rows'     => $rows,
        ];
    }

    /** Printable HTML page (opens in a new tab). */
    public function instancePrint(string $instanceId): void
    {
        $report = $this->instanceReportData((int) $instanceId);
        $this->view('reports/participants_print', ['report' => $report], null);
    }

    /** PDF (portrait/landscape) with repeating heading + "Page X of Y" footer. */
    public function instancePdf(string $instanceId): void
    {
        $report = $this->instanceReportData((int) $instanceId);
        $this->streamPdf($report);
    }

    /** Shared: stream a $report structure as PDF using the requested orientation. */
    private function streamPdf(array $report): void
    {
        $orientation = Request::get('orientation') === 'landscape' ? 'landscape' : 'portrait';
        $html = View::partial('reports/participants_pdf', ['report' => $report, 'orientation' => $orientation]);
        Pdf::stream($html, $report['filename'] . '_' . $orientation, $orientation);
    }

    /** CSV of participants for a single instance. */
    public function instanceCsv(string $instanceId): void
    {
        $d = $this->instanceForReport((int) $instanceId);
        $sl = 0;
        $data = array_map(function ($p) use (&$sl) {
            $sl++;
            return [
                $sl, $p['unique_number'], $p['name'],
                trim(($p['course_name'] ?? '') . ' / ' . ($p['division_name'] ?? ''), ' /'),
                $this->gender($p['gender']), '',
            ];
        }, $d['participants']);
        Csv::download('participants_' . (int) $instanceId, ['Sl No', 'Unique #', 'Name', 'Class/Division', 'Gender', 'Remarks'], $data);
    }

    // ==================================================================
    // Report 4: Class/Division -> list of contestants + participating instances
    // ==================================================================
    public function classContestants(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        $meet = $this->selectedMeet();
        $meets = (new MeetMaster())->options();

        $groups = [];
        if ($meet) {
            $groups = Database::instance()->fetchAll(
                "SELECT cm.course_id, co.name AS course_name, cm.division_id, dv.name AS division_name,
                        COUNT(DISTINCT cm.id) AS contestants
                 FROM contestant_masters cm
                 JOIN contestant_registrations r ON r.contestant_id = cm.id
                 JOIN event_instances ei ON ei.id = r.event_instance_id
                 JOIN event_masters e ON e.id = ei.event_id
                 JOIN discipline_masters d ON d.id = e.discipline_id
                 LEFT JOIN courses co ON co.id = cm.course_id
                 LEFT JOIN divisions dv ON dv.id = cm.division_id
                 WHERE d.meet_id = ?
                 GROUP BY cm.course_id, cm.division_id
                 ORDER BY co.name, dv.name",
                [(int) $meet['id']]
            );
            if ($this->wantsCsv()) {
                $data = array_map(fn($g) => [$g['course_name'] ?? '—', $g['division_name'] ?? '—', (int) $g['contestants']], $groups);
                Csv::download('class_division_summary', ['Course', 'Division', 'Contestants'], $data);
            }
        }

        $this->view('reports/list_class', [
            'title'  => 'Class / Division — Contestant List',
            'meets'  => $meets, 'meet' => $meet, 'groups' => $groups,
        ]);
    }

    /** Parse meet_id / course_id / division_id from the query (0 or '' => null). */
    private function classParams(): array
    {
        $meetId = (int) Request::get('meet_id', 0);
        if ($meetId <= 0) {
            $this->abort(404, 'Meet not found.');
        }
        $c = Request::get('course_id', '');
        $dv = Request::get('division_id', '');
        $courseId   = ($c === '' || $c === '0') ? null : (int) $c;
        $divisionId = ($dv === '' || $dv === '0') ? null : (int) $dv;
        return [$meetId, $courseId, $divisionId];
    }

    /** Build the generic $report structure for a class/division group. */
    private function classReportData(int $meetId, ?int $courseId, ?int $divisionId): array
    {
        $meet = (new MeetMaster())->find($meetId); // campus-scoped
        if (!$meet) {
            $this->abort(404, 'Meet not found.');
        }
        $db = Database::instance();
        $institution = (string) $db->scalar("SELECT name FROM institutions WHERE id = ?", [(int) $meet['campus_id']]);
        $courseName   = $courseId !== null ? (string) $db->scalar("SELECT name FROM courses WHERE id = ?", [$courseId]) : null;
        $divisionName = $divisionId !== null ? (string) $db->scalar("SELECT name FROM divisions WHERE id = ?", [$divisionId]) : null;
        $groupLabel = trim(($courseName ?: '—') . ' / ' . ($divisionName ?: '—'));

        // Null-safe course/division conditions (portable)
        $params = [$meetId];
        $cond = $courseId !== null ? ' AND cm.course_id = ?' : ' AND cm.course_id IS NULL';
        if ($courseId !== null) { $params[] = $courseId; }
        $cond .= $divisionId !== null ? ' AND cm.division_id = ?' : ' AND cm.division_id IS NULL';
        if ($divisionId !== null) { $params[] = $divisionId; }

        $flat = $db->fetchAll(
            "SELECT cm.id, cm.unique_number, cm.name, cm.gender, ei.label AS instance_label
             FROM contestant_masters cm
             JOIN contestant_registrations r ON r.contestant_id = cm.id
             JOIN event_instances ei ON ei.id = r.event_instance_id
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             WHERE d.meet_id = ? $cond
             ORDER BY cm.name, ei.label",
            $params
        );
        $map = [];
        foreach ($flat as $f) {
            $id = (int) $f['id'];
            if (!isset($map[$id])) {
                $map[$id] = ['unique_number' => $f['unique_number'], 'name' => $f['name'], 'gender' => $this->gender($f['gender']), 'instances' => []];
            }
            $map[$id]['instances'][] = $f['instance_label'];
        }
        $rows = array_map(fn($m) => [
            'unique_number' => $m['unique_number'],
            'name'          => $m['name'],
            'gender'        => $m['gender'],
            'instances'     => implode(', ', $m['instances']),
        ], array_values($map));

        $qs = 'meet_id=' . $meetId . '&course_id=' . ($courseId ?? 0) . '&division_id=' . ($divisionId ?? 0);
        return [
            'title'    => 'Class/Division — ' . $groupLabel,
            'main'     => $meet['title'],
            'sub'      => $institution ?: '',
            'line'     => 'Class / Division: <strong>' . e($groupLabel) . '</strong>',
            'pdfBase'  => url('reports/class-contestants/pdf?' . $qs),
            'filename' => 'class_' . $meetId . '_' . ($courseId ?? 0) . '_' . ($divisionId ?? 0),
            'columns'  => [
                ['label' => 'Sl No', 'type' => 'sl'],
                ['label' => 'Unique #', 'key' => 'unique_number', 'cls' => 'num'],
                ['label' => 'Name', 'key' => 'name'],
                ['label' => 'Gender', 'key' => 'gender', 'cls' => 'gen'],
                ['label' => 'Participating Event Instances', 'key' => 'instances'],
                ['label' => 'Remarks', 'type' => 'blank', 'cls' => 'remarks'],
            ],
            'rows'     => $rows,
        ];
    }

    public function classPrint(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        [$m, $c, $dv] = $this->classParams();
        $report = $this->classReportData($m, $c, $dv);
        $this->view('reports/participants_print', ['report' => $report], null);
    }

    public function classPdf(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        [$m, $c, $dv] = $this->classParams();
        $this->streamPdf($this->classReportData($m, $c, $dv));
    }

    public function classCsv(): void
    {
        $this->authorize(...self::REPORT_ROLES);
        [$m, $c, $dv] = $this->classParams();
        $report = $this->classReportData($m, $c, $dv);
        $sl = 0;
        $data = array_map(function ($r) use (&$sl) {
            $sl++;
            return [$sl, $r['unique_number'], $r['name'], $r['gender'], $r['instances'], ''];
        }, $report['rows']);
        Csv::download($report['filename'], ['Sl No', 'Unique #', 'Name', 'Gender', 'Participating Event Instances', 'Remarks'], $data);
    }

    // ---- Printable / PDF for the pivot reports ----

    private function pivotMeetOrAbort(): array
    {
        $this->authorize(...self::REPORT_ROLES);
        $meet = $this->selectedMeet();
        if (!$meet) {
            $this->abort(404, 'Please select a meet.');
        }
        return $meet;
    }

    /** Build the $pivot structure for the print/PDF views. */
    private function pivotReportData(string $type, array $meet): array
    {
        $meetId = (int) $meet['id'];
        $institution = (string) Database::instance()->scalar(
            "SELECT name FROM institutions WHERE id = ?",
            [(int) $meet['campus_id']]
        );
        if ($type === 'instances') {
            [$houses, $rows, $totals] = $this->buildInstanceHousePivot($meetId, (int) $meet['campus_id']);
            $lead = ['Event Instance'];
            $title = 'Instances × House — Contestant Count';
            $file = 'instances_house';
            $key = 'instances-house';
        } else {
            [$houses, $rows, $totals] = $this->buildCourseHousePivot($meetId, (int) $meet['campus_id']);
            $lead = ['Course', 'Division'];
            $title = 'Course / Division × House — Contestant Count';
            $file = 'course_division_house';
            $key = 'course-house';
        }
        return [
            'title'       => $title,
            'main'        => $meet['title'],
            'sub'         => $institution ?: '',
            'line'        => e($title),
            'leadHeaders' => $lead,
            'houses'      => $houses,
            'rows'        => $rows,
            'totals'      => $totals,
            'filename'    => $file . '_' . $meetId,
            'pdfBase'     => url('reports/' . $key . '/pdf?meet_id=' . $meetId),
        ];
    }

    private function streamPivotPdf(array $pivot): void
    {
        $orientation = Request::get('orientation') === 'portrait' ? 'portrait' : 'landscape';
        $html = View::partial('reports/pivot_pdf', ['pivot' => $pivot, 'orientation' => $orientation]);
        Pdf::stream($html, $pivot['filename'] . '_' . $orientation, $orientation);
    }

    public function instancesHousePrint(): void
    {
        $this->view('reports/pivot_print', ['pivot' => $this->pivotReportData('instances', $this->pivotMeetOrAbort())], null);
    }

    public function instancesHousePdf(): void
    {
        $this->streamPivotPdf($this->pivotReportData('instances', $this->pivotMeetOrAbort()));
    }

    public function courseHousePrint(): void
    {
        $this->view('reports/pivot_print', ['pivot' => $this->pivotReportData('course', $this->pivotMeetOrAbort())], null);
    }

    public function courseHousePdf(): void
    {
        $this->streamPivotPdf($this->pivotReportData('course', $this->pivotMeetOrAbort()));
    }

    private function gender(?string $g): string
    {
        return ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '';
    }
}
