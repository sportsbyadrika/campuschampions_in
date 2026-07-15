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

    /** Available certificate placeholders, rendered as labelled code chips. */
    private static function placeholderHint(): string
    {
        $items = [
            '{{contestant_name}}' => 'contestant',
            '{{course}}'          => 'course',
            '{{division}}'        => 'division',
            '{{position}}'        => 'result',
            '{{event_label}}'     => 'event instance',
            '{{event_name}}'      => 'event',
            '{{meet_title}}'      => 'meet',
            '{{issue_date}}'      => 'generate date',
            '{{unique_number}}'   => 'unique #',
            '{{house_name}}'      => 'house',
            '{{category}}'        => 'category',
            '{{certificate_number}}' => 'certificate #',
        ];
        $chips = '';
        foreach ($items as $token => $meaning) {
            $chips .= '<span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 mr-1 mb-1">'
                . '<code class="text-primary">' . e($token) . '</code>'
                . '<span class="text-slate-400">' . e($meaning) . '</span></span>';
        }
        return '<span class="font-medium text-slate-600">Placeholders you can use:</span><br>'
            . '<span class="mt-1 flex flex-wrap">' . $chips . '</span>';
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
                ['name' => 'body_html', 'label' => 'Body HTML (use {{placeholders}})', 'type' => 'textarea',
                    'hint' => self::placeholderHint()],
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
