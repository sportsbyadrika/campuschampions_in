<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Model;
use App\Models\House;

class HouseController extends CrudController
{
    protected function model(): Model
    {
        return new House();
    }

    protected function config(): array
    {
        return [
            'entity'       => 'House',
            'entityPlural' => 'Houses',
            'route'        => 'houses',
            'icon'         => 'fa-flag',
            'showCampus'   => true,
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'color_code', 'label' => 'Colour', 'type' => 'color'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'color_code', 'label' => 'Colour', 'type' => 'color', 'placeholder' => '#4F46E5'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'rules' => [
                'name'       => 'required|max:150',
                'color_code' => 'max:20',
                'status'     => 'required|in:active,inactive',
            ],
            'search'  => ['name'],
            'filters' => [
                'status' => ['label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
        ];
    }
}
