<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Request;
use App\Models\ContestantRegistration;
use App\Models\EventInstance;
use App\Models\EventUserAssignment;
use App\Models\MeetMaster;
use App\Models\PointConfig;
use App\Models\Result;

class ResultController extends Controller
{
    private const POSITIONS = ['first', 'second', 'third', 'participant'];

    /** Load instance + verify campus ownership and (for event users) assignment. */
    private function instanceForEntry(int $instanceId): array
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user');
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail) {
            $this->abort(404, 'Event instance not found.');
        }
        if (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId()) {
            $this->abort(403, 'This event is not in your campus.');
        }
        if (Auth::is('event_user') && !(new EventUserAssignment())->isAssigned((int) Auth::id(), $instanceId)) {
            $this->abort(403, 'You are not assigned to enter results for this event.');
        }
        return $detail;
    }

    // ------------------------------------------------------------------
    // Landing: list instances available for result entry
    // ------------------------------------------------------------------
    public function index(): void
    {
        $this->authorize('super_admin', 'campus_admin', 'event_user', 'campus_staff');

        $meetId = (int) Request::get('meet_id', 0);
        $params = [];
        $where = [];

        if (Auth::campusId() !== null) {
            $where[] = 'm.campus_id = ?';
            $params[] = Auth::campusId();
        }
        if ($meetId > 0) {
            $where[] = 'm.id = ?';
            $params[] = $meetId;
        }
        // Event users only see instances assigned to them
        if (Auth::is('event_user')) {
            $ids = (new EventUserAssignment())->instanceIdsForUser((int) Auth::id());
            if (empty($ids)) {
                $where[] = '1 = 0';
            } else {
                $where[] = 'ei.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
                $params = array_merge($params, $ids);
            }
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $instances = Database::instance()->fetchAll(
            "SELECT ei.id, ei.label, ei.instance_date, ei.status,
                    e.name AS event_name, d.name AS discipline_name, c.name AS category_name,
                    m.id AS meet_id, m.title AS meet_title,
                    (SELECT COUNT(*) FROM contestant_registrations r WHERE r.event_instance_id = ei.id) AS reg_count,
                    (SELECT COUNT(*) FROM results rs WHERE rs.event_instance_id = ei.id) AS result_count
             FROM event_instances ei
             JOIN event_masters e ON e.id = ei.event_id
             JOIN discipline_masters d ON d.id = e.discipline_id
             JOIN categories c ON c.id = ei.category_id
             JOIN meet_masters m ON m.id = d.meet_id
             $whereSql
             ORDER BY ei.instance_date DESC, m.title, e.name",
            $params
        );

        $this->view('results/index', [
            'title'     => 'Results',
            'instances' => $instances,
            'meets'     => (new MeetMaster())->options(),
            'meetId'    => $meetId,
            'canEnter'  => Auth::is('super_admin', 'campus_admin', 'event_user'),
        ]);
    }

    // ------------------------------------------------------------------
    // Result entry grid
    // ------------------------------------------------------------------
    public function entry(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $instance = $this->instanceForEntry($instanceId);

        $registrations = (new ContestantRegistration())->forInstance($instanceId);
        $existing = (new Result())->forInstance($instanceId);
        $points = (new PointConfig())->forMeet((int) $instance['meet_id']);

        $this->view('results/entry', [
            'title'         => 'Enter Results · ' . $instance['label'],
            'instance'      => $instance,
            'registrations' => $registrations,
            'existing'      => $existing,
            'points'        => $points,
            'positions'     => self::POSITIONS,
        ]);
    }

    public function save(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $instance = $this->instanceForEntry($instanceId);
        $pointsMap = (new PointConfig())->forMeet((int) $instance['meet_id']);

        $rows = Request::input('rows');
        if (!is_array($rows)) {
            $this->json(['success' => false, 'message' => 'No data submitted.'], 422);
        }

        // Only registered contestants may receive results
        $validContestants = array_map(fn($r) => (int) $r['contestant_id'], (new ContestantRegistration())->forInstance($instanceId));
        $validSet = array_flip($validContestants);

        $result = new Result();
        $saved = 0;

        Database::instance()->beginTransaction();
        try {
            foreach ($rows as $contestantId => $row) {
                $cid = (int) $contestantId;
                if (!isset($validSet[$cid])) {
                    continue; // ignore non-registered
                }
                $position = (string) ($row['position'] ?? '');
                if ($position === '') {
                    $result->deleteForContestant($instanceId, $cid);
                    continue;
                }
                if (!in_array($position, self::POSITIONS, true)) {
                    continue;
                }
                // Points: use submitted value if numeric, else default from config
                $points = isset($row['points']) && is_numeric($row['points'])
                    ? (float) $row['points']
                    : (float) ($pointsMap[$position] ?? 0);
                $remarks = isset($row['remarks']) ? trim((string) $row['remarks']) : null;

                $result->upsert($instanceId, $cid, $position, $points, $remarks ?: null, (int) Auth::id());
                $saved++;
            }
            Database::instance()->commit();
        } catch (\Throwable $e) {
            Database::instance()->rollBack();
            error_log('Result save failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Failed to save results.'], 500);
        }

        \App\Core\Cache::flush(); // invalidate public results cache
        Audit::log('result_entry', 'results', $instanceId, null, ['saved' => $saved]);
        $this->json(['success' => true, 'message' => "Saved results for {$saved} contestant(s)."]);
    }

    // ------------------------------------------------------------------
    // CSV export of results for an instance
    // ------------------------------------------------------------------
    public function export(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $this->authorize('super_admin', 'campus_admin', 'event_user', 'campus_staff');
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail || (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId())) {
            $this->abort(404, 'Not found.');
        }

        $rows = Database::instance()->fetchAll(
            "SELECT cm.unique_number, cm.name, r.position, r.points, r.remarks
             FROM results r JOIN contestant_masters cm ON cm.id = r.contestant_id
             WHERE r.event_instance_id = ?
             ORDER BY FIELD(r.position,'first','second','third','participant'), cm.name",
            [$instanceId]
        );
        $data = array_map(fn($r) => [
            $r['unique_number'], $r['name'], ucfirst($r['position']), $r['points'], $r['remarks'],
        ], $rows);

        Csv::download('results_' . $instanceId, ['Unique #', 'Contestant', 'Position', 'Points', 'Remarks'], $data);
    }

    // ------------------------------------------------------------------
    // Assignment management (campus_admin / super_admin)
    // ------------------------------------------------------------------
    public function assignForm(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $this->authorize('super_admin', 'campus_admin');
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail || (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId())) {
            $this->abort(404, 'Not found.');
        }
        $assigned = (new EventUserAssignment())->forInstance($instanceId);
        $assignedIds = array_column($assigned, 'user_id');

        // event users in the same campus
        $params = [];
        $sql = "SELECT id, full_name, email FROM users WHERE role = 'event_user' AND status = 'active'";
        if ((int) $detail['campus_id'] > 0) {
            $sql .= " AND campus_id = ?";
            $params[] = (int) $detail['campus_id'];
        }
        if (!empty($assignedIds)) {
            $sql .= " AND id NOT IN (" . implode(',', array_fill(0, count($assignedIds), '?')) . ")";
            $params = array_merge($params, $assignedIds);
        }
        $sql .= " ORDER BY full_name";
        $available = Database::instance()->fetchAll($sql, $params);

        $this->view('results/assign', [
            'title'     => 'Assign Users · ' . $detail['label'],
            'instance'  => $detail,
            'assigned'  => $assigned,
            'available' => $available,
        ]);
    }

    public function assign(string $instanceId): void
    {
        $instanceId = (int) $instanceId;
        $this->authorize('super_admin', 'campus_admin');
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail || (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId())) {
            $this->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        $userId = (int) Request::input('user_id');
        // Verify the user is an event_user in the same campus
        $ok = (int) Database::instance()->scalar(
            "SELECT COUNT(*) FROM users WHERE id = ? AND role = 'event_user'"
            . ($detail['campus_id'] ? " AND campus_id = " . (int) $detail['campus_id'] : ''),
            [$userId]
        );
        if (!$ok) {
            $this->json(['success' => false, 'message' => 'Invalid user.'], 422);
        }
        (new EventUserAssignment())->assign($userId, $instanceId, (int) Auth::id());
        Audit::log('assign_event_user', 'event_user_assignments', $instanceId, null, ['user_id' => $userId]);
        $this->json(['success' => true, 'message' => 'User assigned.']);
    }

    public function unassign(string $instanceId, string $assignmentId): void
    {
        $instanceId = (int) $instanceId;
        $this->authorize('super_admin', 'campus_admin');
        $detail = (new EventInstance())->detail($instanceId);
        if (!$detail || (Auth::campusId() !== null && (int) $detail['campus_id'] !== (int) Auth::campusId())) {
            $this->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        (new EventUserAssignment())->unassign((int) $assignmentId);
        Audit::log('unassign_event_user', 'event_user_assignments', (int) $assignmentId);
        $this->json(['success' => true, 'message' => 'Assignment removed.']);
    }
}
