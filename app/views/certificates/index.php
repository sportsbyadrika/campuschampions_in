<?php
/** @var array $instances @var array $meets @var int $meetId */
?>
<div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-award"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Certificates</h1>
        <p class="text-sm text-slate-500">Choose an event instance to generate certificates.</p>
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
