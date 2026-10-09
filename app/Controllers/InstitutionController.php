<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Core\Request;
use App\Models\Institution;
use App\Models\InstitutionType;

/**
 * Institutions (campuses) — Super Admin only. Manages subscription periods.
 */
class InstitutionController extends CrudController
{
    protected array $manageRoles = ['super_admin'];
    protected array $viewRoles   = ['super_admin'];

    protected function model(): Model
    {
        return new Institution();
    }

    protected function config(): array
    {
        $statusOptions = ['active' => 'Active', 'trial' => 'Trial', 'expired' => 'Expired'];
        $typeOptions = ['' => '— Select type —'];
        foreach ((new InstitutionType())->options() as $r) {
            $typeOptions[$r['id']] = $r['name'];
        }

        return [
            'entity'       => 'Institution',
            'entityPlural' => 'Institutions',
            'route'        => 'institutions',
            'icon'         => 'fa-building-columns',
            'showCampus'   => false,
            'formColumns'  => 2,
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'type_name', 'label' => 'Type'],
                ['key' => 'contact_email', 'label' => 'Contact Email'],
                ['key' => 'contact_phone', 'label' => 'Phone'],
                ['key' => 'subscription_start_date', 'label' => 'Sub. Start', 'type' => 'date'],
                ['key' => 'subscription_end_date', 'label' => 'Sub. End', 'type' => 'date'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Institution Name', 'type' => 'text', 'required' => true, 'full' => true],
                ['name' => 'institution_type_id', 'label' => 'Institution Type', 'type' => 'select', 'options' => $typeOptions],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea'],
                ['name' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email'],
                ['name' => 'contact_phone', 'label' => 'Contact Phone', 'type' => 'text'],
                ['name' => 'subscription_start_date', 'label' => 'Subscription Start', 'type' => 'date'],
                ['name' => 'subscription_end_date', 'label' => 'Subscription End', 'type' => 'date'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => $statusOptions],
            ],
            'rules' => [
                'name'                    => 'required|max:150',
                'contact_email'           => 'email|max:150',
                'contact_phone'           => 'max:30',
                'subscription_start_date' => 'date',
                'subscription_end_date'   => 'date',
                'status'                  => 'required|in:active,trial,expired',
            ],
            'search'  => ['name', 'contact_email', 'contact_phone'],
            'filters' => [
                'status'              => ['label' => 'Status', 'options' => $statusOptions],
                'institution_type_id' => ['label' => 'Type', 'options' => array_filter($typeOptions, fn($k) => $k !== '', ARRAY_FILTER_USE_KEY)],
            ],
        ];
    }

    /** List query with the institution type name joined in. */
    protected function query(array $cfg): array
    {
        $model = $this->model();
        $t = $model->table();

        $filters = [];
        foreach (($cfg['filters'] ?? []) as $field => $_) {
            $val = Request::get($field, '');
            if ($val !== '') {
                $filters["`$t`.$field"] = $val;
            }
        }
        $searchColumns = array_map(fn($c) => "`$t`.$c", $cfg['search'] ?? []);

        return $model->paginate([
            'select'  => "`$t`.*, it.name AS type_name",
            'from'    => "`$t`",
            'joins'   => "LEFT JOIN institution_types it ON it.id = `$t`.institution_type_id",
            'search'  => ['q' => Request::get('q', ''), 'columns' => $searchColumns],
            'filters' => $filters,
            'orderBy' => "`$t`.name ASC",
            'page'    => $this->page(),
            'perPage' => $this->perPage(),
        ]);
    }

    /** Null-safe the optional institution type FK. */
    protected function collect(array $cfg, ?int $id = null): array
    {
        $data = parent::collect($cfg, $id);
        $data['institution_type_id'] = (($data['institution_type_id'] ?? '') === '' || ($data['institution_type_id'] ?? null) === null)
            ? null
            : (int) $data['institution_type_id'];
        return $data;
    }
}
