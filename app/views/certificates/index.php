<?php
/** @var array $instances @var array $meets @var int $meetId @var array $instanceChoices @var int $instanceId */
$selectedLabel = '';
foreach ($instanceChoices as $ei) {
    if ((int) $ei['id'] === $instanceId) { $selectedLabel = $ei['label']; break; }
}
?>
<div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-award"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Certificates</h1>
        <p class="text-sm text-slate-500">Choose an event instance to generate certificates.</p>
    </div>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <form method="get" class="flex flex-wrap items-end gap-3 p-4 border-b border-slate-100" id="certFilter">
        <div>
            <label class="form-label">Meet</label>
            <select name="meet_id" class="form-select" onchange="this.form.submit()">
                <option value="">All meets</option>
                <?php foreach ($meets as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $meetId === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="relative w-72 max-w-full" data-combo>
            <label class="form-label">Event Instance</label>
            <input type="hidden" name="instance_id" value="<?= (int) $instanceId ?>" data-combo-value>
            <div class="relative">
                <input type="text" class="form-input pr-8" placeholder="Search event instance…" value="<?= e($selectedLabel) ?>" data-combo-input autocomplete="off">
                <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-combo-clear title="Clear"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <ul class="combo-list hidden absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200" data-combo-list>
                <li class="combo-item cursor-pointer px-3 py-1.5 hover:bg-slate-100 text-slate-500" data-id="0" data-search="">All event instances</li>
                <?php foreach ($instanceChoices as $ei): ?>
                    <li class="combo-item cursor-pointer px-3 py-1.5 hover:bg-slate-100" data-id="<?= (int) $ei['id'] ?>" data-search="<?= e(mb_strtolower($ei['label'])) ?>"><?= e($ei['label']) ?></li>
                <?php endforeach; ?>
                <li class="combo-empty hidden px-3 py-2 text-slate-400">No match</li>
            </ul>
        </div>
        <a href="<?= e(url('certificate-templates')) ?>" class="btn btn-secondary ml-auto"><i class="fa-solid fa-pen-ruler"></i> Manage Templates</a>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Instance</th><th>Event</th><th>Meet</th><th>Results</th><th>Certificates</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                <?php if (empty($instances)): ?>
                    <tr><td colspan="6" class="text-center py-10 text-slate-400"><i class="fa-solid fa-inbox text-2xl mb-2 block"></i>No event instances found.</td></tr>
                <?php else: foreach ($instances as $i): ?>
                    <tr>
                        <td class="font-medium"><?= e($i['label']) ?></td>
                        <td><?= e($i['discipline_name']) ?> · <?= e($i['event_name']) ?></td>
                        <td><?= e($i['meet_title']) ?></td>
                        <td><span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"><?= (int) $i['result_count'] ?></span></td>
                        <td>
                            <?php if ((int) $i['cert_count'] > 0): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"><?= (int) $i['cert_count'] ?></span>
                            <?php else: ?><span class="text-xs text-slate-400">None</span><?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= e(url('certificates/' . (int) $i['id'] . '/generate')) ?>" class="btn btn-primary btn-sm !inline-flex" <?= (int) $i['result_count'] === 0 ? 'style="pointer-events:none;opacity:.5"' : '' ?>><i class="fa-solid fa-award"></i> Generate</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<style>.btn-sm{padding:.35rem .7rem;font-size:.8rem}</style>
<script>
(function () {
    var combo = document.querySelector('[data-combo]');
    if (!combo) return;
    var form = document.getElementById('certFilter');
    var input = combo.querySelector('[data-combo-input]');
    var hidden = combo.querySelector('[data-combo-value]');
    var list = combo.querySelector('[data-combo-list]');
    var items = Array.prototype.slice.call(list.querySelectorAll('.combo-item'));
    var empty = list.querySelector('.combo-empty');
    var clearBtn = combo.querySelector('[data-combo-clear]');

    function openList() { list.classList.remove('hidden'); }
    function closeList() { list.classList.add('hidden'); }
    function filter() {
        var q = input.value.trim().toLowerCase();
        var shown = 0;
        items.forEach(function (it) {
            if (it.dataset.id === '0') { it.classList.toggle('hidden', q !== ''); return; }
            var match = it.dataset.search.indexOf(q) >= 0;
            it.classList.toggle('hidden', !match);
            if (match) shown++;
        });
        if (empty) empty.classList.toggle('hidden', shown > 0 || q === '');
    }
    function pick(it) {
        hidden.value = it.dataset.id;
        input.value = it.dataset.id === '0' ? '' : it.textContent.trim();
        closeList();
        form.submit();
    }

    input.addEventListener('focus', function () { filter(); openList(); });
    input.addEventListener('input', function () { openList(); filter(); });
    items.forEach(function (it) { it.addEventListener('click', function () { pick(it); }); });
    clearBtn.addEventListener('click', function () {
        input.value = ''; hidden.value = '0';
        if (hidden.getAttribute('value') === '0') { /* no-op */ }
        form.submit();
    });
    document.addEventListener('click', function (e) { if (!combo.contains(e.target)) closeList(); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = items.filter(function (it) { return !it.classList.contains('hidden') && it.dataset.id !== '0'; })[0];
            if (first) pick(first);
        } else if (e.key === 'Escape') { closeList(); }
    });
})();
</script>
