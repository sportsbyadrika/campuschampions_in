<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\ContestantMaster;
use App\Models\Course;
use App\Models\CourseCategoryGroup;
use App\Models\Division;
use App\Models\House;

/**
 * Course/Division-wise bulk edit of contestants (grid editing on a separate page).
 */
class ContestantBulkEditController extends Controller
{
    private function guard(): void
    {
        $this->authorize('super_admin', 'campus_admin');
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

    public function form(): void
    {
        $this->guard();

        $courses   = $this->opts(new Course());
        $divisions = $this->opts(new Division());
        $houses    = $this->opts(new House());
        $groups    = $this->opts(new CourseCategoryGroup());

        $courseId   = (int) Request::get('course_id', 0);
        $divisionId = (int) Request::get('division_id', 0);

        $contestants = [];
        $loaded = false;
        if ($courseId > 0 && $divisionId > 0 && isset($courses[$courseId], $divisions[$divisionId])) {
            $loaded = true;
            $params = [$courseId, $divisionId];
            $sql = "SELECT id, unique_number, admission_number, name, gender,
                           house_id, course_category_group_id, course_id, division_id
                    FROM contestant_masters
                    WHERE course_id = ? AND division_id = ?";
            if (Auth::campusId() !== null) {
                $sql .= " AND campus_id = ?";
                $params[] = Auth::campusId();
            }
            $sql .= " ORDER BY name";
            $contestants = Database::instance()->fetchAll($sql, $params);
        }

        $this->view('contestants/bulk_edit', [
            'title'       => 'Bulk Edit Contestants',
            'courses'     => $courses,
            'divisions'   => $divisions,
            'houses'      => $houses,
            'groups'      => $groups,
            'courseId'    => $courseId,
            'divisionId'  => $divisionId,
            'contestants' => $contestants,
            'loaded'      => $loaded,
        ]);
    }

    public function update(): void
    {
        $this->guard();

        $courseId   = (int) Request::input('course_id', 0);
        $divisionId = (int) Request::input('division_id', 0);
        $rows = Request::input('rows');

        if (!is_array($rows) || empty($rows)) {
            Flash::warning('Nothing to update.');
            $this->redirect('/contestants/bulk-edit?course_id=' . $courseId . '&division_id=' . $divisionId);
        }

        // Allowed id sets (campus-scoped) to prevent cross-campus injection
        $allowedCourses   = $this->opts(new Course());
        $allowedDivisions = $this->opts(new Division());
        $allowedHouses    = $this->opts(new House());
        $allowedGroups    = $this->opts(new CourseCategoryGroup());
        $pick = fn($v, array $allowed) => ($v !== '' && $v !== null && isset($allowed[(int) $v])) ? (int) $v : null;

        $model = new ContestantMaster();
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $cid => $f) {
            $cid = (int) $cid;
            $name = trim((string) ($f['name'] ?? ''));
            if ($name === '') { // never blank out the name
                $skipped++;
                continue;
            }
            $gender = strtoupper(trim((string) ($f['gender'] ?? '')));
            $data = [
                'admission_number'         => trim((string) ($f['admission_number'] ?? '')) ?: null,
                'name'                     => $name,
                'gender'                   => in_array($gender, ['M', 'F', 'O'], true) ? $gender : null,
                'house_id'                 => $pick($f['house_id'] ?? null, $allowedHouses),
                'course_category_group_id' => $pick($f['course_category_group_id'] ?? null, $allowedGroups),
                'course_id'                => $pick($f['course_id'] ?? null, $allowedCourses),
                'division_id'              => $pick($f['division_id'] ?? null, $allowedDivisions),
            ];
            $model->update($cid, $data); // campus-scoped in the model
            $updated++;
        }

        Audit::log('bulk_edit', 'contestant_masters', null, null, ['updated' => $updated]);
        $msg = "Updated {$updated} contestant(s).";
        if ($skipped > 0) {
            $msg .= " Skipped {$skipped} row(s) with a blank name.";
        }
        Flash::success($msg);
        // Return to the same course/division view
        $this->redirect('/contestants/bulk-edit?course_id=' . $courseId . '&division_id=' . $divisionId);
    }
}
