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

    /** Campus-scoped allowed id sets for FK fields (prevents cross-campus injection). */
    private function allowedSets(): array
    {
        return [
            'course'   => $this->opts(new Course()),
            'division' => $this->opts(new Division()),
            'house'    => $this->opts(new House()),
            'group'    => $this->opts(new CourseCategoryGroup()),
        ];
    }

    /** Build a validated update payload from a row's fields, or null if the name is blank. */
    private function buildRowData(array $f, array $allowed): ?array
    {
        $name = trim((string) ($f['name'] ?? ''));
        if ($name === '') {
            return null;
        }
        $gender = strtoupper(trim((string) ($f['gender'] ?? '')));
        $pick = fn($v, array $a) => ($v !== '' && $v !== null && isset($a[(int) $v])) ? (int) $v : null;
        return [
            'admission_number'         => trim((string) ($f['admission_number'] ?? '')) ?: null,
            'name'                     => $name,
            'gender'                   => in_array($gender, ['M', 'F', 'O'], true) ? $gender : null,
            'house_id'                 => $pick($f['house_id'] ?? null, $allowed['house']),
            'course_category_group_id' => $pick($f['course_category_group_id'] ?? null, $allowed['group']),
            'course_id'                => $pick($f['course_id'] ?? null, $allowed['course']),
            'division_id'              => $pick($f['division_id'] ?? null, $allowed['division']),
        ];
    }

    /** AJAX: save a single contestant row. */
    public function updateRow(string $id): void
    {
        $this->guard();
        $cid = (int) $id;

        // Campus-scoped find -> null for other campuses (rejects cross-campus edits)
        $existing = (new ContestantMaster())->find($cid);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Contestant not found.'], 404);
        }

        $f = [
            'name'             => Request::input('name'),
            'admission_number' => Request::input('admission_number'),
            'gender'           => Request::input('gender'),
            'house_id'         => Request::input('house_id'),
            'course_category_group_id' => Request::input('course_category_group_id'),
            'course_id'        => Request::input('course_id'),
            'division_id'      => Request::input('division_id'),
        ];
        $data = $this->buildRowData($f, $this->allowedSets());
        if ($data === null) {
            $this->json(['success' => false, 'errors' => ['name' => 'Name is required.'], 'message' => 'Name is required.'], 422);
        }

        (new ContestantMaster())->update($cid, $data);
        Audit::log('update', 'contestant_masters', $cid, $existing, $data);
        $this->json(['success' => true, 'message' => 'Saved.']);
    }

    /** Non-AJAX fallback: save all rows in one request. */
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

        $allowed = $this->allowedSets();
        $model = new ContestantMaster();
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $cid => $f) {
            $data = $this->buildRowData(is_array($f) ? $f : [], $allowed);
            if ($data === null) {
                $skipped++;
                continue;
            }
            $model->update((int) $cid, $data); // campus-scoped in the model
            $updated++;
        }

        Audit::log('bulk_edit', 'contestant_masters', null, null, ['updated' => $updated]);
        $msg = "Updated {$updated} contestant(s).";
        if ($skipped > 0) {
            $msg .= " Skipped {$skipped} row(s) with a blank name.";
        }
        Flash::success($msg);
        $this->redirect('/contestants/bulk-edit?course_id=' . $courseId . '&division_id=' . $divisionId);
    }
}
