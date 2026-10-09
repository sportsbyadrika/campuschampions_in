<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Models\InstitutionType;

/**
 * Institution Types master (e.g. Engineering, Polytechnic, ITI) — Super Admin only.
 */
class InstitutionTypeController extends CrudController
{
    protected array $manageRoles = ['super_admin'];
    protected array $viewRoles   = ['super_admin'];

    protected function model(): Model
    {
        return new InstitutionType();
    }

    protected function config(): array
    {
        $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];

        return [
            'entity'       => 'Institution Type',
            'entityPlural' => 'Institution Types',
            'route'        => 'institution-types',
            'icon'         => 'fa-sitemap',
            'showCampus'   => false,
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => $statusOptions],
            ],
            'rules' => [
                'name'   => 'required|max:100',
                'status' => 'required|in:active,inactive',
            ],
            'search'  => ['name'],
            'filters' => [
                'status' => ['label' => 'Status', 'options' => $statusOptions],
            ],
        ];
    }
}
