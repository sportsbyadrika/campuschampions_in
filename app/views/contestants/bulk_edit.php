<?php
/** @var array $courses @var array $divisions @var array $houses @var array $groups @var int $courseId @var int $divisionId @var array $contestants @var bool $loaded */
$sel = function ($opts, $current) {
    $out = '';
    foreach ($opts as $id => $name) {
        $out .= '<option value="' . (int) $id . '"' . ((int) $current === (int) $id ? ' selected' : '') . '>' . e($name) . '</option>';
    }
    return $out;
};
?>
<div class="flex items-center gap-3">
    <a href="<?= e(url('contestants')) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-table-list"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Bulk Edit Contestants</h1>
        <p class="text-sm text-slate-500">Select a course and division, then edit the contestants in a grid.</p>
    </div>
</div>

<!-- Course + Division selector -->
<form method="get" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <div>
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select">
            <option value="">— Select course —</option>
            <?= $sel($courses, $courseId) ?>
        </select>
    </div>
    <div>
        <label class="form-label">Division</label>
        <select name="division_id" class="form-select">
            <option value="">— Select division —</option>
            <?= $sel($divisions, $divisionId) ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-list-check"></i> Load</button>
</form>

<?php if ($loaded): ?>
    <?php if (empty($contestants)): ?>
        <div class="mt-6 rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200 text-slate-400">
            <i class="fa-solid fa-inbox text-2xl mb-2 block"></i> No contestants found for this course and division.
        </div>
    <?php else: ?>
    <form method="post" action="<?= e(url('contestants/bulk-edit')) ?>" class="mt-6">
        <?= csrf_field() ?>
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <input type="hidden" name="division_id" value="<?= $divisionId ?>">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <th>Unique #</th>
                        <th>Admission #</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>House</th>
                        <th>Category</th>
                        <th>Class</th>
                        <th>Division</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($contestants as $c): $cid = (int) $c['id']; ?>
                            <tr>
                                <td class="font-medium whitespace-nowrap"><?= e($c['unique_number']) ?></td>
                                <td><input type="text" name="rows[<?= $cid ?>][admission_number]" value="<?= e($c['admission_number'] ?? '') ?>" class="form-input !py-1.5 w-32"></td>
                                <td><input type="text" name="rows[<?= $cid ?>][name]" value="<?= e($c['name']) ?>" class="form-input !py-1.5 w-48" required></td>
                                <td>
                                    <select name="rows[<?= $cid ?>][gender]" class="form-select !py-1.5">
                                        <option value="">—</option>
                                        <?php foreach (['M' => 'Male', 'F' => 'Female', 'O' => 'Other'] as $g => $gl): ?>
                                            <option value="<?= $g ?>" <?= ($c['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $gl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="rows[<?= $cid ?>][house_id]" class="form-select !py-1.5">
                                        <option value="">—</option><?= $sel($houses, (int) ($c['house_id'] ?? 0)) ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="rows[<?= $cid ?>][course_category_group_id]" class="form-select !py-1.5">
                                        <option value="">—</option><?= $sel($groups, (int) ($c['course_category_group_id'] ?? 0)) ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="rows[<?= $cid ?>][course_id]" class="form-select !py-1.5">
                                        <option value="">—</option><?= $sel($courses, (int) ($c['course_id'] ?? 0)) ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="rows[<?= $cid ?>][division_id]" class="form-select !py-1.5">
                                        <option value="">—</option><?= $sel($divisions, (int) ($c['division_id'] ?? 0)) ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-3">
                <p class="text-sm text-slate-500"><?= count($contestants) ?> contestant(s). Changing Class/Division moves them out of this view after saving.</p>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save All</button>
            </div>
        </div>
    </form>
    <?php endif; ?>
<?php else: ?>
    <div class="mt-6 text-center text-slate-400"><i class="fa-solid fa-arrow-up text-2xl mb-2 block"></i>Select a course and division to begin.</div>
<?php endif; ?>
