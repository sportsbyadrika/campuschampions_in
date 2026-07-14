<?php
/**
 * Pivot table PDF (Dompdf) with repeating <thead> + "Page X of Y" footer.
 * @var array $pivot @var string $orientation
 */
use App\Core\Pdf;
$lead = $pivot['leadHeaders'];
$houses = $pivot['houses'];
$rows = $pivot['rows'];
$totals = $pivot['totals'];
$n = count($lead) + count($houses) + 2;
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 12mm 16mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 9px; }
    table { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .doc-head { padding-bottom: 6px; border: none; }
    .discipline { font-size: 16px; font-weight: bold; text-align: center; text-transform: uppercase; }
    .subhead { text-align: center; font-size: 11px; font-weight: bold; margin-top: 2px; }
    .subhead .sm { display: block; font-weight: normal; color: #475569; font-size: 9px; margin-top: 2px; }
    th.col, td.col { border: 0.6px solid #94a3b8; padding: 3px 4px; font-size: 9px; }
    th.col { background: #eff6ff; text-align: left; }
    td.c, th.c { text-align: center; }
    .totrow td { background: #f1f5f9; font-weight: bold; }
</style>
</head>
<body>
<table>
    <thead>
        <tr><th class="doc-head" colspan="<?= $n ?>">
            <div class="discipline"><?= e($pivot['main']) ?></div>
            <div class="subhead"><?= e($pivot['sub']) ?><span class="sm"><?= $pivot['line'] ?></span></div>
        </th></tr>
        <tr>
            <?php foreach ($lead as $h): ?><th class="col"><?= e($h) ?></th><?php endforeach; ?>
            <?php foreach ($houses as $house): ?><th class="col c"><?= e($house['name']) ?></th><?php endforeach; ?>
            <th class="col c">Unassigned</th>
            <th class="col c">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td class="col" colspan="<?= $n ?>" style="text-align:center;">No data for this meet.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <?php foreach ($r['labels'] as $lbl): ?><td class="col"><?= e($lbl) ?></td><?php endforeach; ?>
                    <?php foreach ($houses as $house): ?><td class="col c"><?= (int) ($r['counts'][(int) $house['id']] ?? 0) ?></td><?php endforeach; ?>
                    <td class="col c"><?= (int) $r['unassigned'] ?></td>
                    <td class="col c"><?= (int) $r['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="totrow">
                <?php for ($i = 0; $i < count($lead); $i++): ?><td class="col"><?= $i === count($lead) - 1 ? 'TOTAL' : '' ?></td><?php endfor; ?>
                <?php foreach ($houses as $house): ?><td class="col c"><?= (int) ($totals[(int) $house['id']] ?? 0) ?></td><?php endforeach; ?>
                <td class="col c"><?= (int) ($totals['__un'] ?? 0) ?></td>
                <td class="col c"><?= (int) ($totals['__row'] ?? 0) ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?= Pdf::pageNumberScript() ?>
</body>
</html>
