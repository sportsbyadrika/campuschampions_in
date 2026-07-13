<?php
/** @var array $instance @var array $assigned @var array $available */
?>
<div class="flex items-center gap-3">
    <a href="<?= e(url('results?meet_id=' . (int) $instance['meet_id'])) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-user-gear"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Assign Event Users</h1>
        <p class="text-sm text-slate-500"><?= e($instance['label']) ?> · <?= e($instance['event_name']) ?></p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="p-4 border-b border-slate-100"><h2 class="font-semibold text-slate-900">Assigned users</h2></div>
        <table class="data-table">
            <tbody id="assignedList">
                <?php if (empty($assigned)): ?>
                    <tr><td class="text-center py-8 text-slate-400">No users assigned yet.</td></tr>
                <?php else: foreach ($assigned as $a): ?>
                    <tr>
                        <td><div class="font-medium"><?= e($a['full_name']) ?></div><div class="text-xs text-slate-500"><?= e($a['email']) ?></div></td>
                        <td class="text-right"><button type="button" class="text-slate-500 hover:text-rose-600 px-2" data-unassign="<?= (int) $a['id'] ?>"><i class="fa-solid fa-user-minus"></i></button></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 p-6">
        <h2 class="font-semibold text-slate-900">Add an event user</h2>
        <?php if (empty($available)): ?>
            <p class="mt-2 text-sm text-slate-500">No unassigned event users available in this campus.</p>
        <?php else: ?>
        <form id="assignForm" class="mt-3 flex gap-2">
            <select name="user_id" class="form-select flex-1" required>
                <option value="">— Select event user —</option>
                <?php foreach ($available as $u): ?>
                    <option value="<?= (int) $u['id'] ?>"><?= e($u['full_name'] . ' (' . $u['email'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Assign</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
window.ASSIGN = { base: <?= json_encode(url('results/' . (int) $instance['id'])) ?> };
</script>
<script src="<?= e(asset('js/assign.js')) ?>"></script>
