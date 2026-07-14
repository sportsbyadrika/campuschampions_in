<?php
/**
 * Generic participant list PDF (Dompdf). Repeating <thead> + "Page X of Y" footer.
 * @var array $report  keys: main, sub, line(html), columns, rows
 * @var string $orientation
 */
use App\Core\Pdf;
$cols = $report['columns'];
$rows = $report['rows'];
$n = count($cols);
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 12mm 16mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .doc-head { padding-bottom: 6px; border: none; }
    .discipline { font-size: 17px; font-weight: bold; text-align: center; text-transform: uppercase; }
    .subhead { text-align: center; font-size: 12px; font-weight: bold; margin-top: 2px; }
    .subhead .sm { display: block; font-weight: normal; color: #475569; font-size: 10px; margin-top: 2px; }
    th.col, td.col { border: 0.6px solid #94a3b8; padding: 4px 5px; font-size: 10px; }
    th.col { background: #eff6ff; text-align: left; }
    .sl { width: 6%; text-align: center; }
    .num { width: 12%; }
    .gen { width: 9%; }
    .remarks { width: 20%; }
</style>
</head>
<body>
<table>
    <thead>
        <tr><th class="doc-head" colspan="<?= $n ?>">
            <div class="discipline"><?= e($report['main']) ?></div>
            <div class="subhead"><?= e($report['sub']) ?><span class="sm"><?= $report['line'] ?></span></div>
        </th></tr>
        <tr>
            <?php foreach ($cols as $c): ?><th class="col <?= e($c['cls'] ?? '') ?>"><?= e($c['label']) ?></th><?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td class="col" colspan="<?= $n ?>" style="text-align:center;">No contestants.</td></tr>
        <?php else: $sl = 0; foreach ($rows as $row): $sl++; ?>
            <tr>
                <?php foreach ($cols as $c): $type = $c['type'] ?? 'text'; $cls = $c['cls'] ?? ''; ?>
                    <?php if ($type === 'sl'): ?><td class="col sl"><?= $sl ?></td>
                    <?php elseif ($type === 'blank'): ?><td class="col <?= e($cls) ?>">&nbsp;</td>
                    <?php else: ?><td class="col <?= e($cls) ?>"><?= e($row[$c['key']] ?? '') ?></td><?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<?= Pdf::pageNumberScript() ?>
</body>
</html>
