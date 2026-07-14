<?php
/** @var array $meet @var string $type @var array $headers @var array $cols @var array $preview @var int $validCount @var string $importUrl @var string $backUrl */
$total = count($preview);
?>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Preview <?= $type === 'events' ? 'Events' : 'Event Instances' ?></h1>
        <p class="text-sm text-slate-500">
            <span class="font-semibold text-emerald-600"><?= $validCount ?></span> valid,
            <span class="font-semibold text-rose-600"><?= $total - $validCount ?></span> invalid of <?= $total ?> rows.
        </p>
    </div>
    <a href="<?= e($backUrl) ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
        <table class="data-table">
            <thead class="sticky top-0 bg-white">
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview as $i => $p): $r = $p['raw']; ?>
                    <tr class="<?= $p['valid'] ? '' : 'bg-rose-50/60' ?>">
                        <td><?= $i + 1 ?></td>
                        <td>
                            <?php if ($p['valid']): ?>
                                <span class="inline-flex items-center gap-1 text-emerald-600 text-xs font-medium"><i class="fa-solid fa-circle-check"></i> Valid</span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-rose-600 text-xs font-medium"><i class="fa-solid fa-circle-xmark"></i> Skip</span>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($cols as $c): ?><td><?= e($r[$c] ?? '') ?></td><?php endforeach; ?>
                        <td class="text-xs text-rose-600"><?= e(implode(', ', $p['errors'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-3">
        <p class="text-sm text-slate-500">Only the <strong><?= $validCount ?></strong> valid row(s) will be imported.</p>
        <form method="post" action="<?= e($importUrl) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary" <?= $validCount === 0 ? 'disabled' : '' ?>>
                <i class="fa-solid fa-file-import"></i> Import <?= $validCount ?> Row(s)
            </button>
        </form>
    </div>
</div>
