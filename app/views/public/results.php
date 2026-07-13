<?php
/** @var array $rows @var int $total @var int $pages @var int $page @var array $meets @var array $categories @var string $q @var int $meetId @var int $catId @var string $position */
$posLabels = ['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'participant' => 'Participant'];
$posClass = ['first' => 'bg-amber-100 text-amber-800', 'second' => 'bg-slate-200 text-slate-700', 'third' => 'bg-orange-100 text-orange-800', 'participant' => 'bg-blue-50 text-blue-700'];
$qs = function (array $overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
};
?>
<div class="flex items-center justify-between print:block">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Competition Results</h1>
        <p class="text-sm text-slate-500"><?= number_format($total) ?> result(s)</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary print:hidden"><i class="fa-solid fa-print"></i> Print</button>
</div>

<!-- Filters -->
<form method="get" class="mt-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 grid gap-3 md:grid-cols-5 print:hidden">
    <div class="md:col-span-2">
        <label class="form-label">Search</label>
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Contestant, unique #, or event" class="form-input pl-9">
        </div>
    </div>
    <div>
        <label class="form-label">Meet</label>
        <select name="meet_id" class="form-select">
            <option value="">All meets</option>
            <?php foreach ($meets as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= $meetId === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select">
            <option value="">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label">Position</label>
        <select name="position" class="form-select">
            <option value="">All</option>
            <?php foreach (['first' => '1st', 'second' => '2nd', 'third' => '3rd', 'participant' => 'Participant'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $position === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="md:col-span-5 flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
        <a href="<?= e(url('public-results')) ?>" class="btn btn-secondary">Clear</a>
    </div>
</form>

<!-- Results table -->
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th>Rank</th><th>Contestant</th><th>Institution</th><th>Event</th><th>Category</th><th>Meet</th>
            </tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center py-12 text-slate-400"><i class="fa-solid fa-magnifying-glass text-2xl mb-2 block"></i>No results found.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $posClass[$r['position']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e($posLabels[$r['position']] ?? $r['position']) ?></span></td>
                        <td><div class="font-medium text-slate-900"><?= e($r['contestant_name']) ?></div><div class="text-xs text-slate-500"><?= e($r['unique_number']) ?></div></td>
                        <td><?= e($r['institution_name']) ?></td>
                        <td><?= e($r['discipline_name']) ?> · <?= e($r['event_name']) ?></td>
                        <td><?= e($r['category_name']) ?></td>
                        <td><?= e($r['meet_title']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="flex items-center justify-center gap-1 border-t border-slate-100 px-4 py-3 print:hidden">
        <?php
        $start = max(1, $page - 2); $end = min($pages, $page + 2);
        if ($page > 1) echo '<a href="' . e($qs(['page' => $page - 1])) . '" class="pg-btn"><i class="fa-solid fa-chevron-left text-xs"></i></a>';
        if ($start > 1) echo '<a href="' . e($qs(['page' => 1])) . '" class="pg-btn">1</a>' . ($start > 2 ? '<span class="px-1 text-slate-400">…</span>' : '');
        for ($p = $start; $p <= $end; $p++) {
            echo '<a href="' . e($qs(['page' => $p])) . '" class="pg-btn ' . ($p === $page ? 'bg-primary text-white border-primary' : '') . '">' . $p . '</a>';
        }
        if ($end < $pages) echo ($end < $pages - 1 ? '<span class="px-1 text-slate-400">…</span>' : '') . '<a href="' . e($qs(['page' => $pages])) . '" class="pg-btn">' . $pages . '</a>';
        if ($page < $pages) echo '<a href="' . e($qs(['page' => $page + 1])) . '" class="pg-btn"><i class="fa-solid fa-chevron-right text-xs"></i></a>';
        ?>
    </div>
    <?php endif; ?>
</div>
<style>.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 .5rem;border:1px solid #e2e8f0;border-radius:.5rem;font-size:.8rem;color:#334155;background:#fff}.pg-btn:hover{background:#f1f5f9}</style>
