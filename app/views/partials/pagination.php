<?php
/**
 * Pagination + per-page selector.
 * @var array $result  (page, pages, total, perPage)
 */
$page    = $result['page'];
$pages   = $result['pages'];
$total   = $result['total'];
$perPage = $result['perPage'];

// Preserve existing query params except page
$query = $_GET;
unset($query['page']);
$baseQs = http_build_query($query);
$link = function (int $p) use ($baseQs): string {
    return '?' . ($baseQs ? $baseQs . '&' : '') . 'page=' . $p;
};

$from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to   = min($page * $perPage, $total);

// Window of page numbers
$start = max(1, $page - 2);
$end   = min($pages, $page + 2);
?>
<div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-100">
    <div class="flex items-center gap-3 text-sm text-slate-500">
        <span>Showing <strong><?= $from ?></strong>&ndash;<strong><?= $to ?></strong> of <strong><?= number_format($total) ?></strong></span>
        <form method="get" class="flex items-center gap-1" data-perpage-form>
            <?php foreach ($_GET as $k => $v): if ($k === 'per_page' || $k === 'page') continue; ?>
                <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <label class="text-slate-500">Show</label>
            <select name="per_page" class="form-select !py-1 !w-auto text-sm" onchange="this.form.submit()">
                <?php foreach ([10, 20, 25, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="flex items-center gap-1">
        <a href="<?= $page > 1 ? $link($page - 1) : '#' ?>" class="pg-btn <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>"><i class="fa-solid fa-chevron-left text-xs"></i></a>
        <?php if ($start > 1): ?>
            <a href="<?= $link(1) ?>" class="pg-btn">1</a>
            <?php if ($start > 2): ?><span class="px-1 text-slate-400">&hellip;</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= $link($p) ?>" class="pg-btn <?= $p === $page ? 'bg-primary text-white border-primary' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($end < $pages): ?>
            <?php if ($end < $pages - 1): ?><span class="px-1 text-slate-400">&hellip;</span><?php endif; ?>
            <a href="<?= $link($pages) ?>" class="pg-btn"><?= $pages ?></a>
        <?php endif; ?>
        <a href="<?= $page < $pages ? $link($page + 1) : '#' ?>" class="pg-btn <?= $page >= $pages ? 'pointer-events-none opacity-40' : '' ?>"><i class="fa-solid fa-chevron-right text-xs"></i></a>
    </nav>
    <?php endif; ?>
</div>
<style>
.pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:2rem; height:2rem; padding:0 .5rem; border:1px solid #e2e8f0; border-radius:.5rem; font-size:.8rem; color:#334155; background:#fff; }
.pg-btn:hover { background:#f1f5f9; }
</style>
