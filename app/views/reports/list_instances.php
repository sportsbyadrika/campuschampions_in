<?php
/** @var string $title @var array $meets @var ?array $meet @var array $groups */
$fmtCd = fn($c, $d) => trim(($c ?? '') . ' / ' . ($d ?? ''), ' /');
$gender = fn($g) => ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '';
include APP_PATH . '/views/reports/_toolbar.php';
if ($meet):
?>
<div class="mt-6 space-y-6">
    <?php if (empty($groups)): ?>
        <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200 text-slate-400">No registered contestants for this meet.</div>
    <?php else: foreach ($groups as $label => $list): ?>
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50">
                <h2 class="font-semibold text-slate-900"><?= e($label) ?></h2>
                <span class="text-xs text-slate-500"><?= count($list) ?> contestant(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Unique #</th><th>Name</th><th>Course / Division</th><th>Gender</th></tr></thead>
                    <tbody>
                        <?php foreach ($list as $r): ?>
                            <tr>
                                <td><?= e($r['unique_number']) ?></td>
                                <td class="font-medium"><?= e($r['name']) ?></td>
                                <td><?= e($fmtCd($r['course_name'], $r['division_name'])) ?></td>
                                <td><?= e($gender($r['gender'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php endif; ?>
