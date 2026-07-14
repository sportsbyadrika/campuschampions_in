<?php
$cards = [
    ['url' => 'reports/instances-house',      'icon' => 'fa-table-cells',      'title' => 'Instances × House',            'desc' => 'Contestant count per event instance, pivoted by house.'],
    ['url' => 'reports/course-house',         'icon' => 'fa-table-cells-large','title' => 'Course/Division × House',      'desc' => 'Contestant count per course/division, pivoted by house.'],
    ['url' => 'reports/instance-contestants', 'icon' => 'fa-list-check',       'title' => 'Instance Contestant List',     'desc' => 'Contestants registered per event instance (sorted by name).'],
    ['url' => 'reports/class-contestants',    'icon' => 'fa-users-rectangle',  'title' => 'Class/Division Contestants',   'desc' => 'Contestants by class/division with their event instances.'],
];
?>
<div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-chart-column"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Reports</h1>
        <p class="text-sm text-slate-500">Select a report — you'll choose a meet on the next screen.</p>
    </div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($cards as $c): ?>
        <a href="<?= e(url($c['url'])) ?>" class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-primary hover:shadow-md transition">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition">
                <i class="fa-solid <?= e($c['icon']) ?> text-lg"></i>
            </span>
            <h2 class="mt-3 font-semibold text-slate-900"><?= e($c['title']) ?></h2>
            <p class="mt-1 text-sm text-slate-500"><?= e($c['desc']) ?></p>
            <span class="mt-3 inline-flex items-center text-sm font-medium text-primary">Open <i class="fa-solid fa-arrow-right ml-1 text-xs"></i></span>
        </a>
    <?php endforeach; ?>

    <?php if (can('super_admin')): ?>
        <a href="<?= e(url('reports/system')) ?>" class="group rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-primary hover:shadow-md transition">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-primary group-hover:text-white transition"><i class="fa-solid fa-server text-lg"></i></span>
            <h2 class="mt-3 font-semibold text-slate-900">System Overview</h2>
            <p class="mt-1 text-sm text-slate-500">Platform totals, per-institution breakdown and full CSV exports.</p>
            <span class="mt-3 inline-flex items-center text-sm font-medium text-primary">Open <i class="fa-solid fa-arrow-right ml-1 text-xs"></i></span>
        </a>
    <?php endif; ?>
</div>
