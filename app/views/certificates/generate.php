<?php
/** @var array $instance @var array $contestants @var array $templates */
$posLabels = ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'participant' => 'Participant'];
?>
<div class="flex items-center gap-3">
    <a href="<?= e(url('certificates?meet_id=' . (int) $instance['meet_id'])) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-award"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Generate Certificates</h1>
        <p class="text-sm text-slate-500"><?= e($instance['label']) ?> · <?= e($instance['meet_title']) ?></p>
    </div>
</div>

<?php if (empty($templates)): ?>
    <div class="mt-6 rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-800">
        No certificate templates found. <a href="<?= e(url('certificate-templates')) ?>" class="font-medium underline">Create one first</a>.
    </div>
<?php elseif (empty($contestants)): ?>
    <div class="mt-6 rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200 text-slate-400">
        <i class="fa-solid fa-inbox text-2xl mb-2 block"></i> No results recorded for this event yet.
    </div>
<?php else: ?>
<form id="certForm" class="mt-6 space-y-4">
    <div class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="flex-1 min-w-[12rem]">
            <label class="form-label">Template</label>
            <select name="template_id" class="form-select" required>
                <?php foreach ($templates as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= $t['is_default'] ? 'selected' : '' ?>><?= e($t['name']) ?><?= $t['is_default'] ? ' (default)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-bolt"></i> Generate Selected</button>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th class="w-10"><input type="checkbox" id="selectAll" checked></th>
                    <th>Unique #</th><th>Contestant</th><th>Position</th><th>Certificate #</th><th class="text-right">PDF</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($contestants as $c): ?>
                    <tr>
                        <td><input type="checkbox" name="contestant_ids[]" value="<?= (int) $c['id'] ?>" class="cert-check" checked></td>
                        <td><?= e($c['unique_number']) ?></td>
                        <td class="font-medium"><?= e($c['name']) ?></td>
                        <td><?= e($posLabels[$c['position']] ?? $c['position']) ?></td>
                        <td><?= e($c['certificate_number'] ?? '—') ?></td>
                        <td class="text-right">
                            <?php if (!empty($c['certificate_id']) && !empty($c['file_path'])): ?>
                                <a href="<?= e(url('certificates/download/' . (int) $c['certificate_id'])) ?>" target="_blank" class="text-primary hover:underline text-sm"><i class="fa-solid fa-file-pdf"></i> View</a>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">Not generated</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
window.CERT = { generate: <?= json_encode(url('certificates/' . (int) $instance['id'] . '/generate')) ?> };
</script>
<script src="<?= e(asset('js/certificates.js')) ?>"></script>
<?php endif; ?>
