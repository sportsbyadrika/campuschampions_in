<?php
/**
 * Generic master-data list page.
 * @var array $cfg
 * @var array $result
 * @var array $filters
 * @var string $search
 * @var bool $canManage
 */
$rows = $result['rows'];
$columns = $cfg['columns'];
$showCampus = !empty($cfg['showCampus']) && \App\Core\Auth::isSuperAdmin();
$route = $cfg['route'];
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <i class="fa-solid <?= e($cfg['icon'] ?? 'fa-table') ?>"></i>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= e($cfg['entityPlural']) ?></h1>
            <p class="text-sm text-slate-500"><?= number_format($result['total']) ?> record(s)</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <?php foreach (($cfg['extraActions'] ?? []) as $action): if (!empty($action['manage']) && !$canManage) continue; ?>
            <a href="<?= e(url($action['url'])) ?>" class="btn btn-secondary"><i class="fa-solid <?= e($action['icon']) ?>"></i> <?= e($action['label']) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(url($route . '/export?' . http_build_query($_GET))) ?>" class="btn btn-secondary">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <?php if ($canManage): ?>
        <button type="button" class="btn btn-primary" data-crud-add>
            <i class="fa-solid fa-plus"></i> Add <?= e($cfg['entity']) ?>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Toolbar: search + filters -->
<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <form method="get" class="flex flex-col md:flex-row md:items-end gap-3 p-4 border-b border-slate-100">
        <div class="flex-1">
            <label class="form-label">Search</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($cfg['entityPlural'])) ?>..." class="form-input pl-9">
            </div>
        </div>
        <?php foreach (($cfg['filters'] ?? []) as $field => $filter): ?>
            <div>
                <label class="form-label"><?= e($filter['label']) ?></label>
                <select name="<?= e($field) ?>" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($filter['options'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= ($filters[$field] ?? '') === (string) $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="<?= e(url($route)) ?>" class="btn btn-secondary">Clear</a>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th><?= e($col['label']) ?></th>
                    <?php endforeach; ?>
                    <?php if ($showCampus): ?><th>Campus</th><?php endif; ?>
                    <?php if ($canManage): ?><th class="text-right">Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= count($columns) + ($showCampus ? 1 : 0) + ($canManage ? 1 : 0) ?>" class="text-center py-10 text-slate-400">
                            <i class="fa-solid fa-inbox text-2xl mb-2 block"></i>
                            No records found.
                        </td>
                    </tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($columns as $col):
                            $val = $row[$col['key']] ?? '';
                            $type = $col['type'] ?? 'text';
                        ?>
                            <td>
                                <?php if ($type === 'badge'): ?>
                                    <?= status_badge((string) $val) ?>
                                <?php elseif ($type === 'role'): ?>
                                    <?= e(\App\Core\Auth::roleLabel((string) $val)) ?>
                                <?php elseif ($type === 'date'): ?>
                                    <?= e(format_date((string) $val)) ?>
                                <?php elseif ($type === 'datetime'): ?>
                                    <?= e(format_datetime((string) $val)) ?>
                                <?php elseif ($type === 'yesno'): ?>
                                    <?= ((string) $val === '1') ? 'Yes' : 'No' ?>
                                <?php elseif ($type === 'color'): ?>
                                    <span class="inline-flex items-center gap-2"><span class="inline-block h-4 w-4 rounded-full border border-slate-200" style="background:<?= e((string)$val) ?>"></span><?= e((string) $val) ?></span>
                                <?php else: ?>
                                    <?= e((string) $val) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php if ($showCampus): ?><td><?= e($row['campus_name'] ?? '') ?></td><?php endif; ?>
                        <?php if ($canManage): ?>
                        <td class="text-right whitespace-nowrap">
                            <?php if (!empty($cfg['rowLink'])): ?>
                                <a href="<?= e(url(str_replace('{id}', (string) (int) $row['id'], $cfg['rowLink']['urlPattern']))) ?>" class="text-slate-500 hover:text-primary px-2" title="<?= e($cfg['rowLink']['title'] ?? 'Open') ?>"><i class="fa-solid <?= e($cfg['rowLink']['icon']) ?>"></i></a>
                            <?php endif; ?>
                            <button type="button" class="text-slate-500 hover:text-primary px-2" data-crud-edit="<?= (int) $row['id'] ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button type="button" class="text-slate-500 hover:text-rose-600 px-2" data-crud-delete="<?= (int) $row['id'] ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php include APP_PATH . '/views/partials/pagination.php'; ?>
</div>

<?php if ($canManage): ?>
<!-- ============ Add/Edit Modal ============ -->
<div id="crudModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="crudModalTitle">
    <div class="modal-panel" role="document">
        <form id="crudForm" novalidate>
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 id="crudModalTitle" class="text-lg font-semibold text-slate-900">Add <?= e($cfg['entity']) ?></h2>
                <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" name="id" id="crudId" value="">
                <?php foreach ($cfg['fields'] as $field):
                    $name = $field['name'];
                    $req = !empty($field['required']);
                ?>
                    <div>
                        <label class="form-label" for="f_<?= e($name) ?>"><?= e($field['label']) ?><?= $req ? ' <span class="text-rose-500">*</span>' : '' ?></label>
                        <?php if ($field['type'] === 'textarea'): ?>
                            <textarea id="f_<?= e($name) ?>" name="<?= e($name) ?>" rows="3" class="form-textarea" data-field="<?= e($name) ?>"></textarea>
                        <?php elseif ($field['type'] === 'select'): ?>
                            <select id="f_<?= e($name) ?>" name="<?= e($name) ?>" class="form-select" data-field="<?= e($name) ?>">
                                <?php foreach ($field['options'] as $val => $label): ?>
                                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['type'] === 'color'): ?>
                            <div class="flex items-center gap-2">
                                <input type="color" id="f_<?= e($name) ?>_picker" value="#2563EB" class="h-9 w-12 rounded border border-slate-300">
                                <input type="text" id="f_<?= e($name) ?>" name="<?= e($name) ?>" class="form-input" placeholder="#RRGGBB" data-field="<?= e($name) ?>">
                            </div>
                        <?php else: ?>
                            <input type="<?= e($field['type']) ?>" id="f_<?= e($name) ?>" name="<?= e($name) ?>" class="form-input" data-field="<?= e($name) ?>" <?= !empty($field['placeholder']) ? 'placeholder="' . e($field['placeholder']) . '"' : '' ?>>
                        <?php endif; ?>
                        <p class="form-error hidden" data-error="<?= e($name) ?>"></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> <span data-submit-label>Save</span></button>
            </div>
        </form>
    </div>
</div>

<!-- ============ Delete Confirm Modal ============ -->
<div id="deleteModal" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal-panel !max-w-md" role="document">
        <div class="px-6 py-5 text-center">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600"><i class="fa-solid fa-triangle-exclamation text-xl"></i></span>
            <h2 class="mt-3 text-lg font-semibold text-slate-900">Delete <?= e($cfg['entity']) ?>?</h2>
            <p class="mt-1 text-sm text-slate-500">This action cannot be undone.</p>
        </div>
        <div class="flex justify-center gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDelete"><i class="fa-solid fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<script>
window.CRUD_CONFIG = {
    route: <?= json_encode(url($route)) ?>,
    entity: <?= json_encode($cfg['entity']) ?>,
    fields: <?= json_encode(array_map(fn($f) => $f['name'], $cfg['fields'])) ?>
};
</script>
<script src="<?= e(asset('js/crud.js')) ?>"></script>
<?php endif; ?>
