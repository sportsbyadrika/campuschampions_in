<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csv;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Model;
use App\Core\Request;
use App\Core\Validator;

/**
 * Generic CRUD controller powering all master-data list pages.
 *
 * Concrete controllers implement config() and model(). The engine provides:
 *   index   -> list page (search, filters, pagination)
 *   find    -> JSON single record (for the edit modal)
 *   store   -> create (AJAX/JSON)
 *   update  -> update (AJAX/JSON)
 *   destroy -> delete (AJAX/JSON)
 *   export  -> CSV of the currently filtered data
 *
 * config() returns:
 *   entity, entityPlural, route, icon,
 *   columns  => [ ['key','label','type'?('badge'|'date'|'datetime'|'raw')] ]
 *   fields   => [ ['name','label','type'('text'|'textarea'|'select'|'date'|'color'|'email'|'number'),'required'?,'options'?] ]
 *   rules    => [ field => 'rule|rule' ]
 *   labels   => [ field => 'Label' ]
 *   search   => [ columns... ]
 *   filters  => [ field => ['label','options'=>[val=>label]] ]
 *   showCampus => bool (append campus column/CSV for super admin)
 */
abstract class CrudController extends Controller
{
    abstract protected function config(): array;
    abstract protected function model(): Model;

    /** Roles allowed to manage this resource. Override as needed. */
    protected array $manageRoles = ['super_admin', 'campus_admin'];

    /** Roles allowed to view/list/export. */
    protected array $viewRoles = ['super_admin', 'campus_admin', 'event_user', 'campus_staff'];

    protected function guardManage(): void
    {
        $this->authorize(...$this->manageRoles);
    }

    protected function guardView(): void
    {
        $this->authorize(...$this->viewRoles);
    }

    // ------------------------------------------------------------------
    // List
    // ------------------------------------------------------------------
    public function index(): void
    {
        $this->guardView();
        $cfg = $this->config();

        $result = $this->query($cfg);

        $this->view('crud/index', [
            'title'   => $cfg['entityPlural'],
            'cfg'     => $cfg,
            'result'  => $result,
            'filters' => $this->currentFilters($cfg),
            'search'  => (string) Request::get('q', ''),
            'canManage' => Auth::is(...$this->manageRoles),
        ]);
    }

    /** Build the paginated query honouring search + filters + campus scope. */
    protected function query(array $cfg): array
    {
        $model = $this->model();
        $table = $model->table();

        $showCampus = !empty($cfg['showCampus']) && Auth::isSuperAdmin();
        $select = "`$table`.*" . ($showCampus ? ", inst.name AS campus_name" : "");
        $joins  = $showCampus ? "LEFT JOIN institutions inst ON inst.id = `$table`.campus_id" : "";

        $filters = [];
        foreach (($cfg['filters'] ?? []) as $field => $_) {
            $val = Request::get($field, '');
            if ($val !== '') {
                $filters["`$table`.$field"] = $val;
            }
        }

        $searchColumns = array_map(fn($c) => "`$table`.$c", $cfg['search'] ?? []);

        return $model->paginate([
            'select'  => $select,
            'from'    => "`$table`",
            'joins'   => $joins,
            'search'  => ['q' => Request::get('q', ''), 'columns' => $searchColumns],
            'filters' => $filters,
            'orderBy' => "`$table`.id DESC",
            'page'    => $this->page(),
            'perPage' => $this->perPage(),
            'campusAlias' => "`$table`",
        ]);
    }

    protected function currentFilters(array $cfg): array
    {
        $out = [];
        foreach (($cfg['filters'] ?? []) as $field => $_) {
            $out[$field] = (string) Request::get($field, '');
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Find one (for edit modal)
    // ------------------------------------------------------------------
    public function find(string $id): void
    {
        $this->guardManage();
        $record = $this->model()->find((int) $id);
        if (!$record) {
            $this->json(['success' => false, 'message' => 'Record not found.'], 404);
        }
        $this->json(['success' => true, 'data' => $record]);
    }

    // ------------------------------------------------------------------
    // Create
    // ------------------------------------------------------------------
    public function store(): void
    {
        $this->guardManage();
        $cfg = $this->config();
        $data = $this->collect($cfg);

        $validator = Validator::make($data, $cfg['rules'] ?? [], $cfg['labels'] ?? []);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->firstErrors(), 'message' => 'Please correct the highlighted fields.'], 422);
        }

        $id = $this->model()->create($data);
        Audit::log('create', $this->model()->table(), $id, null, $data);

        $this->respond("{$cfg['entity']} created successfully.");
    }

    // ------------------------------------------------------------------
    // Update
    // ------------------------------------------------------------------
    public function update(string $id): void
    {
        $this->guardManage();
        $cfg = $this->config();
        $id = (int) $id;

        $existing = $this->model()->find($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $data = $this->collect($cfg, $id);

        $validator = Validator::make($data, $this->rulesForUpdate($cfg, $id), $cfg['labels'] ?? []);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->firstErrors(), 'message' => 'Please correct the highlighted fields.'], 422);
        }

        $this->model()->update($id, $data);
        Audit::log('update', $this->model()->table(), $id, $existing, $data);

        $this->respond("{$cfg['entity']} updated successfully.");
    }

    // ------------------------------------------------------------------
    // Delete
    // ------------------------------------------------------------------
    public function destroy(string $id): void
    {
        $this->guardManage();
        $cfg = $this->config();
        $id = (int) $id;

        $existing = $this->model()->find($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        try {
            $this->model()->delete($id);
        } catch (\PDOException $e) {
            // Foreign key constraint (record in use)
            $this->json(['success' => false, 'message' => "Cannot delete this {$cfg['entity']} because it is being used elsewhere."], 409);
        }

        Audit::log('delete', $this->model()->table(), $id, $existing, null);
        $this->respond("{$cfg['entity']} deleted successfully.");
    }

    // ------------------------------------------------------------------
    // CSV export (respects current filters + campus scope)
    // ------------------------------------------------------------------
    public function export(): void
    {
        $this->guardView();
        $cfg = $this->config();

        // Fetch all matching rows (no pagination)
        $model = $this->model();
        $table = $model->table();
        $showCampus = !empty($cfg['showCampus']) && Auth::isSuperAdmin();

        // Reuse query with a very large page size
        $_GET['per_page'] = 100000;
        $_GET['page'] = 1;
        $result = $this->query($cfg);
        $rows = $result['rows'];

        $columns = $cfg['columns'];
        $headers = array_map(fn($c) => $c['label'], $columns);
        if ($showCampus) {
            $headers[] = 'Campus';
        }

        $data = [];
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $this->formatCsvCell($row[$col['key']] ?? '', $col['type'] ?? 'text');
            }
            if ($showCampus) {
                $line[] = $row['campus_name'] ?? '';
            }
            $data[] = $line;
        }

        Audit::log('export_csv', $table, null);
        Csv::download($cfg['route'], $headers, $data);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Collect only configured field values from the request. */
    protected function collect(array $cfg, ?int $id = null): array
    {
        $data = [];
        foreach ($cfg['fields'] as $field) {
            $name = $field['name'];
            $value = Request::input($name);
            if ($field['type'] === 'email' && is_string($value)) {
                $value = strtolower(trim($value));
            }
            $data[$name] = $value;
        }
        return $data;
    }

    /** Inject the current id into any `unique:` rule so it ignores itself. */
    protected function rulesForUpdate(array $cfg, int $id): array
    {
        $rules = $cfg['rules'] ?? [];
        foreach ($rules as $field => &$rule) {
            if (is_string($rule) && str_contains($rule, 'unique:')) {
                $rule = preg_replace_callback('/unique:([^|]+)/', function ($m) use ($id) {
                    $parts = explode(',', $m[1]);
                    // ensure table,column,ignoreId
                    $parts = array_pad($parts, 3, '');
                    $parts[2] = (string) $id;
                    return 'unique:' . implode(',', array_slice($parts, 0, 3));
                }, $rule);
            }
        }
        return $rules;
    }

    protected function formatCsvCell(mixed $value, string $type): string
    {
        return match ($type) {
            'date'     => format_date((string) $value),
            'datetime' => format_datetime((string) $value),
            'badge'    => ucfirst((string) $value),
            'role'     => Auth::roleLabel((string) $value),
            'yesno'    => ((string) $value === '1') ? 'Yes' : 'No',
            default    => (string) ($value ?? ''),
        };
    }

    /** Uniform JSON success response for AJAX modals. */
    protected function respond(string $message): never
    {
        Flash::success($message); // shown after the JS-triggered reload
        $this->json(['success' => true, 'message' => $message]);
    }
}
