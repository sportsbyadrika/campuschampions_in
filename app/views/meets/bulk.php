<?php /** @var array $meet */ ?>
<div class="flex items-center gap-3">
    <a href="<?= e(url('meets/' . (int) $meet['id'] . '/setup')) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-file-arrow-up"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Bulk Import</h1>
        <p class="text-sm text-slate-500"><?= e($meet['title']) ?> — import events and event instances from CSV.</p>
    </div>
</div>

<div class="mt-4 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-800">
    <i class="fa-solid fa-circle-info mr-1"></i>
    Disciplines and categories are matched by <strong>name</strong> within this meet, so create them first
    (in the meet setup) before importing events/instances.
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <!-- Events -->
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-dumbbell"></i></span>
            <h2 class="font-semibold text-slate-900">Events</h2>
        </div>
        <form method="post" action="<?= e(url('meets/' . (int) $meet['id'] . '/bulk/events-preview')) ?>" enctype="multipart/form-data" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <input type="file" name="csv_file" accept=".csv,text/csv" required class="form-input">
            <div class="text-xs text-slate-500">
                Columns: <code>discipline, event_name, event_type, status</code><br>
                <span class="text-slate-400">event_type: individual|group · status: active|inactive</span>
            </div>
            <div class="flex justify-between">
                <a href="<?= e(url('meets/' . (int) $meet['id'] . '/bulk/events-template')) ?>" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Template</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-eye"></i> Preview</button>
            </div>
        </form>
    </div>

    <!-- Event Instances -->
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-calendar-check"></i></span>
            <h2 class="font-semibold text-slate-900">Event Instances</h2>
        </div>
        <form method="post" action="<?= e(url('meets/' . (int) $meet['id'] . '/bulk/instances-preview')) ?>" enctype="multipart/form-data" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <input type="file" name="csv_file" accept=".csv,text/csv" required class="form-input">
            <div class="text-xs text-slate-500">
                Columns: <code>discipline, event_name, category, label, instance_date, instance_time, venue, status</code><br>
                <span class="text-slate-400">date: YYYY-MM-DD · time: HH:MM · status: scheduled|ongoing|completed|cancelled</span>
            </div>
            <div class="flex justify-between">
                <a href="<?= e(url('meets/' . (int) $meet['id'] . '/bulk/instances-template')) ?>" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Template</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-eye"></i> Preview</button>
            </div>
        </form>
    </div>
</div>
