<?php
/** @var array $template @var bool $isNew */
$t = $template;
$action = $isNew ? url('certificate-templates') : url('certificate-templates/' . (int) $t['id']);
$v = fn(string $k, $def = '') => e((string) ($t[$k] ?? $def));
$num = fn(string $k, $def = 0) => (int) ($t[$k] ?? $def);
$placeholders = [
    '{{contestant_name}}' => 'contestant', '{{course}}' => 'course', '{{division}}' => 'division',
    '{{position}}' => 'result', '{{event_label}}' => 'event instance', '{{event_name}}' => 'event',
    '{{meet_title}}' => 'meet', '{{issue_date}}' => 'date', '{{unique_number}}' => 'unique #',
    '{{house_name}}' => 'house', '{{category}}' => 'category', '{{category_group}}' => 'category group',
    '{{certificate_number}}' => 'certificate #',
];
?>
<div class="flex items-center gap-3">
    <a href="<?= e(url('certificate-templates')) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-award"></i></span>
    <div>
        <h1 class="text-2xl font-bold text-slate-900"><?= $isNew ? 'New' : 'Edit' ?> Certificate Template</h1>
        <p class="text-sm text-slate-500">Positions and margins are in millimetres (A4). Use <b>Preview</b> to check placement.</p>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" class="mt-6 grid gap-6 lg:grid-cols-3">
    <?= csrf_field() ?>

    <!-- Left: body + placeholders -->
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Template Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="<?= $v('name') ?>" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Page Orientation</label>
                    <select name="orientation" class="form-select">
                        <option value="portrait" <?= ($t['orientation'] ?? 'portrait') === 'portrait' ? 'selected' : '' ?>>Portrait</option>
                        <option value="landscape" <?= ($t['orientation'] ?? '') === 'landscape' ? 'selected' : '' ?>>Landscape</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="form-label">Certificate Body HTML <span class="text-rose-500">*</span></label>
                <textarea name="body_html" rows="14" class="form-textarea font-mono text-xs" required><?= $v('body_html') ?></textarea>
                <div class="mt-2">
                    <p class="text-xs font-medium text-slate-600">Placeholders you can use (click to copy):</p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        <?php foreach ($placeholders as $token => $meaning): ?>
                            <button type="button" class="ph-chip inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 hover:bg-slate-200" data-token="<?= e($token) ?>">
                                <code class="text-primary text-[11px]"><?= e($token) ?></code><span class="text-slate-400 text-[11px]"><?= e($meaning) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: layout config -->
    <div class="space-y-6">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">Content Margins <span class="text-xs font-normal text-slate-400">(mm)</span></h3>
            <p class="text-xs text-slate-500 mt-0.5">The content box where the body is centred — set these so the text lands in the blank area.</p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <?php foreach (['margin_top' => 'Top', 'margin_bottom' => 'Bottom', 'margin_left' => 'Left', 'margin_right' => 'Right'] as $k => $lbl): ?>
                    <div>
                        <label class="form-label"><?= $lbl ?></label>
                        <input type="number" min="0" max="400" name="<?= $k ?>" value="<?= $num($k) ?>" class="form-input">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">Certificate Number</h3>
            <p class="text-xs text-slate-500 mt-0.5">Printed top-left. Number = prefix + running number + suffix (auto-increments per meet).</p>
            <div class="mt-3">
                <label class="form-label">Label <span class="text-xs font-normal text-slate-400">(optional — printed before the number)</span></label>
                <input type="text" name="number_label" value="<?= $v('number_label') ?>" maxlength="60" class="form-input" placeholder="e.g. Certificate No:">
            </div>
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div>
                    <label class="form-label">Prefix</label>
                    <input type="text" name="number_prefix" value="<?= $v('number_prefix') ?>" maxlength="30" class="form-input" placeholder="e.g. SK/">
                </div>
                <div>
                    <label class="form-label">Next #</label>
                    <input type="number" min="1" name="number_next" value="<?= $num('number_next', 1) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Suffix</label>
                    <input type="text" name="number_suffix" value="<?= $v('number_suffix') ?>" maxlength="30" class="form-input" placeholder="e.g. /2026">
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Position Top (mm)</label>
                    <input type="number" min="0" max="400" name="number_top" value="<?= $num('number_top', 12) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Position Left (mm)</label>
                    <input type="number" min="0" max="400" name="number_left" value="<?= $num('number_left', 15) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Font Size (px)</label>
                    <input type="number" min="6" max="72" name="number_font_size" value="<?= $num('number_font_size', 11) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Font Colour</label>
                    <input type="color" name="number_font_color" value="<?= $v('number_font_color', '#333333') ?>" class="h-9 w-full rounded border border-slate-300 p-0.5">
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">Certificate Date</h3>
            <p class="text-xs text-slate-500 mt-0.5">Printed bottom-left. The date value is chosen when generating.</p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Position Top (mm)</label>
                    <input type="number" min="0" max="400" name="date_top" value="<?= $num('date_top', 262) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Position Left (mm)</label>
                    <input type="number" min="0" max="400" name="date_left" value="<?= $num('date_left', 20) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Font Size (px)</label>
                    <input type="number" min="6" max="72" name="date_font_size" value="<?= $num('date_font_size', 11) ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Font Colour</label>
                    <input type="color" name="date_font_color" value="<?= $v('date_font_color', '#333333') ?>" class="h-9 w-full rounded border border-slate-300 p-0.5">
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($t['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($t['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_default" value="1" <?= (int) ($t['is_default'] ?? 0) === 1 ? 'checked' : '' ?> class="rounded border-slate-300 text-primary">
                        Default template
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="lg:col-span-3 flex flex-wrap justify-end gap-2">
        <a href="<?= e(url('certificate-templates')) ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" formaction="<?= e(url('certificate-templates/preview')) ?>" formtarget="_blank" class="btn btn-secondary"><i class="fa-solid fa-file-pdf"></i> Preview PDF</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Template</button>
    </div>
</form>

<script>
document.querySelectorAll('.ph-chip').forEach(function (b) {
    b.addEventListener('click', function () {
        var ta = document.querySelector('textarea[name="body_html"]');
        var tok = b.dataset.token;
        if (!ta) return;
        var s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.slice(0, s) + tok + ta.value.slice(e);
        ta.focus(); ta.selectionStart = ta.selectionEnd = s + tok.length;
    });
});
</script>
