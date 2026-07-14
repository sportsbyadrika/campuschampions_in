<div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-file-arrow-up"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Bulk Upload Contestants</h1>
        <p class="text-sm text-slate-500">Import many contestants at once from a CSV file.</p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <form method="post" action="<?= e(url('contestants/bulk/preview')) ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="form-label" for="csv_file">CSV file</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required class="form-input">
                <p class="mt-1 text-xs text-slate-500">Max 5 MB. First row must be the header. Course/Division/House/Group are matched by name within your campus.</p>
            </div>
            <div class="flex justify-end gap-2">
                <a href="<?= e(url('contestants')) ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-eye"></i> Preview</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold text-slate-900">Need a template?</h2>
        <p class="mt-1 text-sm text-slate-500">Download a CSV with the exact columns expected.</p>
        <a href="<?= e(url('contestants/bulk/template')) ?>" class="btn btn-secondary mt-3"><i class="fa-solid fa-download"></i> Download Template</a>
        <div class="mt-4 text-xs text-slate-500">
            <p class="font-medium text-slate-700">Columns:</p>
            <code class="block mt-1 break-words">unique_number, name, dob, gender, course, division, house, course_category_group, mobile, email, guardian_name, status, event_instances</code>
            <p class="mt-2"><strong>event_instances</strong> (optional): event instance labels to register the contestant for, separated by <code>;</code> or <code>|</code>. If the unique number already exists, only these event instances are added (existing details are left unchanged).</p>
        </div>
    </div>
</div>
