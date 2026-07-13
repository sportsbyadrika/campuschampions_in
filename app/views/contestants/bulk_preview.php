<?php
/** @var array $preview @var array $headers @var int $validCount @var int $totalCount */
?>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Preview Import</h1>
        <p class="text-sm text-slate-500">
            <span class="font-semibold text-emerald-600"><?= $validCount ?></span> valid,
            <span class="font-semibold text-rose-600"><?= $totalCount - $validCount ?></span> invalid of <?= $totalCount ?> rows.
        </p>
    </div>
    <a href="<?= e(url('contestants/bulk')) ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
        <table class="data-table">
            <thead class="sticky top-0 bg-white">
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Unique #</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Course</th>
                    <th>Issues</th>
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
                        <td><?= e($r['unique_number'] ?? '') ?></td>
                        <td><?= e($r['name'] ?? '') ?></td>
                        <td><?= e($r['gender'] ?? '') ?></td>
                        <td><?= e($r['course'] ?? '') ?></td>
                        <td class="text-xs text-rose-600"><?= e(implode(', ', $p['errors'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-3">
        <p class="text-sm text-slate-500">Only the <strong><?= $validCount ?></strong> valid row(s) will be imported.</p>
        <form method="post" action="<?= e(url('contestants/bulk/import')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary" <?= $validCount === 0 ? 'disabled' : '' ?>>
                <i class="fa-solid fa-file-import"></i> Import <?= $validCount ?> Contestant(s)
            </button>
        </form>
    </div>
</div>
