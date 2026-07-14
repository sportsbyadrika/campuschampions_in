<?php
/**
 * Report toolbar: back link, title, meet dropdown, print + CSV export.
 * Expects: $title, $meets, $meet (?array), and $reportPath is derived from the URI.
 */
use App\Core\Request;
$currentPath = Request::uri();
$exportQs = http_build_query(array_merge($_GET, ['export' => 'csv']));
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:block">
    <div class="flex items-center gap-3">
        <a href="<?= e(url('reports')) ?>" class="text-slate-400 hover:text-primary print:hidden"><i class="fa-solid fa-arrow-left"></i></a>
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary print:hidden"><i class="fa-solid fa-chart-column"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= e($title) ?></h1>
            <?php if ($meet): ?><p class="text-sm text-slate-500"><?= e($meet['title']) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="flex items-end gap-2 print:hidden">
        <form method="get" class="flex items-end gap-2">
            <div>
                <label class="form-label">Meet</label>
                <select name="meet_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— Select meet —</option>
                    <?php foreach ($meets as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= ($meet && (int) $meet['id'] === (int) $m['id']) ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php if ($meet): ?>
            <?php if (!empty($printUrl)): ?>
                <a href="<?= e($printUrl) ?>" target="_blank" rel="noopener" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Print</a>
            <?php else: ?>
                <button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Print</button>
            <?php endif; ?>
            <a href="<?= e(url(ltrim($currentPath, '/') . '?' . $exportQs)) ?>" class="btn btn-secondary"><i class="fa-solid fa-file-csv"></i> CSV</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$meet): ?>
    <div class="mt-10 text-center text-slate-400">
        <i class="fa-solid fa-arrow-up text-2xl mb-2 block"></i>
        Select a meet to generate this report.
    </div>
<?php endif; ?>
