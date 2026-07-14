<?php
/** @var array $report @var string $orientation */
use App\Core\Pdf;
$d = $report['detail'];
$institution = $report['institution'];
$participants = $report['participants'];
$classOf = fn($c, $dv) => trim(($c ?? '') . ' / ' . ($dv ?? ''), ' /');
$gender = fn($g) => ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '';
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
    th.col, td.col { border: 0.6px solid #94a3b8; padding: 4px 5px; }
    th.col { background: #eff6ff; text-align: left; font-size: 10px; }
    td.col { font-size: 10px; }
    .num { width: 12%; }
    .gen { width: 10%; }
    .remarks { width: 22%; }
</style>
</head>
<body>
<table>
    <thead>
        <tr><th class="doc-head" colspan="5">
            <div class="discipline"><?= e($d['discipline_name']) ?></div>
            <div class="subhead">
                <?= e($institution) ?>
                <span class="sm"><?= e($d['event_name']) ?> &mdash; <?= e($d['category_name']) ?> &middot; <?= e($d['label']) ?></span>
            </div>
        </th></tr>
        <tr>
            <th class="col num">Unique #</th>
            <th class="col">Name</th>
            <th class="col">Class/Division</th>
            <th class="col gen">Gender</th>
            <th class="col remarks">Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($participants)): ?>
            <tr><td class="col" colspan="5" style="text-align:center;">No participants registered.</td></tr>
        <?php else: foreach ($participants as $p): ?>
            <tr>
                <td class="col num"><?= e($p['unique_number']) ?></td>
                <td class="col"><?= e($p['name']) ?></td>
                <td class="col"><?= e($classOf($p['course_name'], $p['division_name'])) ?></td>
                <td class="col gen"><?= e($gender($p['gender'])) ?></td>
                <td class="col remarks">&nbsp;</td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<?= Pdf::pageNumberScript() ?>
</body>
</html>
