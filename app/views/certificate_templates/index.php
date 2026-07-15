<?php
/** @var array $templates */
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-award"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Certificate Templates</h1>
            <p class="text-sm text-slate-500">Design certificate layouts, positions and numbering.</p>
        </div>
    </div>
    <a href="<?= e(url('certificate-templates/new')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Template</a>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th>Name</th><th>Orientation</th><th class="text-center">Default</th>
                <th>Status</th><th>Updated</th><th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="6" class="text-center py-10 text-slate-400"><i class="fa-solid fa-award text-2xl mb-2 block"></i>No certificate templates yet.</td></tr>
                <?php else: foreach ($templates as $t): ?>
                    <tr>
                        <td class="font-medium text-slate-900"><?= e($t['name']) ?></td>
                        <td class="capitalize"><?= e($t['orientation'] ?? 'portrait') ?></td>
                        <td class="text-center">
                            <?php if ((int) ($t['is_default'] ?? 0) === 1): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Default</span>
                            <?php else: ?><span class="text-slate-300">—</span><?php endif; ?>
                        </td>
                        <td><?= status_badge((string) $t['status']) ?></td>
                        <td class="text-sm text-slate-500"><?= e(format_datetime($t['updated_at'] ?? '')) ?></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="<?= e(url('certificate-templates/' . (int) $t['id'] . '/edit')) ?>" class="text-slate-500 hover:text-primary px-2" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form method="post" action="<?= e(url('certificate-templates/' . (int) $t['id'] . '/delete')) ?>" class="inline" onsubmit="return confirm('Delete this template?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-slate-500 hover:text-rose-600 px-2" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
