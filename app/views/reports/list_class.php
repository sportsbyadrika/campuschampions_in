<?php
/** @var string $title @var array $meets @var ?array $meet @var array $groups @var string $routeBase */
$routeBase = $routeBase ?? 'class-contestants';
include APP_PATH . '/views/reports/_toolbar.php';
if ($meet):
    $mid = (int) $meet['id'];
?>
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Division</th>
                    <th class="text-center">Contestants</th>
                    <th class="text-right print:hidden">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr><td colspan="4" class="text-center py-10 text-slate-400">No participating contestants for this meet.</td></tr>
                <?php else: foreach ($groups as $g):
                    $cid = $g['course_id'] !== null ? (int) $g['course_id'] : 0;
                    $did = $g['division_id'] !== null ? (int) $g['division_id'] : 0;
                    $qs = 'meet_id=' . $mid . '&course_id=' . $cid . '&division_id=' . $did;
                ?>
                    <tr>
                        <td class="font-medium"><?= e($g['course_name'] ?? '—') ?></td>
                        <td><?= e($g['division_name'] ?? '—') ?></td>
                        <td class="text-center">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700"><?= (int) $g['contestants'] ?></span>
                        </td>
                        <td class="text-right whitespace-nowrap print:hidden">
                            <a href="<?= e(url('reports/' . $routeBase . '/print?' . $qs)) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm !inline-flex">
                                <i class="fa-solid fa-print"></i> Print
                            </a>
                            <a href="<?= e(url('reports/' . $routeBase . '/csv?' . $qs)) ?>" class="btn btn-secondary btn-sm !inline-flex">
                                <i class="fa-solid fa-file-csv"></i> CSV
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>.btn-sm{padding:.3rem .6rem;font-size:.8rem}</style>
<?php endif; ?>
