<?php
/** @var array $totals @var array $perCampus @var array $exports */
$icons = ['Institutions' => 'fa-building-columns', 'Users' => 'fa-users', 'Contestants' => 'fa-user-group', 'Meets' => 'fa-calendar-days', 'Results' => 'fa-ranking-star', 'Certificates' => 'fa-award'];
?>
<div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-chart-line"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">System Reports</h1>
        <p class="text-sm text-slate-500">Platform-wide overview and data exports.</p>
    </div>
</div>

<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
    <?php foreach ($totals as $label => $value): ?>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary mb-2"><i class="fa-solid <?= e($icons[$label] ?? 'fa-hashtag') ?>"></i></span>
            <p class="text-2xl font-bold text-slate-900"><?= number_format($value) ?></p>
            <p class="text-xs text-slate-500"><?= e($label) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="p-4 border-b border-slate-100"><h2 class="font-semibold text-slate-900">Per-institution breakdown</h2></div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Institution</th><th>Status</th><th>Sub. Ends</th><th>Users</th><th>Contestants</th><th>Meets</th></tr></thead>
                <tbody>
                    <?php if (empty($perCampus)): ?>
                        <tr><td colspan="6" class="text-center py-8 text-slate-400">No institutions yet.</td></tr>
                    <?php else: foreach ($perCampus as $c): ?>
                        <tr>
                            <td class="font-medium"><?= e($c['name']) ?></td>
                            <td><?= status_badge($c['status']) ?></td>
                            <td class="text-sm"><?= e(format_date($c['subscription_end_date'])) ?></td>
                            <td><?= (int) $c['users'] ?></td>
                            <td><?= (int) $c['contestants'] ?></td>
                            <td><?= (int) $c['meets'] ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
        <h2 class="font-semibold text-slate-900">Export data</h2>
        <p class="text-sm text-slate-500 mt-1">Download full CSV exports.</p>
        <div class="mt-4 space-y-2">
            <?php foreach ($exports as $label => $route): ?>
                <a href="<?= e(url($route)) ?>" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">
                    <span><?= e($label) ?></span>
                    <i class="fa-solid fa-file-csv text-primary"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
