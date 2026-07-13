<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Models\MeetMaster;

class MeetController extends CrudController
{
    protected array $manageRoles = ['super_admin', 'campus_admin'];
    protected array $viewRoles   = ['super_admin', 'campus_admin', 'event_user', 'campus_staff'];

    protected function model(): Model
    {
        return new MeetMaster();
    }

    protected function config(): array
    {
        $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive', 'completed' => 'Completed'];

        return [
            'entity'       => 'Meet',
            'entityPlural' => 'Meets',
            'route'        => 'meets',
            'icon'         => 'fa-calendar-days',
            'showCampus'   => true,
            'rowLink'      => ['urlPattern' => 'meets/{id}/setup', 'icon' => 'fa-sliders', 'title' => 'Configure meet'],
            'columns' => [
                ['key' => 'title', 'label' => 'Title'],
                ['key' => 'location', 'label' => 'Location'],
                ['key' => 'start_date', 'label' => 'Start', 'type' => 'date'],
                ['key' => 'end_date', 'label' => 'End', 'type' => 'date'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
            ],
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date'],
                ['name' => 'location', 'label' => 'Location', 'type' => 'text'],
                ['name' => 'details', 'label' => 'Details', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => $statusOptions],
            ],
            'rules' => [
                'title'      => 'required|max:200',
                'start_date' => 'date',
                'end_date'   => 'date',
                'status'     => 'required|in:active,inactive,completed',
            ],
            'search'  => ['title', 'location'],
            'filters' => [
                'status' => ['label' => 'Status', 'options' => $statusOptions],
            ],
        ];
    }

}
