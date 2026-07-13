<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Models\Institution;

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

        return [
            'entity'       => 'Institution',
            'entityPlural' => 'Institutions',
            'route'        => 'institutions',
            'icon'         => 'fa-building-columns',
            'showCampus'   => false,
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'contact_email', 'label' => 'Contact Email'],
                ['key' => 'contact_phone', 'label' => 'Phone'],
                ['key' => 'subscription_start_date', 'label' => 'Sub. Start', 'type' => 'date'],
                ['key' => 'subscription_end_date', 'label' => 'Sub. End', 'type' => 'date'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Institution Name', 'type' => 'text', 'required' => true],
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
                'status' => ['label' => 'Status', 'options' => $statusOptions],
            ],
        ];
    }
}
