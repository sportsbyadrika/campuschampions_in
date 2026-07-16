<?php
/** @var array $instances @var array $meets @var int $meetId @var array $instanceChoices @var int $instanceId @var string $published @var string $entered @var string $reg @var bool $canEnter @var bool $canPublish */
?>
<div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-ranking-star"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Results</h1>
        <p class="text-sm text-slate-500">Select an event instance to enter or view results.</p>
    </div>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <form method="get" class="flex flex-wrap items-end gap-3 p-4 border-b border-slate-100">
        <div>
            <label class="form-label">Meet</label>
            <select name="meet_id" class="form-select" onchange="this.form.submit()">
                <option value="">All meets</option>
                <?php foreach ($meets as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $meetId === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Event Instance</label>
            <select name="instance_id" class="form-select" onchange="this.form.submit()">
                <option value="">All event instances</option>
                <?php foreach ($instanceChoices as $ei): ?>
                    <option value="<?= (int) $ei['id'] ?>" <?= $instanceId === (int) $ei['id'] ? 'selected' : '' ?>><?= e($ei['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Published</label>
            <select name="published" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="1" <?= $published === '1' ? 'selected' : '' ?>>Published</option>
                <option value="0" <?= $published === '0' ? 'selected' : '' ?>>Unpublished</option>
            </select>
        </div>
        <div>
            <label class="form-label">Results</label>
            <select name="entered" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="1" <?= $entered === '1' ? 'selected' : '' ?>>Entered</option>
                <option value="0" <?= $entered === '0' ? 'selected' : '' ?>>Not entered</option>
            </select>
        </div>
        <div>
            <label class="form-label">Registrations</label>
            <select name="reg" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="1" <?= $reg === '1' ? 'selected' : '' ?>>Has registrations</option>
                <option value="0" <?= $reg === '0' ? 'selected' : '' ?>>No registrations</option>
            </select>
        </div>
        <?php if ($meetId || $instanceId || $published !== '' || $entered !== '' || $reg !== ''): ?>
            <a href="<?= e(url('results?meet_id=&instance_id=&published=&entered=&reg=')) ?>" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th>Instance</th><th>Event</th><th>Category</th><th>Meet</th><th>Date</th>
                <th>Registered</th><th>Results</th>
                <?php if ($canPublish): ?><th class="text-center">Publish</th><?php endif; ?>
                <th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                <?php if (empty($instances)): ?>
                    <tr><td colspan="<?= $canPublish ? 9 : 8 ?>" class="text-center py-10 text-slate-400">
                        <i class="fa-solid fa-inbox text-2xl mb-2 block"></i>
                        <?php if (\App\Core\Auth::role() === 'event_user'): ?>
                            You have no assigned events yet.
                            <div class="text-xs mt-1">Ask your campus admin to assign you to event instances — assigned events will appear here for result entry.</div>
                        <?php else: ?>
                            No event instances found<?= (int) $meetId > 0 ? ' for the selected meet' : '' ?>.
                        <?php endif; ?>
                    </td></tr>
                <?php else: foreach ($instances as $i): ?>
                    <tr>
                        <td class="font-medium"><?= e($i['label']) ?></td>
                        <td><?= e($i['discipline_name']) ?> · <?= e($i['event_name']) ?></td>
                        <td><?= e($i['category_name']) ?></td>
                        <td><?= e($i['meet_title']) ?></td>
                        <td><?= e(format_date($i['instance_date'])) ?></td>
                        <td><span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"><?= (int) $i['reg_count'] ?></span></td>
                        <td>
                            <?php if ((int) $i['result_count'] > 0): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"><?= (int) $i['result_count'] ?> entered</span>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">None</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($canPublish): $pub = (int) ($i['results_published'] ?? 0) === 1; ?>
                        <td class="text-center">
                            <button type="button" role="switch" aria-checked="<?= $pub ? 'true' : 'false' ?>"
                                    class="pub-switch relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors <?= $pub ? 'bg-emerald-500' : 'bg-slate-300' ?>"
                                    data-publish-url="<?= e(url('results/' . (int) $i['id'] . '/publish')) ?>"
                                    title="<?= $pub ? 'Published — click to unpublish' : 'Unpublished — click to publish' ?>">
                                <span class="pub-knob inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform <?= $pub ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                            </button>
                        </td>
                        <?php endif; ?>
                        <td class="text-right whitespace-nowrap">
                            <a href="<?= e(url('results/' . (int) $i['id'] . '/export')) ?>" class="text-slate-500 hover:text-primary px-2" title="Export CSV"><i class="fa-solid fa-file-csv"></i></a>
                            <?php if (can('super_admin', 'campus_admin')): ?>
                                <a href="<?= e(url('results/' . (int) $i['id'] . '/assign')) ?>" class="text-slate-500 hover:text-primary px-2" title="Assign users"><i class="fa-solid fa-user-gear"></i></a>
                            <?php endif; ?>
                            <?php if ($canEnter): ?>
                                <a href="<?= e(url('results/' . (int) $i['id'] . '/entry')) ?>" class="btn btn-primary btn-sm !inline-flex" title="Enter results"><i class="fa-solid fa-pen"></i> Enter</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>.btn-sm{padding:.35rem .7rem;font-size:.8rem}</style>
<?php if ($canPublish): ?>
<script>
(function () {
    document.querySelectorAll('.pub-switch').forEach(function (btn) {
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
    });
})();
</script>
<?php endif; ?>
