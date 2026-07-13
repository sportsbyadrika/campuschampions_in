<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Models\Course;

class CourseController extends CrudController
{
    protected function model(): Model
    {
        return new Course();
    }

    protected function config(): array
    {
        return [
            'entity'       => 'Course',
            'entityPlural' => 'Courses',
            'route'        => 'courses',
            'icon'         => 'fa-book',
            'showCampus'   => true,
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'rules' => [
                'name'   => 'required|max:150',
                'status' => 'required|in:active,inactive',
            ],
            'search'  => ['name', 'description'],
            'filters' => [
                'status' => ['label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
        ];
    }
}
