<?php
/** @var string $title @var array $meets @var ?array $meet @var array $rows */
$fmtCd = fn($c, $d) => trim(($c ?? '') . ' / ' . ($d ?? ''), ' /');
$gender = fn($g) => ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '';
include APP_PATH . '/views/reports/_toolbar.php';
if ($meet):
?>
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h2 class="font-semibold text-slate-900">Contestants</h2>
        <span class="text-xs text-slate-500"><?= count($rows) ?> contestant(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Unique #</th><th>Name</th><th>Course / Division</th><th>Gender</th><th>Participating Event Instances</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center py-10 text-slate-400">No participating contestants for this meet.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['unique_number']) ?></td>
                        <td class="font-medium"><?= e($r['name']) ?></td>
                        <td><?= e($fmtCd($r['course_name'], $r['division_name'])) ?></td>
                        <td><?= e($gender($r['gender'])) ?></td>
                        <td class="text-sm text-slate-600"><?= e(implode(', ', $r['instances'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
