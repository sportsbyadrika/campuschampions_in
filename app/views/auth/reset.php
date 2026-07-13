<?php /** @var string $token */ ?>
<h1 class="text-xl font-bold text-slate-900">Choose a new password</h1>
<p class="mt-1 text-sm text-slate-500">Your new password must be at least 8 characters.</p>

<form method="post" action="<?= e(url('reset-password')) ?>" class="mt-6 space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div>
        <label for="password" class="form-label">New password</label>
        <input type="password" id="password" name="password" required class="form-input" placeholder="••••••••">
        <?php if ($err = error_for('password')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
    </div>

    <div>
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="••••••••">
    </div>

    <button type="submit" class="btn btn-primary w-full justify-center"><i class="fa-solid fa-check"></i> Reset Password</button>
</form>
<?php clear_errors(); ?>
