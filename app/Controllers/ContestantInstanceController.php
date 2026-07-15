<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\Course;
use App\Models\CourseCategoryGroup;
use App\Models\Division;

/**
 * Bulk-change the Event Instance of registered contestants: pick a current
 * instance, filter by course/division/gender/category-group, multi-select
 * contestants and move their registration to a new instance.
 * Campus admin and event users only.
 */
class ContestantInstanceController extends Controller
{
    private function guard(): void
    {
        $this->authorize('campus_admin', 'event_user');
    }

    /** [id => name] options for a campus-scoped master. */
    private function opts(\App\Core\Model $m): array
    {
        $out = [];
        foreach ($m->options() as $r) {
            $out[(int) $r['id']] = $r['name'];
        }
        return $out;
    }

    /** Event instances in the user's campus, alphabetically by label. */
    private function instanceOptions(): array
    {
        $sql = "SELECT ei.id, ei.label, m.title AS meet_title
                FROM event_instances ei
                JOIN event_masters e ON e.id = ei.event_id
                JOIN discipline_masters d ON d.id = e.discipline_id
                JOIN meet_masters m ON m.id = d.meet_id";
        $params = [];
        if (Auth::campusId() !== null) {
            $sql .= " WHERE m.campus_id = ?";
            $params[] = Auth::campusId();
        }
        $sql .= " ORDER BY ei.label ASC";
        return Database::instance()->fetchAll($sql, $params);
    }

    /** True if an event instance belongs to the current campus. */
    private function instanceInCampus(int $instanceId): bool
    {
        if ($instanceId <= 0) {
            return false;
        }
        $sql = "SELECT COUNT(*) FROM event_instances ei
                JOIN event_masters e ON e.id = ei.event_id
                JOIN discipline_masters d ON d.id = e.discipline_id
                JOIN meet_masters m ON m.id = d.meet_id
                WHERE ei.id = ?";
        $params = [$instanceId];
        if (Auth::campusId() !== null) {
            $sql .= " AND m.campus_id = ?";
            $params[] = Auth::campusId();
        }
        return (int) Database::instance()->scalar($sql, $params) > 0;
    }

    public function form(): void
    {
        $this->guard();

        $currentInstanceId = (int) Request::get('current_instance_id', 0);
        $courseId   = (int) Request::get('course_id', 0);
        $divisionId = (int) Request::get('division_id', 0);
        $groupId    = (int) Request::get('group_id', 0);
        $gender     = strtoupper(trim((string) Request::get('gender', '')));
        $gender     = in_array($gender, ['M', 'F', 'O'], true) ? $gender : '';

        $contestants = [];
        $loaded = false;
        if ($currentInstanceId > 0 && $this->instanceInCampus($currentInstanceId)) {
            $loaded = true;
            $sql = "SELECT cm.id, cm.unique_number, cm.admission_number, cm.name, cm.gender,
                           co.name AS course_name, dv.name AS division_name, g.name AS group_name
                    FROM contestant_registrations r
                    JOIN contestant_masters cm ON cm.id = r.contestant_id
                    LEFT JOIN courses co ON co.id = cm.course_id
                    LEFT JOIN divisions dv ON dv.id = cm.division_id
                    LEFT JOIN course_category_groups g ON g.id = cm.course_category_group_id
                    WHERE r.event_instance_id = ?";
            $params = [$currentInstanceId];
            if (Auth::campusId() !== null) {
                $sql .= " AND cm.campus_id = ?";
                $params[] = Auth::campusId();
            }
            if ($courseId > 0)   { $sql .= " AND cm.course_id = ?"; $params[] = $courseId; }
            if ($divisionId > 0) { $sql .= " AND cm.division_id = ?"; $params[] = $divisionId; }
            if ($groupId > 0)    { $sql .= " AND cm.course_category_group_id = ?"; $params[] = $groupId; }
            if ($gender !== '')  { $sql .= " AND cm.gender = ?"; $params[] = $gender; }
            $sql .= " ORDER BY cm.name ASC";
            $contestants = Database::instance()->fetchAll($sql, $params);
        }

        $this->view('contestants/change_instance', [
            'title'             => 'Change Event Instance',
            'instances'         => $this->instanceOptions(),
            'courses'           => $this->opts(new Course()),
            'divisions'         => $this->opts(new Division()),
            'groups'            => $this->opts(new CourseCategoryGroup()),
            'currentInstanceId' => $currentInstanceId,
            'courseId'          => $courseId,
            'divisionId'        => $divisionId,
            'groupId'           => $groupId,
            'gender'            => $gender,
            'contestants'       => $contestants,
            'loaded'            => $loaded,
        ]);
    }

    public function apply(): void
    {
        $this->guard();
        $db = Database::instance();

        $current = (int) Request::input('current_instance_id', 0);
        $target  = (int) Request::input('new_instance_id', 0);
        $ids     = Request::input('contestant_ids');
        $ids     = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];

        $back = '/contestants/change-instance?current_instance_id=' . $current;

        if (!$this->instanceInCampus($current) || !$this->instanceInCampus($target)) {
            Flash::error('Invalid event instance selected.');
            $this->redirect($back);
        }
        if ($current === $target) {
            Flash::warning('The new event instance is the same as the current one.');
            $this->redirect($back);
        }
        if (empty($ids)) {
            Flash::warning('Select at least one contestant.');
            $this->redirect($back);
        }

        // Only contestants in this campus that are actually registered to the current instance.
        $place = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT r.id AS reg_id, cm.id AS contestant_id
                FROM contestant_registrations r
                JOIN contestant_masters cm ON cm.id = r.contestant_id
                WHERE r.event_instance_id = ? AND cm.id IN ($place)";
        $params = array_merge([$current], $ids);
        if (Auth::campusId() !== null) {
            $sql .= " AND cm.campus_id = ?";
            $params[] = Auth::campusId();
        }
        $rows = $db->fetchAll($sql, $params);

        $moved = 0;
        $movedIds = [];
        $db->beginTransaction();
        try {
            foreach ($rows as $row) {
                $cid = (int) $row['contestant_id'];
                // If already registered to the target, drop the current registration (merge);
                // otherwise move the current registration to the target instance.
                $already = (int) $db->scalar(
                    "SELECT COUNT(*) FROM contestant_registrations WHERE contestant_id = ? AND event_instance_id = ?",
                    [$cid, $target]
                );
                if ($already > 0) {
                    $db->query("DELETE FROM contestant_registrations WHERE id = ?", [(int) $row['reg_id']]);
                } else {
                    $db->query("UPDATE contestant_registrations SET event_instance_id = ? WHERE id = ?", [$target, (int) $row['reg_id']]);
                }
                $moved++;
                $movedIds[] = $cid;
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('change-instance failed: ' . $e->getMessage());
            Flash::error('Could not move registrations. Please try again.');
            $this->redirect($back);
        }

        \App\Core\Cache::flush();
        Audit::log('change_event_instance', 'contestant_registrations', null,
            ['from_instance' => $current],
            ['to_instance' => $target, 'moved' => $moved, 'contestant_ids' => $movedIds]
        );
        Flash::success("Moved {$moved} contestant(s) to the new event instance.");
        $this->redirect('/contestants/change-instance?current_instance_id=' . $target);
    }
}
