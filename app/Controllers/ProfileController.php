<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;

class ProfileController extends Controller
{
    public function show(): void
    {
        $user = (new User())->find((int) Auth::id());
        $this->view('profile/show', ['title' => 'My Profile', 'profile' => $user]);
    }

    public function update(): void
    {
        $id = (int) Auth::id();
        $data = Request::only(['full_name', 'email']);

        $validator = Validator::make($data, [
            'full_name' => 'required|max:150',
            'email'     => "required|email|unique:users,email,{$id}",
        ]);
        if ($validator->fails()) {
            flash_errors($validator->firstErrors());
            flash_old($data);
            $this->redirect('/profile');
        }

        (new User())->update($id, $data);

        // Keep session in sync
        $_SESSION['user']['full_name'] = $data['full_name'];
        $_SESSION['user']['email'] = strtolower($data['email']);

        Audit::log('profile_update', 'users', $id, null, $data);
        Flash::success('Profile updated successfully.');
        $this->redirect('/profile');
    }

    public function showPassword(): void
    {
        $this->view('profile/password', ['title' => 'Change Password']);
    }

    public function updatePassword(): void
    {
        $id = (int) Auth::id();
        $current = (string) Request::input('current_password', '');

        $validator = Validator::make(
            Request::only(['current_password', 'password', 'password_confirmation']),
            [
                'current_password' => 'required',
                'password'         => 'required|min:8|confirmed',
            ],
            ['password' => 'New password', 'current_password' => 'Current password']
        );
        if ($validator->fails()) {
            flash_errors($validator->firstErrors());
            $this->redirect('/change-password');
        }

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user || !password_verify($current, $user['password'])) {
            Flash::error('Your current password is incorrect.');
            $this->redirect('/change-password');
        }

        $userModel->updatePassword($id, password_hash((string) Request::input('password'), PASSWORD_DEFAULT));
        Audit::log('password_change', 'users', $id);
        Flash::success('Password changed successfully.');
        $this->redirect('/profile');
    }
}
