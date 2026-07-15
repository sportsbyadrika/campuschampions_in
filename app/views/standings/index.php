<?php
/** @var array $meets @var int $meetId @var ?array $meet @var array $houses @var array $events @var array $disciplines @var array $courseDivisions */
$maxHouse = 0.0;
foreach ($houses as $h) { $maxHouse = max($maxHouse, (float) $h['total_points']); }
$medalRank = ['fa-trophy text-amber-400', 'fa-medal text-slate-400', 'fa-medal text-amber-700'];
$num = fn($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$classOf = fn($c, $d) => trim(($c ?? '') . ' / ' . ($d ?? ''), ' /');
// Render a table cell of winners for one position (stacked, one per line).
$winnerCell = function (array $list) use ($classOf) {
    if (empty($list)) {
        return '<span class="text-slate-300">—</span>';
    }
    $out = '';
    foreach ($list as $w) {
        $meta = array_filter([$w['house_name'] ?? '', $classOf($w['course_name'], $w['division_name'])]);
        $out .= '<div class="mb-1.5 last:mb-0">'
            . '<div class="text-sm font-medium text-slate-900">' . e($w['contestant_name']) . '</div>';
        if ($meta) {
            $out .= '<div class="text-xs text-slate-500">' . e(implode(' · ', $meta)) . '</div>';
        }
        $out .= '</div>';
    }
    return $out;
};
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

<!-- ===================== Panel 2: Prize Winners by Event (table) ===================== -->
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between p-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-900">Prize Winners by Event</h2>
        <a href="<?= e(url('standings/export/events?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th class="w-1/4">Event Instance</th>
                <th><i class="fa-solid fa-trophy text-amber-400 mr-1"></i>First</th>
                <th><i class="fa-solid fa-medal text-slate-400 mr-1"></i>Second</th>
                <th><i class="fa-solid fa-medal text-amber-700 mr-1"></i>Third</th>
            </tr></thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="4" class="text-center py-10 text-slate-400">No prize winners recorded yet.</td></tr>
                <?php else: foreach ($events as $ev): ?>
                    <tr>
                        <td class="align-top">
                            <div class="font-medium text-slate-900"><?= e($ev['label']) ?></div>
                            <div class="text-xs text-slate-500"><?= e($ev['discipline']) ?> &middot; <?= e($ev['event']) ?> &middot; <?= e($ev['category']) ?></div>
                        </td>
                        <td class="align-top"><?= $winnerCell($ev['first']) ?></td>
                        <td class="align-top"><?= $winnerCell($ev['second']) ?></td>
                        <td class="align-top"><?= $winnerCell($ev['third']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <!-- ===================== Panel 3: By Discipline ===================== -->
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

    <!-- ===================== Panel 4: By Course / Division ===================== -->
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">By Course / Division</h2>
            <a href="<?= e(url('standings/export/course-divisions?meet_id=' . $meetId)) ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Export</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr>
                    <th>Course / Division</th>
                    <th class="text-center" title="First"><i class="fa-solid fa-trophy text-amber-400"></i></th>
                    <th class="text-center" title="Second"><i class="fa-solid fa-medal text-slate-400"></i></th>
                    <th class="text-center" title="Third"><i class="fa-solid fa-medal text-amber-700"></i></th>
                    <th class="text-right">Points</th>
                </tr></thead>
                <tbody>
                    <?php if (empty($courseDivisions)): ?>
                        <tr><td colspan="5" class="text-center py-6 text-slate-400">No results recorded yet.</td></tr>
                    <?php else: foreach ($courseDivisions as $cd): ?>
                        <tr>
                            <td class="font-medium"><?= e($classOf($cd['course_name'], $cd['division_name']) ?: '—') ?></td>
                            <td class="text-center"><?= (int) $cd['golds'] ?></td>
                            <td class="text-center"><?= (int) $cd['silvers'] ?></td>
                            <td class="text-center"><?= (int) $cd['bronzes'] ?></td>
                            <td class="text-right font-semibold text-slate-900"><?= e($num($cd['total_points'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<style>.btn-sm{padding:.35rem .7rem;font-size:.8rem}</style>
