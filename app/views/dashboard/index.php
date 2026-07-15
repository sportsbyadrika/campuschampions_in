<?php
/** @var array $stats */
use App\Core\Auth;
$icons = [
    'Institutions' => 'fa-building-columns',
    'Users' => 'fa-users',
    'Contestants' => 'fa-user-group',
    'Meets' => 'fa-calendar-days',
    'Courses' => 'fa-book',
    'Houses' => 'fa-flag',
    'Total Event Instances'   => 'fa-list-check',
    'Total Results Entered'   => 'fa-ranking-star',
    'Total Results Published' => 'fa-bullhorn',
    'Certificates Generated'  => 'fa-award',
];
?>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Welcome back, <?= e(auth()['full_name']) ?> &middot; <span class="font-medium text-primary"><?= e(Auth::roleLabel()) ?></span></p>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500"><?= e($label) ?></p>
                    <p class="mt-1 text-3xl font-bold text-slate-900"><?= e(number_format($value)) ?></p>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <i class="fa-solid <?= e($icons[$label] ?? 'fa-chart-simple') ?> text-lg"></i>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-8 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold text-slate-900">Quick actions</h2>
    <div class="mt-4 flex flex-wrap gap-3">
        <?php if (can('super_admin')): ?>
            <a href="<?= e(url('institutions')) ?>" class="btn btn-secondary"><i class="fa-solid fa-building-columns"></i> Manage Institutions</a>
        <?php endif; ?>
        <?php if (can('super_admin', 'campus_admin')): ?>
            <a href="<?= e(url('contestants')) ?>" class="btn btn-secondary"><i class="fa-solid fa-user-group"></i> Contestants</a>
            <a href="<?= e(url('meets')) ?>" class="btn btn-secondary"><i class="fa-solid fa-calendar-days"></i> Meets</a>
        <?php endif; ?>
        <?php if (can('super_admin', 'campus_admin', 'event_user')): ?>
            <a href="<?= e(url('results')) ?>" class="btn btn-secondary"><i class="fa-solid fa-ranking-star"></i> Enter Results</a>
        <?php endif; ?>
        <a href="<?= e(url('standings')) ?>" class="btn btn-secondary"><i class="fa-solid fa-medal"></i> Standings</a>
    </div>
</div>
