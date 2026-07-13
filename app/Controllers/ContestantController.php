<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\FileUpload;
use App\Core\Model;
use App\Core\Request;
use App\Core\Validator;
use App\Models\ContestantMaster;
use App\Models\Course;
use App\Models\CourseCategoryGroup;
use App\Models\Division;
use App\Models\House;

class ContestantController extends CrudController
{
    protected array $manageRoles = ['super_admin', 'campus_admin'];
    protected array $viewRoles   = ['super_admin', 'campus_admin', 'event_user', 'campus_staff'];

    protected function model(): Model
    {
        return new ContestantMaster();
    }

    /** [id => name] options for a campus-scoped master. '' => label first. */
    private function fkOptions(Model $m, string $placeholder): array
    {
        $opts = ['' => $placeholder];
        foreach ($m->options() as $r) {
            $opts[$r['id']] = $r['name'];
        }
        return $opts;
    }

    protected function config(): array
    {
        $courses = $this->fkOptions(new Course(), '— Select course —');
        $divisions = $this->fkOptions(new Division(), '— Select division —');
        $houses = $this->fkOptions(new House(), '— Select house —');
        $groups = $this->fkOptions(new CourseCategoryGroup(), '— Select group —');

        return [
            'entity'       => 'Contestant',
            'entityPlural' => 'Contestants',
            'route'        => 'contestants',
            'icon'         => 'fa-user-group',
            'showCampus'   => true,
            'formColumns'  => 3,
            'extraActions' => [
                ['label' => 'Bulk Upload', 'url' => 'contestants/bulk', 'icon' => 'fa-file-arrow-up', 'manage' => true],
            ],
            'columns' => [
                ['key' => 'unique_number', 'label' => 'Unique #'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'gender', 'label' => 'Gender'],
                ['key' => 'course_name', 'label' => 'Course'],
                ['key' => 'house_name', 'label' => 'House'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
            ],
            'fields' => [
                ['name' => 'unique_number', 'label' => 'Unique Number', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
                ['name' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['' => '— Select —', 'M' => 'Male', 'F' => 'Female', 'O' => 'Other']],
                ['name' => 'course_id', 'label' => 'Course', 'type' => 'select', 'options' => $courses],
                ['name' => 'division_id', 'label' => 'Division', 'type' => 'select', 'options' => $divisions],
                ['name' => 'house_id', 'label' => 'House', 'type' => 'select', 'options' => $houses],
                ['name' => 'course_category_group_id', 'label' => 'Category Group', 'type' => 'select', 'options' => $groups],
                ['name' => 'mobile', 'label' => 'Mobile', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'guardian_name', 'label' => 'Guardian Name', 'type' => 'text'],
                ['name' => 'photo', 'label' => 'Photo (JPG/PNG/WEBP, ≤2MB)', 'type' => 'file'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'search'  => ['unique_number', 'name', 'mobile', 'email'],
            'filters' => [
                'status'    => ['label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                'gender'    => ['label' => 'Gender', 'options' => ['M' => 'Male', 'F' => 'Female', 'O' => 'Other']],
                'course_id' => ['label' => 'Course', 'options' => array_filter($courses, fn($k) => $k !== '', ARRAY_FILTER_USE_KEY)],
                'house_id'  => ['label' => 'House', 'options' => array_filter($houses, fn($k) => $k !== '', ARRAY_FILTER_USE_KEY)],
            ],
        ];
    }

    /** Custom list query with FK name joins. */
    protected function query(array $cfg): array
    {
        $model = $this->model();
        $t = $model->table();
        $showCampus = Auth::isSuperAdmin();

        $select = "`$t`.*, c.name AS course_name, d.name AS division_name, "
                . "h.name AS house_name, g.name AS group_name"
                . ($showCampus ? ", inst.name AS campus_name" : "");
        $joins = "LEFT JOIN courses c ON c.id = `$t`.course_id "
               . "LEFT JOIN divisions d ON d.id = `$t`.division_id "
               . "LEFT JOIN houses h ON h.id = `$t`.house_id "
               . "LEFT JOIN course_category_groups g ON g.id = `$t`.course_category_group_id"
               . ($showCampus ? " LEFT JOIN institutions inst ON inst.id = `$t`.campus_id" : "");

        $filters = [];
        foreach (($cfg['filters'] ?? []) as $field => $_) {
            $val = Request::get($field, '');
            if ($val !== '') {
                $filters["`$t`.$field"] = $val;
            }
        }
        $searchColumns = array_map(fn($col) => "`$t`.$col", $cfg['search'] ?? []);

        return $model->paginate([
            'select'  => $select,
            'from'    => "`$t`",
            'joins'   => $joins,
            'search'  => ['q' => Request::get('q', ''), 'columns' => $searchColumns],
            'filters' => $filters,
            'orderBy' => "`$t`.id DESC",
            'page'    => $this->page(),
            'perPage' => $this->perPage(),
            'campusAlias' => "`$t`",
        ]);
    }

    // ------------------------------------------------------------------
    // Create / Update with photo handling
    // ------------------------------------------------------------------
    public function store(): void
    {
        $this->guardManage();
        $data = $this->collectContestant();

        $validator = Validator::make($data, $this->rules(false), []);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->firstErrors(), 'message' => 'Please correct the highlighted fields.'], 422);
        }

        $photo = Request::file('photo');
        if ($photo && ($photo['error'] ?? 4) === UPLOAD_ERR_OK) {
            try {
                $data['photo_path'] = FileUpload::image($photo, 'contestants');
            } catch (\RuntimeException $e) {
                $this->json(['success' => false, 'errors' => ['photo' => $e->getMessage()], 'message' => $e->getMessage()], 422);
            }
        }

        $id = $this->model()->create($data);
        Audit::log('create', 'contestant_masters', $id, null, $data);
        $this->respond('Contestant created successfully.');
    }

    public function update(string $id): void
    {
        $this->guardManage();
        $id = (int) $id;
        $existing = $this->model()->find($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Contestant not found.'], 404);
        }

        $data = $this->collectContestant($id);

        $validator = Validator::make($data, $this->rules(true, $id), []);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->firstErrors(), 'message' => 'Please correct the highlighted fields.'], 422);
        }

        $photo = Request::file('photo');
        if ($photo && ($photo['error'] ?? 4) === UPLOAD_ERR_OK) {
            try {
                $data['photo_path'] = FileUpload::image($photo, 'contestants');
                FileUpload::delete($existing['photo_path'] ?? null);
            } catch (\RuntimeException $e) {
                $this->json(['success' => false, 'errors' => ['photo' => $e->getMessage()], 'message' => $e->getMessage()], 422);
            }
        }

        $this->model()->update($id, $data);
        Audit::log('update', 'contestant_masters', $id, $existing, $data);
        $this->respond('Contestant updated successfully.');
    }

    public function destroy(string $id): void
    {
        $this->guardManage();
        $id = (int) $id;
        $existing = $this->model()->find($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Contestant not found.'], 404);
        }
        try {
            $this->model()->delete($id);
        } catch (\PDOException $e) {
            $this->json(['success' => false, 'message' => 'Cannot delete: this contestant has registrations or results.'], 409);
        }
        FileUpload::delete($existing['photo_path'] ?? null);
        Audit::log('delete', 'contestant_masters', $id, $existing, null);
        $this->respond('Contestant deleted successfully.');
    }

    // ------------------------------------------------------------------
    private function collectContestant(?int $id = null): array
    {
        $intOrNull = fn($v) => ($v === '' || $v === null) ? null : (int) $v;
        return [
            'unique_number' => Request::input('unique_number'),
            'name'          => Request::input('name'),
            'dob'           => Request::input('dob') ?: null,
            'gender'        => Request::input('gender') ?: null,
            'course_id'     => $intOrNull(Request::input('course_id')),
            'division_id'   => $intOrNull(Request::input('division_id')),
            'house_id'      => $intOrNull(Request::input('house_id')),
            'course_category_group_id' => $intOrNull(Request::input('course_category_group_id')),
            'mobile'        => Request::input('mobile') ?: null,
            'email'         => ($e = strtolower((string) Request::input('email', ''))) !== '' ? $e : null,
            'guardian_name' => Request::input('guardian_name') ?: null,
            'status'        => Request::input('status', 'active'),
        ];
    }

    private function rules(bool $isEdit, ?int $id = null): array
    {
        $unique = $isEdit
            ? "unique:contestant_masters,unique_number,{$id}"
            : 'unique:contestant_masters,unique_number';
        return [
            'unique_number' => "required|max:50|{$unique}",
            'name'          => 'required|max:150',
            'gender'        => 'in:M,F,O',
            'email'         => 'email|max:150',
            'mobile'        => 'max:30',
            'dob'           => 'date',
            'status'        => 'required|in:active,inactive',
        ];
    }
}
