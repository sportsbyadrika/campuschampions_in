<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Models\CertificateTemplate;

class CertificateTemplateController extends CrudController
{
    protected array $manageRoles = ['super_admin', 'campus_admin'];
    protected array $viewRoles   = ['super_admin', 'campus_admin'];

    protected function model(): Model
    {
        return new CertificateTemplate();
    }

    protected function config(): array
    {
        return [
            'entity'       => 'Template',
            'entityPlural' => 'Certificate Templates',
            'route'        => 'certificate-templates',
            'icon'         => 'fa-award',
            'showCampus'   => true,
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'is_default', 'label' => 'Default', 'type' => 'yesno'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Template Name', 'type' => 'text', 'required' => true],
                ['name' => 'body_html', 'label' => 'Body HTML (use {{placeholders}})', 'type' => 'textarea'],
                ['name' => 'is_default', 'label' => 'Default template', 'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'rules' => [
                'name'      => 'required|max:150',
                'body_html' => 'required',
                'is_default'=> 'in:0,1',
                'status'    => 'required|in:active,inactive',
            ],
            'search'  => ['name'],
            'filters' => [
                'status' => ['label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
        ];
    }
}
