<?php
/** @var array $instance @var array $registrations @var array $available @var bool $canManage */
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= e(url('meets/' . (int) $instance['meet_id'] . '/setup#instances')) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-user-check"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= e($instance['label']) ?></h1>
            <p class="text-sm text-slate-500"><?= e($instance['discipline_name']) ?> · <?= e($instance['event_name']) ?> · <?= e($instance['category_name']) ?></p>
        </div>
    </div>
    <?php if ($canManage): ?>
    <button type="button" class="btn btn-primary" id="addRegBtn" <?= empty($available) ? 'disabled title="No unregistered contestants"' : '' ?>>
        <i class="fa-solid fa-plus"></i> Register Contestant
    </button>
    <?php endif; ?>
</div>

<div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th>Unique #</th><th>Contestant</th><th>House</th><th>Reg. Date</th><th>Status</th>
                <?php if ($canManage): ?><th class="text-right">Actions</th><?php endif; ?>
            </tr></thead>
            <tbody>
                <?php if (empty($registrations)): ?>
                    <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center py-10 text-slate-400"><i class="fa-solid fa-inbox text-2xl mb-2 block"></i>No registrations yet.</td></tr>
                <?php else: foreach ($registrations as $r): ?>
                    <tr>
                        <td><?= e($r['unique_number']) ?></td>
                        <td><?= e($r['contestant_name']) ?></td>
                        <td><?= e($r['house_name'] ?? '') ?></td>
                        <td><?= e(format_date($r['registration_date'])) ?></td>
                        <td>
                            <?php if ($canManage): ?>
                                <select class="form-select !py-1 !w-auto text-sm" data-status="<?= (int) $r['id'] ?>">
                                    <?php foreach (['registered' => 'Registered', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $v => $l): ?>
                                        <option value="<?= $v ?>" <?= $r['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <?= status_badge($r['status']) ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($canManage): ?>
                        <td class="text-right"><button type="button" class="text-slate-500 hover:text-rose-600 px-2" data-remove="<?= (int) $r['id'] ?>" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Add registration modal -->
<div id="regModal" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal-panel !max-w-lg">
        <form id="regForm" novalidate>
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Register Contestant</h2>
                <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="px-6 py-5">
                <label class="form-label">Contestant</label>
                <select name="contestant_id" class="form-select" required>
                    <option value="">— Select contestant —</option>
                    <?php foreach ($available as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['unique_number'] . ' — ' . $c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Register</button>
            </div>
        </form>
    </div>
</div>

<script>
window.REG = { base: <?= json_encode(url('instances/' . (int) $instance['id'])) ?> };
</script>
<script src="<?= e(asset('js/registrations.js')) ?>"></script>
<?php endif; ?>
