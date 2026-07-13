<h1 class="text-2xl font-bold text-slate-900">Change Password</h1>

<div class="mt-6 max-w-md rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <form method="post" action="<?= e(url('change-password')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>
        <div>
            <label class="form-label" for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" class="form-input" required>
            <?php if ($err = error_for('current_password')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
        </div>
        <div>
            <label class="form-label" for="password">New password</label>
            <input type="password" id="password" name="password" class="form-input" required>
            <?php if ($err = error_for('password')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
        </div>
        <div>
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= e(url('profile')) ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Update Password</button>
        </div>
    </form>
</div>
<?php clear_errors(); ?>
