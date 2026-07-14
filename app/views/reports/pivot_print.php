<?php
/**
 * Printable pivot table (opens in a new tab).
 * @var array $pivot  keys: title, main, sub, line(html), pdfBase, leadHeaders, houses, rows, totals
 */
$lead = $pivot['leadHeaders'];
$houses = $pivot['houses'];
$rows = $pivot['rows'];
$totals = $pivot['totals'];
$pdfBase = $pivot['pdfBase'];
$n = count($lead) + count($houses) + 2;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pivot['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 0; color: #111827; background: #f1f5f9; }
        .toolbar { position: sticky; top: 0; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; background: #fff; border-bottom: 1px solid #e2e8f0; padding: .75rem 1rem; }
        .toolbar .grow { flex: 1; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: .5rem; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .875rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #2563EB; color: #fff; border-color: #2563EB; }
        select { padding: .45rem .6rem; border: 1px solid #cbd5e1; border-radius: .5rem; font-size: .875rem; }
        .sheet { background: #fff; margin: 1rem auto; padding: 1.25rem 1.5rem; max-width: 1150px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .doc-head { padding: 0 0 .5rem; border: none; }
        .discipline { font-size: 1.4rem; font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: .03em; }
        .subhead { text-align: center; font-size: 1rem; font-weight: 600; margin-top: .15rem; }
        .subhead small { display:block; font-weight: 400; color: #475569; font-size: .85rem; margin-top: .1rem; }
        th.col, td.col { border: 1px solid #94a3b8; padding: .4rem .5rem; font-size: .82rem; }
        th.col { background: #eff6ff; text-align: left; }
        td.c, th.c { text-align: center; }
        td.zero { color: #cbd5e1; }
        .totrow td { background: #f1f5f9; font-weight: 700; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print { body { background: #fff; } .toolbar { display: none; } .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="grow" style="font-weight:600;"><?= e($pivot['title']) ?></div>
        <label>Orientation:
            <select id="orient">
                <option value="landscape" selected>Landscape</option>
                <option value="portrait">Portrait</option>
            </select>
        </label>
        <a class="btn" id="pdfBtn" href="<?= e($pdfBase . (str_contains($pdfBase, '?') ? '&' : '?') . 'orientation=landscape') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>

    <div class="sheet">
        <table>
            <thead>
                <tr><th class="doc-head" colspan="<?= $n ?>">
                    <div class="discipline"><?= e($pivot['main']) ?></div>
                    <div class="subhead"><?= e($pivot['sub']) ?><small><?= $pivot['line'] ?></small></div>
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
                    <tr><td class="col" colspan="<?= $n ?>" style="text-align:center;color:#64748b;">No data for this meet.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($r['labels'] as $lbl): ?><td class="col"><?= e($lbl) ?></td><?php endforeach; ?>
                            <?php foreach ($houses as $house): $v = (int) ($r['counts'][(int) $house['id']] ?? 0); ?>
                                <td class="col c <?= $v === 0 ? 'zero' : '' ?>"><?= $v ?></td>
                            <?php endforeach; ?>
                            <td class="col c <?= (int) $r['unassigned'] === 0 ? 'zero' : '' ?>"><?= (int) $r['unassigned'] ?></td>
                            <td class="col c"><strong><?= (int) $r['total'] ?></strong></td>
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
    </div>

    <script>
        var orient = document.getElementById('orient');
        var pdfBtn = document.getElementById('pdfBtn');
        var base = <?= json_encode($pdfBase) ?>;
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        orient.addEventListener('change', function () {
            var o = orient.value;
            document.styleSheets[0].insertRule('@page { size: A4 ' + o + '; margin: 12mm; }', document.styleSheets[0].cssRules.length);
            pdfBtn.href = base + sep + 'orientation=' + o;
        });
    </script>
</body>
</html>
