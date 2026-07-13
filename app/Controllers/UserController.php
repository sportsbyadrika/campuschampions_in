<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Model;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Institution;
use App\Models\User;

/**
 * User management. Campus admins manage users within their own campus;
 * super admins manage all users and may assign a campus + the super_admin role.
 */
class UserController extends CrudController
{
    protected array $manageRoles = ['super_admin', 'campus_admin'];
    protected array $viewRoles   = ['super_admin', 'campus_admin'];

    protected function model(): Model
    {
        return new User();
    }

    /** Roles the current user is allowed to assign. */
    private function assignableRoles(): array
    {
        if (Auth::isSuperAdmin()) {
            return Auth::ROLE_LABELS; // all roles
        }
        // Campus admins cannot create super admins
        return [
            'campus_admin' => 'Campus Admin',
            'event_user'   => 'Event User',
            'campus_staff' => 'Campus Staff',
        ];
    }

    protected function config(): array
    {
        $roleOptions = $this->assignableRoles();

        $fields = [
            ['name' => 'full_name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'required' => true, 'options' => $roleOptions],
        ];

        // Super admin chooses the campus
        if (Auth::isSuperAdmin()) {
            $campusOptions = ['' => '— None (system) —'];
            foreach ((new Institution())->options() as $c) {
                $campusOptions[$c['id']] = $c['name'];
            }
            $fields[] = ['name' => 'campus_id', 'label' => 'Campus', 'type' => 'select', 'options' => $campusOptions];
        }

        $fields[] = ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive']];
        $fields[] = ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Min 8 chars (leave blank to keep on edit)'];

        return [
            'entity'       => 'User',
            'entityPlural' => 'Users',
            'route'        => 'users',
            'icon'         => 'fa-users',
            'showCampus'   => true,
            'formColumns'  => 2,
            'columns' => [
                ['key' => 'full_name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'role', 'label' => 'Role', 'type' => 'role'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['key' => 'last_login', 'label' => 'Last Login', 'type' => 'datetime'],
            ],
            'fields'  => $fields,
            'search'  => ['full_name', 'email'],
            'filters' => [
                'role'   => ['label' => 'Role', 'options' => Auth::ROLE_LABELS],
                'status' => ['label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Validation rules
    // ------------------------------------------------------------------
    private function rules(bool $isEdit, ?int $id = null): array
    {
        $emailUnique = $isEdit ? "unique:users,email,{$id}" : 'unique:users,email';
        $rules = [
            'full_name' => 'required|max:150',
            'email'     => "required|email|max:150|{$emailUnique}",
            'role'      => 'required|in:' . implode(',', array_keys($this->assignableRoles())),
            'status'    => 'required|in:active,inactive',
        ];
        $rules['password'] = $isEdit ? 'min:8' : 'required|min:8';
        return $rules;
    }

    // ------------------------------------------------------------------
    // Create
    // ------------------------------------------------------------------
    public function store(): void
    {
        $this->guardManage();
        $data = $this->collectUser();

        $validator = Validator::make($data, $this->rules(false), ['password' => 'Password']);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->firstErrors(), 'message' => 'Please correct the highlighted fields.'], 422);
        }

        $data['password'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $this->applyCampusAssignment($data);

        $id = (new User())->create($data);
        Audit::log('create', 'users', $id, null, $this->redact($data));
        $this->respond('User created successfully.');
    }

    // ------------------------------------------------------------------
    // Update
    // ------------------------------------------------------------------
    public function update(string $id): void
    {
        $this->guardManage();
        $id = (int) $id;

        $existing = (new User())->find($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        // Campus admins cannot edit super admins
        if (!Auth::isSuperAdmin() && $existing['role'] === 'super_admin') {
            $this->json(['success' => false, 'message' => 'You cannot modify this user.'], 403);
        }

        $data = $this->collectUser();

        $validator = Validator::make($data, $this->rules(true, $id), ['password' => 'Password']);
        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->firstErrors(), 'message' => 'Please correct the highlighted fields.'], 422);
        }

        // Password optional on edit
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }
        $this->applyCampusAssignment($data);

        (new User())->update($id, $data);
        Audit::log('update', 'users', $id, $this->redact($existing), $this->redact($data));
        $this->respond('User updated successfully.');
    }

    // ------------------------------------------------------------------
    // Delete (prevent self-deletion)
    // ------------------------------------------------------------------
    public function destroy(string $id): void
    {
        $this->guardManage();
        $id = (int) $id;

        if ($id === (int) Auth::id()) {
            $this->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }

        $existing = (new User())->find($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        if (!Auth::isSuperAdmin() && $existing['role'] === 'super_admin') {
            $this->json(['success' => false, 'message' => 'You cannot delete this user.'], 403);
        }

        (new User())->delete($id);
        Audit::log('delete', 'users', $id, $this->redact($existing), null);
        $this->respond('User deleted successfully.');
    }

    // ------------------------------------------------------------------
    // Find (strip password hash before returning to client)
    // ------------------------------------------------------------------
    public function find(string $id): void
    {
        $this->guardManage();
        $record = (new User())->find((int) $id);
        if (!$record) {
            $this->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        unset($record['password'], $record['reset_token'], $record['reset_token_expiry']);
        $this->json(['success' => true, 'data' => $record]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    private function collectUser(): array
    {
        $data = [
            'full_name' => Request::input('full_name'),
            'email'     => strtolower((string) Request::input('email', '')),
            'role'      => Request::input('role'),
            'status'    => Request::input('status'),
            'password'  => (string) Request::input('password', ''),
        ];
        if (Auth::isSuperAdmin()) {
            $campus = Request::input('campus_id');
            $data['campus_id'] = ($campus === '' || $campus === null) ? null : (int) $campus;
        }
        return $data;
    }

    /** Force campus admins' new users into their own campus. */
    private function applyCampusAssignment(array &$data): void
    {
        if (!Auth::isSuperAdmin()) {
            $data['campus_id'] = Auth::campusId();
        }
    }

    private function redact(array $data): array
    {
        unset($data['password'], $data['reset_token'], $data['reset_token_expiry']);
        return $data;
    }
}
