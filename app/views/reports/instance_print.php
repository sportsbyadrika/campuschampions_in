<?php
/** @var array $report */
$d = $report['detail'];
$institution = $report['institution'];
$participants = $report['participants'];
$id = (int) $d['id'];
$classOf = fn($c, $dv) => trim(($c ?? '') . ' / ' . ($dv ?? ''), ' /');
$gender = fn($g) => ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$g] ?? '';
$pdfBase = url('reports/instance-contestants/' . $id . '/pdf');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants — <?= e($d['label']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 0; color: #111827; background: #f1f5f9; }
        .toolbar { position: sticky; top: 0; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; background: #fff; border-bottom: 1px solid #e2e8f0; padding: .75rem 1rem; }
        .toolbar .grow { flex: 1; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: .5rem; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .875rem; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #2563EB; color: #fff; border-color: #2563EB; }
        select { padding: .45rem .6rem; border: 1px solid #cbd5e1; border-radius: .5rem; font-size: .875rem; }
        .sheet { background: #fff; margin: 1rem auto; padding: 1.25rem 1.5rem; max-width: 1000px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }           /* repeat heading on every printed page */
        tr { page-break-inside: avoid; }
        .doc-head { padding: 0 0 .5rem; border: none; }
        .discipline { font-size: 1.4rem; font-weight: 800; text-align: center; text-transform: uppercase; letter-spacing: .03em; }
        .subhead { text-align: center; font-size: 1rem; font-weight: 600; margin-top: .15rem; }
        .subhead small { display:block; font-weight: 400; color: #475569; font-size: .85rem; margin-top: .1rem; }
        th.col, td.col { border: 1px solid #94a3b8; padding: .45rem .5rem; font-size: .85rem; }
        th.col { background: #eff6ff; text-align: left; }
        td.remarks { width: 22%; }
        .sl { width: 7%; text-align: center; }
        .num { width: 12%; }
        .gen { width: 10%; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        @page { size: A4 portrait; margin: 12mm; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="grow" style="font-weight:600;">Participants — <?= e($d['label']) ?> <span style="color:#64748b;font-weight:400;">(<?= count($participants) ?>)</span></div>
        <label>Orientation:
            <select id="orient">
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
            </select>
        </label>
        <a class="btn" id="pdfBtn" href="<?= e($pdfBase . '?orientation=portrait') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>

    <div class="sheet">
        <table>
            <thead>
                <tr><th class="doc-head" colspan="6">
                    <div class="discipline"><?= e($d['meet_title']) ?></div>
                    <div class="subhead">
                        <?= e($institution) ?>
                        <small><strong><?= e($d['discipline_name']) ?></strong> &mdash; <?= e($d['event_name']) ?> &middot; <?= e($d['category_name']) ?> &middot; <?= e($d['label']) ?></small>
                    </div>
                </th></tr>
                <tr>
                    <th class="col sl">Sl No</th>
                    <th class="col num">Unique #</th>
                    <th class="col">Name</th>
                    <th class="col">Class/Division</th>
                    <th class="col gen">Gender</th>
                    <th class="col remarks">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($participants)): ?>
                    <tr><td class="col" colspan="6" style="text-align:center;color:#64748b;">No participants registered.</td></tr>
                <?php else: $sl = 0; foreach ($participants as $p): $sl++; ?>
                    <tr>
                        <td class="col sl"><?= $sl ?></td>
                        <td class="col num"><?= e($p['unique_number']) ?></td>
                        <td class="col"><?= e($p['name']) ?></td>
                        <td class="col"><?= e($classOf($p['course_name'], $p['division_name'])) ?></td>
                        <td class="col gen"><?= e($gender($p['gender'])) ?></td>
                        <td class="col remarks">&nbsp;</td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        var pageStyle = document.querySelector('style');
        var orient = document.getElementById('orient');
        var pdfBtn = document.getElementById('pdfBtn');
        var base = <?= json_encode($pdfBase) ?>;
        orient.addEventListener('change', function () {
            var o = orient.value;
            // swap @page size for browser print
            document.styleSheets[0].insertRule('@page { size: A4 ' + o + '; margin: 12mm; }', document.styleSheets[0].cssRules.length);
            pdfBtn.href = base + '?orientation=' + o;
        });
    </script>
</body>
</html>
