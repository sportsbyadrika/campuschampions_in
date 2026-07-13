<?php
/** @var array $profile */
use App\Core\Auth;
?>
<h1 class="text-2xl font-bold text-slate-900">My Profile</h1>

<div class="mt-6 max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <form method="post" action="<?= e(url('profile')) ?>" class="space-y-4" novalidate>
        <?= csrf_field() ?>
        <div>
            <label class="form-label" for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" class="form-input" value="<?= e($profile['full_name'] ?? '') ?>" required>
            <?php if ($err = error_for('full_name')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
        </div>
        <div>
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-input" value="<?= e($profile['email'] ?? '') ?>" required>
            <?php if ($err = error_for('email')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
        </div>
        <div>
            <label class="form-label">Role</label>
            <input type="text" class="form-input bg-slate-50" value="<?= e(Auth::roleLabel($profile['role'] ?? '')) ?>" disabled>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= e(url('change-password')) ?>" class="btn btn-secondary"><i class="fa-solid fa-key"></i> Change Password</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </form>
</div>
<?php clear_errors(); clear_old(); ?>
