<?php
/** @var array $rows @var int $total @var int $pages @var int $page @var array $actions @var array $tables @var array $filters */
$qs = fn(array $o) => '?' . http_build_query(array_filter(array_merge($_GET, $o), fn($v) => $v !== '' && $v !== null));
?>
<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-clipboard-list"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Audit Logs</h1>
            <p class="text-sm text-slate-500"><?= number_format($total) ?> entries</p>
        </div>
    </div>
    <a href="<?= e(url('audit-logs/export' . $qs([]))) ?>" class="btn btn-secondary"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <form method="get" class="grid gap-3 md:grid-cols-6 p-4 border-b border-slate-100">
        <div class="md:col-span-2">
            <label class="form-label">User</label>
            <input type="text" name="user" value="<?= e($filters['user']) ?>" placeholder="Name or email" class="form-input">
        </div>
        <div>
            <label class="form-label">Action</label>
            <select name="action" class="form-select">
                <option value="">All</option>
                <?php foreach ($actions as $a): ?><option value="<?= e($a['action']) ?>" <?= $filters['action'] === $a['action'] ? 'selected' : '' ?>><?= e($a['action']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Table</label>
            <select name="table" class="form-select">
                <option value="">All</option>
                <?php foreach ($tables as $t): ?><option value="<?= e($t['table_name']) ?>" <?= $filters['table'] === $t['table_name'] ? 'selected' : '' ?>><?= e($t['table_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">From</label>
            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>" class="form-input">
        </div>
        <div>
            <label class="form-label">To</label>
            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>" class="form-input">
        </div>
        <div class="md:col-span-6 flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="<?= e(url('audit-logs')) ?>" class="btn btn-secondary">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>IP</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center py-10 text-slate-400">No log entries.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td class="whitespace-nowrap text-xs"><?= e(format_datetime($r['created_at'])) ?></td>
                        <td><?= e($r['user_name'] ?? 'System') ?><?php if (!empty($r['email'])): ?><div class="text-xs text-slate-400"><?= e($r['email']) ?></div><?php endif; ?></td>
                        <td><span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"><?= e($r['action']) ?></span></td>
                        <td class="text-sm"><?= e($r['table_name'] ?? '') ?></td>
                        <td class="text-sm"><?= e((string) ($r['record_id'] ?? '')) ?></td>
                        <td class="text-xs text-slate-500"><?= e($r['ip_address'] ?? '') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="flex items-center justify-center gap-1 border-t border-slate-100 px-4 py-3">
        <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
            <a href="<?= e($qs(['page' => $p])) ?>" class="pg-btn <?= $p === $page ? 'bg-primary text-white border-primary' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<style>.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:2rem;height:2rem;padding:0 .5rem;border:1px solid #e2e8f0;border-radius:.5rem;font-size:.8rem;color:#334155;background:#fff}.pg-btn:hover{background:#f1f5f9}</style>
