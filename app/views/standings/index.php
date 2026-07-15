<?php
/** @var array $meets @var int $meetId @var ?array $meet @var array $houses @var array $events @var array $disciplines */
$maxHouse = 0.0;
foreach ($houses as $h) { $maxHouse = max($maxHouse, (float) $h['total_points']); }
$medalRank = ['fa-trophy text-amber-400', 'fa-medal text-slate-400', 'fa-medal text-amber-700'];
$posMeta = [
    'first'  => ['1st', 'bg-amber-100 text-amber-800'],
    'second' => ['2nd', 'bg-slate-200 text-slate-700'],
    'third'  => ['3rd', 'bg-orange-100 text-orange-800'],
];
$num = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$classOf = fn($c, $d) => trim(($c ?? '') . ' / ' . ($d ?? ''), ' /');
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-medal"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Championship Standings</h1>
            <p class="text-sm text-slate-500">House points, prize winners and discipline breakdown.</p>
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
    <div class="mt-10 text-center text-slate-400"><i class="fa-solid fa-medal text-3xl mb-2 block"></i>Select a meet to view its standings.</div>
<?php else: ?>

<!-- ===================== Panel 1: House Standings ===================== -->
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between p-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-900">House Standings</h2>
        <a href="<?= e(url('standings/export/houses?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
    </div>
    <div class="p-4 space-y-2.5">
        <?php if (empty($houses)): ?>
            <p class="text-center py-6 text-slate-400">No results recorded yet.</p>
        <?php else: foreach ($houses as $idx => $h):
            $color = $h['color_code'] ?: '#2563EB';
            $pct = $maxHouse > 0 ? ((float) $h['total_points'] / $maxHouse) * 100 : 0;
        ?>
            <div class="flex items-center gap-3">
                <div class="w-6 text-center">
                    <?php if ($idx < 3): ?><i class="fa-solid <?= $medalRank[$idx] ?> text-sm"></i><?php else: ?><span class="text-slate-400 text-xs"><?= $idx + 1 ?></span><?php endif; ?>
                </div>
                <div class="w-28 shrink-0 truncate font-medium text-sm flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-full border border-slate-200" style="background: <?= e($color) ?>"></span>
                    <?= e($h['name']) ?>
                </div>
                <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-2.5 rounded-full" style="width: <?= $pct ?>%; background: <?= e($color) ?>"></div>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 whitespace-nowrap w-28 justify-end">
                    <span title="First"><i class="fa-solid fa-trophy text-amber-400"></i> <?= (int) $h['golds'] ?></span>
                    <span title="Second"><i class="fa-solid fa-medal text-slate-400"></i> <?= (int) $h['silvers'] ?></span>
                    <span title="Third"><i class="fa-solid fa-medal text-amber-700"></i> <?= (int) $h['bronzes'] ?></span>
                </div>
                <div class="w-16 text-right font-semibold text-slate-900 text-sm"><?= e($num($h['total_points'])) ?> pts</div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <!-- ===================== Panel 2: Prize winners by event instance ===================== -->
    <div class="lg:col-span-2 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">Prize Winners by Event</h2>
            <a href="<?= e(url('standings/export/events?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
        </div>
        <div class="p-4 space-y-4 max-h-[36rem] overflow-y-auto">
            <?php if (empty($events)): ?>
                <p class="text-center py-6 text-slate-400">No prize winners recorded yet.</p>
            <?php else: foreach ($events as $ev): ?>
                <div class="rounded-lg border border-slate-200">
                    <div class="px-3 py-2 border-b border-slate-100 bg-slate-50 rounded-t-lg">
                        <div class="font-medium text-slate-900 text-sm"><?= e($ev['label']) ?></div>
                        <div class="text-xs text-slate-500"><?= e($ev['discipline']) ?> &middot; <?= e($ev['event']) ?> &middot; <?= e($ev['category']) ?></div>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <?php foreach ($ev['winners'] as $w): [$plabel, $pcls] = $posMeta[$w['position']] ?? ['', 'bg-slate-100']; ?>
                            <div class="flex items-center gap-3 px-3 py-2">
                                <span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold <?= $pcls ?> w-9 text-center"><?= $plabel ?></span>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-slate-900 truncate"><?= e($w['contestant_name']) ?></div>
                                    <div class="text-xs text-slate-500 truncate">
                                        <?php $meta = array_filter([$w['house_name'] ?? '', $classOf($w['course_name'], $w['division_name'])]); ?>
                                        <?= e(implode(' · ', $meta)) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ===================== Panel 3: Discipline breakdown ===================== -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">By Discipline</h2>
            <a href="<?= e(url('standings/export/disciplines?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th>Discipline</th>
                    <th class="text-center" title="First"><i class="fa-solid fa-trophy text-amber-400"></i></th>
                    <th class="text-center" title="Second"><i class="fa-solid fa-medal text-slate-400"></i></th>
                    <th class="text-center" title="Third"><i class="fa-solid fa-medal text-amber-700"></i></th>
                    <th class="text-right">Points</th>
                </tr></thead>
                <tbody>
                    <?php if (empty($disciplines)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-slate-400">No results recorded yet.</td></tr>
                    <?php else: foreach ($disciplines as $d): ?>
                        <tr>
                            <td class="font-medium"><?= e($d['discipline_name']) ?></td>
                            <td class="text-center"><?= (int) $d['golds'] ?></td>
                            <td class="text-center"><?= (int) $d['silvers'] ?></td>
                            <td class="text-center"><?= (int) $d['bronzes'] ?></td>
                            <td class="text-right font-semibold text-slate-900"><?= e($num($d['total_points'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<style>.btn-sm{padding:.35rem .7rem;font-size:.8rem}</style>
