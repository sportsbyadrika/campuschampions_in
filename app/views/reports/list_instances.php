<?php
/** @var string $title @var array $meets @var ?array $meet @var array $instances */
include APP_PATH . '/views/reports/_toolbar.php';
if ($meet):
?>
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Discipline</th>
                    <th>Event</th>
                    <th>Category</th>
                    <th>Event Instance</th>
                    <th class="text-center">Participants</th>
                    <th class="text-right print:hidden">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($instances)): ?>
                    <tr><td colspan="6" class="text-center py-10 text-slate-400">No event instances for this meet.</td></tr>
                <?php else: foreach ($instances as $i): ?>
                    <tr>
                        <td><?= e($i['discipline_name']) ?></td>
                        <td><?= e($i['event_name']) ?></td>
                        <td><?= e($i['category_name']) ?></td>
                        <td class="font-medium"><?= e($i['label']) ?></td>
                        <td class="text-center">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700"><?= (int) $i['participants'] ?></span>
                        </td>
                        <td class="text-right whitespace-nowrap print:hidden">
                            <a href="<?= e(url('reports/instance-contestants/' . (int) $i['id'] . '/print')) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm !inline-flex">
                                <i class="fa-solid fa-print"></i> Print
                            </a>
                            <a href="<?= e(url('reports/instance-contestants/' . (int) $i['id'] . '/csv')) ?>" class="btn btn-secondary btn-sm !inline-flex">
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
