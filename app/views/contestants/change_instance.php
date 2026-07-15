<?php
/** @var array $instances @var array $courses @var array $divisions @var array $groups
 *  @var int $currentInstanceId @var int $courseId @var int $divisionId @var int $groupId
 *  @var string $gender @var array $contestants @var bool $loaded */
$instOptions = function (array $instances, int $current) {
    $out = '';
    foreach ($instances as $i) {
        $label = $i['label'] . ' — ' . $i['meet_title'];
        $out .= '<option value="' . (int) $i['id'] . '"' . ((int) $current === (int) $i['id'] ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $out;
};
$sel = function (array $opts, int $current) {
    $out = '';
    foreach ($opts as $id => $name) {
        $out .= '<option value="' . (int) $id . '"' . ((int) $current === (int) $id ? ' selected' : '') . '>' . e($name) . '</option>';
    }
    return $out;
};
$genders = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
?>
<div class="flex items-center gap-3">
    <a href="<?= e(url('contestants')) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-right-left"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Change Event Instance</h1>
        <p class="text-sm text-slate-500">Move registered contestants from one event instance to another.</p>
    </div>
</div>

<!-- Filters -->
<form method="get" class="mt-6 grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-6">
    <div class="lg:col-span-2">
        <label class="form-label">Current Event Instance</label>
        <select name="current_instance_id" class="form-select">
            <option value="">— Select event instance —</option>
            <?= $instOptions($instances, $currentInstanceId) ?>
        </select>
    </div>
    <div>
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select"><option value="">All</option><?= $sel($courses, $courseId) ?></select>
    </div>
    <div>
        <label class="form-label">Division</label>
        <select name="division_id" class="form-select"><option value="">All</option><?= $sel($divisions, $divisionId) ?></select>
    </div>
    <div>
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
            <option value="">All</option>
            <?php foreach ($genders as $g => $gl): ?><option value="<?= $g ?>" <?= $gender === $g ? 'selected' : '' ?>><?= $gl ?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label">Category Group</label>
        <select name="group_id" class="form-select"><option value="">All</option><?= $sel($groups, $groupId) ?></select>
    </div>
    <div class="sm:col-span-2 lg:col-span-6">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Load Contestants</button>
    </div>
</form>

<?php if ($loaded): ?>
    <?php if (empty($contestants)): ?>
        <div class="mt-6 rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200 text-slate-400">
            <i class="fa-solid fa-inbox text-2xl mb-2 block"></i> No contestants match these filters for the selected event instance.
        </div>
    <?php else: ?>
    <form method="post" action="<?= e(url('contestants/change-instance')) ?>" id="ciForm">
        <?= csrf_field() ?>
        <input type="hidden" name="current_instance_id" value="<?= (int) $currentInstanceId ?>">
        <div class="mt-4 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 p-4">
                <p class="text-sm text-slate-500"><span id="ciCount">0</span> selected of <?= count($contestants) ?></p>
                <button type="button" id="ciAssign" class="btn btn-primary"><i class="fa-solid fa-right-left"></i> Assign New Event Instance</button>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <th class="w-10"><input type="checkbox" id="ciAll"></th>
                        <th>Unique #</th><th>Admission #</th><th>Name</th><th>Gender</th><th>Course / Division</th><th>Category Group</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($contestants as $c): ?>
                            <tr>
                                <td><input type="checkbox" name="contestant_ids[]" value="<?= (int) $c['id'] ?>" class="ci-check"></td>
                                <td class="font-medium whitespace-nowrap"><?= e($c['unique_number']) ?></td>
                                <td><?= e($c['admission_number'] ?? '') ?: '—' ?></td>
                                <td class="font-medium"><?= e($c['name']) ?></td>
                                <td><?= e($genders[$c['gender']] ?? '—') ?></td>
                                <td><?= e(trim(($c['course_name'] ?? '') . ' / ' . ($c['division_name'] ?? ''), ' /') ?: '—') ?></td>
                                <td><?= e($c['group_name'] ?? '') ?: '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal: choose new event instance -->
        <div id="ciModal" class="modal-backdrop hidden" role="dialog" aria-modal="true">
            <div class="modal-panel !max-w-lg">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Assign New Event Instance</h2>
                    <button type="button" class="text-slate-400 hover:text-slate-600" data-ci-close><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="px-6 py-5">
                    <p class="text-sm text-slate-500 mb-3"><span id="ciModalCount">0</span> contestant(s) will be moved to:</p>
                    <label class="form-label">New Event Instance</label>
                    <select name="new_instance_id" id="ciNew" class="form-select">
                        <option value="">— Select event instance —</option>
                        <?= $instOptions($instances, 0) ?>
                    </select>
                    <p class="form-error hidden mt-1" id="ciNewErr">Please choose a new event instance.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" class="btn btn-secondary" data-ci-close>Cancel</button>
                    <button type="submit" id="ciUpdate" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update</button>
                </div>
            </div>
        </div>
    </form>

    <script>
    (function () {
        var all = document.getElementById('ciAll');
        var checks = Array.prototype.slice.call(document.querySelectorAll('.ci-check'));
        var countEl = document.getElementById('ciCount');
        var modal = document.getElementById('ciModal');
        var modalCount = document.getElementById('ciModalCount');
        function selected() { return checks.filter(function (c) { return c.checked; }); }
        function refresh() { countEl.textContent = selected().length; }
        checks.forEach(function (c) { c.addEventListener('change', function () {
            if (!c.checked && all) all.checked = false; refresh();
        }); });
        if (all) all.addEventListener('change', function () { checks.forEach(function (c) { c.checked = all.checked; }); refresh(); });
        refresh();

        function open() { modal.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        function close() { modal.classList.add('hidden'); document.body.style.overflow = ''; }
        document.getElementById('ciAssign').addEventListener('click', function () {
            var n = selected().length;
            if (!n) { window.Toast.show('Select at least one contestant.', 'error'); return; }
            modalCount.textContent = n; open();
        });
        modal.querySelectorAll('[data-ci-close]').forEach(function (b) { b.addEventListener('click', close); });
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        document.getElementById('ciUpdate').addEventListener('click', function (e) {
            if (!document.getElementById('ciNew').value) {
                e.preventDefault();
                document.getElementById('ciNewErr').classList.remove('hidden');
            }
        });
    })();
    </script>
    <?php endif; ?>
<?php else: ?>
    <div class="mt-6 text-center text-slate-400"><i class="fa-solid fa-arrow-up text-2xl mb-2 block"></i>Select an event instance and filters, then load contestants.</div>
<?php endif; ?>
