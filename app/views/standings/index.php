<?php
/** @var array $meets @var int $meetId @var ?array $meet @var array $houses @var array $individuals */
$maxHouse = 0;
foreach ($houses as $h) { $maxHouse = max($maxHouse, (float) $h['total_points']); }
$medal = ['fa-trophy text-amber-400', 'fa-medal text-slate-400', 'fa-medal text-amber-700'];
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-medal"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Championship Standings</h1>
            <p class="text-sm text-slate-500">House and individual points for a meet.</p>
        </div>
    </div>
    <form method="get" class="flex items-end gap-2">
        <div>
            <label class="form-label">Meet</label>
            <select name="meet_id" class="form-select" onchange="this.form.submit()">
                <option value="">— Select meet —</option>
                <?php foreach ($meets as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= $meetId === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if (!$meetId): ?>
    <div class="mt-10 text-center text-slate-400">
        <i class="fa-solid fa-medal text-3xl mb-2 block"></i>
        Select a meet to view its standings.
    </div>
<?php else: ?>

<!-- House standings -->
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between p-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-900">House Standings</h2>
        <a href="<?= e(url('standings/export/houses?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
    </div>
    <div class="p-4 space-y-3">
        <?php if (empty($houses)): ?>
            <p class="text-center py-6 text-slate-400">No results recorded yet.</p>
        <?php else: foreach ($houses as $idx => $h):
            $pct = $maxHouse > 0 ? ((float) $h['total_points'] / $maxHouse) * 100 : 0;
        ?>
            <div class="flex items-center gap-3">
                <div class="w-8 text-center">
                    <?php if ($idx < 3): ?><i class="fa-solid <?= $medal[$idx] ?>"></i><?php else: ?><span class="text-slate-400 text-sm"><?= $idx + 1 ?></span><?php endif; ?>
                </div>
                <div class="w-32 truncate font-medium flex items-center gap-2">
                    <span class="inline-block h-3 w-3 rounded-full border border-slate-200" style="background: <?= e($h['color_code'] ?: '#e2e8f0') ?>"></span>
                    <?= e($h['name']) ?>
                </div>
                <div class="flex-1 h-4 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-4 rounded-full bg-primary" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="w-20 text-right font-semibold text-slate-900"><?= e(rtrim(rtrim(number_format((float) $h['total_points'], 1), '0'), '.')) ?> pts</div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Individual standings -->
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between p-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-900">Individual Standings <span class="text-sm font-normal text-slate-400">(Top 50)</span></h2>
        <a href="<?= e(url('standings/export/individuals?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Rank</th><th>Unique #</th><th>Contestant</th><th>House</th><th>🥇</th><th>🥈</th><th>🥉</th><th class="text-right">Points</th></tr></thead>
            <tbody>
                <?php if (empty($individuals)): ?>
                    <tr><td colspan="8" class="text-center py-8 text-slate-400">No results recorded yet.</td></tr>
                <?php else: foreach ($individuals as $idx => $i): ?>
                    <tr>
                        <td class="font-semibold"><?= $idx + 1 ?></td>
                        <td><?= e($i['unique_number']) ?></td>
                        <td class="font-medium"><?= e($i['name']) ?></td>
                        <td><?= e($i['house_name'] ?? '') ?></td>
                        <td><?= (int) $i['golds'] ?></td>
                        <td><?= (int) $i['silvers'] ?></td>
                        <td><?= (int) $i['bronzes'] ?></td>
                        <td class="text-right font-semibold text-slate-900"><?= e(rtrim(rtrim(number_format((float) $i['total_points'], 1), '0'), '.')) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<style>.btn-sm{padding:.35rem .7rem;font-size:.8rem}</style>
