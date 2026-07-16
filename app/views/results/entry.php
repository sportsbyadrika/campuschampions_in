<?php
/** @var array $instance @var array $registrations @var array $existing @var array $points @var array $positions */
$posLabels = ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'participant' => 'Participant'];
$genderLabels = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= e(url('results')) ?>" class="text-slate-400 hover:text-primary" title="Back to results (keeps your filters)"><i class="fa-solid fa-arrow-left"></i></a>
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-pen"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= e($instance['label']) ?></h1>
            <p class="text-sm text-slate-500"><?= e($instance['discipline_name']) ?> · <?= e($instance['event_name']) ?> · <?= e($instance['category_name']) ?></p>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <?php $pub = (int) ($instance['results_published'] ?? 0) === 1; ?>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-600">Published</span>
            <button type="button" role="switch" aria-checked="<?= $pub ? 'true' : 'false' ?>"
                    class="pub-switch relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors <?= $pub ? 'bg-emerald-500' : 'bg-slate-300' ?>"
                    data-publish-url="<?= e(url('results/' . (int) $instance['id'] . '/publish')) ?>"
                    title="<?= $pub ? 'Published — click to unpublish' : 'Unpublished — click to publish' ?>">
                <span class="pub-knob inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform <?= $pub ? 'translate-x-6' : 'translate-x-1' ?>"></span>
            </button>
        </div>
        <?php if (!empty($existing)): ?>
            <form method="post" action="<?= e(url('results/' . (int) $instance['id'] . '/clear')) ?>" onsubmit="return confirm('Delete ALL entered results for this event? This cannot be undone.');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete All Results</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-800">
    <i class="fa-solid fa-circle-info mr-1"></i>
    Choosing a position auto-fills default points (1st: <?= e((string) $points['first']) ?>,
    2nd: <?= e((string) $points['second']) ?>, 3rd: <?= e((string) $points['third']) ?>,
    Participant: <?= e((string) $points['participant']) ?>). You can override points per contestant.
</div>

<?php if (empty($registrations)): ?>
    <div class="mt-6 rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200 text-slate-400">
        <i class="fa-solid fa-user-slash text-2xl mb-2 block"></i>
        No contestants are registered for this event yet.
        <div class="mt-3"><a href="<?= e(url('instances/' . (int) $instance['id'] . '/registrations')) ?>" class="btn btn-secondary">Manage Registrations</a></div>
    </div>
<?php else: ?>
<form id="resultForm" class="mt-6">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 border-b border-slate-100">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="resultSearch" class="form-input pl-9" placeholder="Search unique #, name or admission #">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 whitespace-nowrap">
                <input type="checkbox" id="enteredOnly" class="rounded border-slate-300"> Show entered rows only
            </label>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table" id="resultTable">
                <thead><tr>
                    <th>Unique #</th><th>Admission #</th><th>Contestant</th>
                    <th>Course</th><th>Division</th><th>Gender</th><th>House</th>
                    <th class="w-40">Position</th><th class="w-28">Points</th><th>Remarks</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($registrations as $r):
                        $cid = (int) $r['contestant_id'];
                        $cur = $existing[$cid] ?? null;
                        $curPos = $cur['position'] ?? '';
                        $curPts = $cur['points'] ?? '';
                        $curRem = $cur['remarks'] ?? '';
                        $searchKey = strtolower(trim($r['unique_number'] . ' ' . ($r['admission_number'] ?? '') . ' ' . $r['contestant_name']));
                    ?>
                    <tr class="result-row" data-search="<?= e($searchKey) ?>">
                        <td><?= e($r['unique_number']) ?></td>
                        <td><?= e($r['admission_number'] ?? '') ?></td>
                        <td class="font-medium"><?= e($r['contestant_name']) ?></td>
                        <td><?= e($r['course_name'] ?? '') ?: '—' ?></td>
                        <td><?= e($r['division_name'] ?? '') ?: '—' ?></td>
                        <td><?= e($genderLabels[$r['gender'] ?? ''] ?? '—') ?></td>
                        <td><?= e($r['house_name'] ?? '') ?></td>
                        <td>
                            <select name="rows[<?= $cid ?>][position]" class="form-select !py-1.5 result-pos">
                                <option value="">— None —</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= e($pos) ?>" <?= $curPos === $pos ? 'selected' : '' ?>><?= e($posLabels[$pos] ?? $pos) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.5" name="rows[<?= $cid ?>][points]" value="<?= e((string) $curPts) ?>" class="form-input !py-1.5 result-pts">
                        </td>
                        <td>
                            <input type="text" name="rows[<?= $cid ?>][remarks]" value="<?= e((string) $curRem) ?>" class="form-input !py-1.5" placeholder="Optional">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noMatchRow" class="hidden"><td colspan="10" class="text-center py-8 text-slate-400">No matching contestants.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-4 py-3">
            <p class="text-sm text-slate-500"><span id="visibleCount"><?= count($registrations) ?></span> of <?= count($registrations) ?> registered contestant(s)</p>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Results</button>
        </div>
    </div>
</form>

<script>
window.POINTS = <?= json_encode($points) ?>;
window.RESULT = { save: <?= json_encode(url('results/' . (int) $instance['id'] . '/save')) ?> };
</script>
<script src="<?= e(asset('js/results.js')) ?>"></script>
<?php endif; ?>

<script>
(function () {
    var btn = document.querySelector('.pub-switch');
    if (!btn) return;
    btn.addEventListener('click', async function () {
        var on = btn.getAttribute('aria-checked') === 'true';
        var next = on ? 0 : 1;
        btn.disabled = true;
        var fd = new FormData(); fd.append('published', String(next));
        try {
            var res = await window.apiFetch(btn.dataset.publishUrl, { method: 'POST', body: fd });
            var pub = !!res.published;
            btn.setAttribute('aria-checked', pub ? 'true' : 'false');
            btn.classList.toggle('bg-emerald-500', pub);
            btn.classList.toggle('bg-slate-300', !pub);
            var knob = btn.querySelector('.pub-knob');
            knob.classList.toggle('translate-x-6', pub);
            knob.classList.toggle('translate-x-1', !pub);
            btn.title = pub ? 'Published — click to unpublish' : 'Unpublished — click to publish';
            window.Toast.show(res.message || 'Saved.', 'success');
        } catch (err) {
            window.Toast.show(err.message || 'Failed to update.', 'error');
        }
        btn.disabled = false;
    });
})();
</script>
