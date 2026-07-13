<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Validator;
use App\Models\LoginAttempt;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Sign In'], 'layouts/auth');
    }

    public function login(): void
    {
        $email = strtolower((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');
        $ip = Request::ip();

        $attempts = new LoginAttempt();
        $maxAttempts = config('security.login_max_attempts');
        $lockoutMins = config('security.login_lockout_minutes');

        // Rate limiting
        if ($attempts->recentFailures($email, $ip, $lockoutMins) >= $maxAttempts) {
            Flash::error("Too many failed attempts. Please try again in {$lockoutMins} minutes.");
            flash_old(['email' => $email]);
            $this->redirect('/login');
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );
        if ($validator->fails()) {
            flash_errors($validator->firstErrors());
            flash_old(['email' => $email]);
            Flash::error('Please enter a valid email and password.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        $valid = $user
            && $user['status'] === 'active'
            && password_verify($password, $user['password']);

        if (!$valid) {
            $attempts->record($email, $ip, false);
            $remaining = max(0, $maxAttempts - $attempts->recentFailures($email, $ip, $lockoutMins));
            Flash::error('Invalid credentials.' . ($remaining > 0 && $remaining <= 3 ? " {$remaining} attempt(s) left." : ''));
            flash_old(['email' => $email]);
            $this->redirect('/login');
        }

        // Inactive campus check (basic subscription awareness)
        if ($user['campus_id'] !== null) {
            $campusStatus = (new \App\Models\Institution())->find((int) $user['campus_id']);
            if ($campusStatus && $campusStatus['status'] === 'expired') {
                Flash::error('Your campus subscription has expired. Please contact your administrator.');
                $this->redirect('/login');
            }
        }

        // Success
        $attempts->record($email, $ip, true);
        $attempts->clearFailures($email, $ip);
        $userModel->updateLastLogin((int) $user['id']);

        Auth::login($user);
        Audit::log('login', 'users', (int) $user['id']);
        clear_old();
        clear_errors();

        Flash::success('Welcome back, ' . $user['full_name'] . '!');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Audit::log('logout', 'users', Auth::id());
        Auth::logout();
        Flash::success('You have been logged out.');
        $this->redirect('/login');
    }

    // ----------------------------------------------------------------
    // Password reset
    // ----------------------------------------------------------------
    public function showForgot(): void
    {
        $this->view('auth/forgot', ['title' => 'Forgot Password'], 'layouts/auth');
    }

    public function sendReset(): void
    {
        $email = strtolower((string) Request::input('email', ''));

        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            flash_errors($validator->firstErrors());
            $this->redirect('/forgot-password');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        // Always show the same message (avoid user enumeration)
        $genericMsg = 'If an account exists for that email, a reset link has been sent.';

        if ($user && $user['status'] === 'active') {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + config('security.reset_token_minutes') * 60);
            $userModel->setResetToken((int) $user['id'], $token, $expiry);

            $link = url('reset-password/' . $token);
            $body = $this->resetEmailBody($user['full_name'], $link);
            Mailer::send($user['email'], $user['full_name'], 'Reset your Campus Champions password', $body);
            Audit::log('password_reset_requested', 'users', (int) $user['id']);
        }

        Flash::success($genericMsg);
        $this->redirect('/login');
    }

    public function showReset(string $token): void
    {
        $user = (new User())->findByResetToken($token);
        if (!$user) {
            Flash::error('This password reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }
        $this->view('auth/reset', ['title' => 'Reset Password', 'token' => $token], 'layouts/auth');
    }

    public function resetPassword(): void
    {
        $token = (string) Request::input('token', '');
        $password = (string) Request::input('password', '');

        $validator = Validator::make(
            Request::only(['password', 'password_confirmation']),
            ['password' => 'required|min:8|confirmed'],
            ['password' => 'Password']
        );
        if ($validator->fails()) {
            flash_errors($validator->firstErrors());
            $this->redirect('/reset-password/' . urlencode($token));
        }

        $userModel = new User();
        $user = $userModel->findByResetToken($token);
        if (!$user) {
            Flash::error('This password reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }

        $userModel->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        $userModel->clearResetToken((int) $user['id']);
        Audit::log('password_reset_completed', 'users', (int) $user['id']);

        Flash::success('Your password has been reset. Please sign in.');
        $this->redirect('/login');
    }

    private function resetEmailBody(string $name, string $link): string
    {
        $name = e($name);
        $link = e($link);
        $mins = config('security.reset_token_minutes');
        return <<<HTML
            <div style="font-family:Arial,sans-serif;color:#1e293b;">
              <h2 style="color:#2563EB;">Campus Champions</h2>
              <p>Hello {$name},</p>
              <p>We received a request to reset your password. Click the button below to choose a new one.
                 This link expires in {$mins} minutes.</p>
              <p style="margin:24px 0;">
                <a href="{$link}" style="background:#2563EB;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;">Reset Password</a>
              </p>
              <p>If you didn't request this, you can safely ignore this email.</p>
              <p style="color:#64748b;font-size:12px;">{$link}</p>
            </div>
        HTML;
    }
}
