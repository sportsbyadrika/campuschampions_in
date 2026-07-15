<h1 class="text-xl font-bold text-slate-900">Institution Login</h1>
<p class="mt-1 text-sm text-slate-500">Sign in to your account to continue.</p>

<form method="post" action="<?= e(url('login')) ?>" class="mt-6 space-y-4" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus
               class="form-input" placeholder="you@school.edu">
        <?php if ($err = error_for('email')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
    </div>

    <div>
        <label for="password" class="form-label">Password</label>
        <div class="relative">
            <input type="password" id="password" name="password" required class="form-input pr-10" placeholder="••••••••">
            <button type="button" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600" data-toggle-password="password">
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
        <?php if ($err = error_for('password')): ?><p class="form-error"><?= e($err) ?></p><?php endif; ?>
    </div>

    <div class="flex items-center justify-between text-sm">
        <span></span>
        <a href="<?= e(url('forgot-password')) ?>" class="font-medium text-primary hover:underline">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary w-full justify-center">
        <i class="fa-solid fa-right-to-bracket"></i> Sign In
    </button>
</form>

<div class="mt-6 text-center">
    <a href="<?= e(url('public-results')) ?>" class="text-sm text-slate-500 hover:text-primary">View public results &rarr;</a>
</div>

<script>
document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.togglePassword);
        const icon = btn.querySelector('i');
        if (input.type === 'password') { input.type = 'text'; icon.className = 'fa-solid fa-eye-slash'; }
        else { input.type = 'password'; icon.className = 'fa-solid fa-eye'; }
    });
});
</script>
<?php clear_errors(); clear_old(); ?>
