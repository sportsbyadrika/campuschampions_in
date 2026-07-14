<?php
/** @var string $title @var array $meets @var ?array $meet @var array $leadHeaders @var array $houses @var array $rows @var array $totals @var string $reportKey */
$printUrl = $meet ? url('reports/' . $reportKey . '/print?meet_id=' . (int) $meet['id']) : null;
include APP_PATH . '/views/reports/_toolbar.php';
if ($meet):
?>
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <?php foreach ($leadHeaders as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
                    <?php foreach ($houses as $house): ?><th class="text-center"><?= e($house['name']) ?></th><?php endforeach; ?>
                    <th class="text-center">Unassigned</th>
                    <th class="text-center font-bold">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($leadHeaders) + count($houses) + 2 ?>" class="text-center py-10 text-slate-400">No data for this meet.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($r['labels'] as $lbl): ?><td class="font-medium"><?= e($lbl) ?></td><?php endforeach; ?>
                            <?php foreach ($houses as $house): $v = $r['counts'][(int) $house['id']] ?? 0; ?>
                                <td class="text-center <?= $v === 0 ? 'text-slate-300' : '' ?>"><?= (int) $v ?></td>
                            <?php endforeach; ?>
                            <td class="text-center <?= (int) $r['unassigned'] === 0 ? 'text-slate-300' : '' ?>"><?= (int) $r['unassigned'] ?></td>
                            <td class="text-center font-semibold"><?= (int) $r['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-slate-50 font-semibold">
                        <?php for ($i = 0; $i < count($leadHeaders); $i++): ?>
                            <td><?= $i === count($leadHeaders) - 1 ? 'TOTAL' : '' ?></td>
                        <?php endfor; ?>
                        <?php foreach ($houses as $house): ?><td class="text-center"><?= (int) ($totals[(int) $house['id']] ?? 0) ?></td><?php endforeach; ?>
                        <td class="text-center"><?= (int) ($totals['__un'] ?? 0) ?></td>
                        <td class="text-center"><?= (int) ($totals['__row'] ?? 0) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
