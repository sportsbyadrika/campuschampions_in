<h1 class="text-xl font-bold text-slate-900">Forgot your password?</h1>
<p class="mt-1 text-sm text-slate-500">Enter your email and we'll send you a reset link.</p>

<form method="post" action="<?= e(url('forgot-password')) ?>" class="mt-6 space-y-4" novalidate>
    <?= csrf_field() ?>
    <div>
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus class="form-input" placeholder="you@school.edu">
        <?php if ($err = error_for('email')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary w-full justify-center"><i class="fa-solid fa-paper-plane"></i> Send Reset Link</button>
</form>

<div class="mt-6 text-center">
    <a href="<?= e(url('login')) ?>" class="text-sm text-slate-500 hover:text-primary">&larr; Back to sign in</a>
</div>
<?php clear_errors(); clear_old(); ?>
