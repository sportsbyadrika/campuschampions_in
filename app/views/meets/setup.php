<?php
/** @var array $meet @var array $disciplines @var array $categories @var array $events @var array $instances @var array $points @var array $disciplineOptions @var array $categoryOptions */
$statusSel = fn($cur, $val) => $cur === $val ? 'selected' : '';
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="flex items-center gap-3">
        <a href="<?= e(url('meets')) ?>" class="text-slate-400 hover:text-primary"><i class="fa-solid fa-arrow-left"></i></a>
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><i class="fa-solid fa-sliders"></i></span>
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= e($meet['title']) ?></h1>
            <p class="text-sm text-slate-500"><?= e(format_date($meet['start_date'])) ?> &ndash; <?= e(format_date($meet['end_date'])) ?> · <?= status_badge($meet['status']) ?></p>
        </div>
    </div>
    <a href="<?= e(url('meets/' . (int) $meet['id'] . '/bulk')) ?>" class="btn btn-secondary"><i class="fa-solid fa-file-arrow-up"></i> Bulk Import</a>
</div>

<!-- Tabs -->
<div class="mt-6 border-b border-slate-200">
    <nav class="flex flex-wrap gap-1 -mb-px" id="setupTabs">
        <?php foreach (['points' => 'Points', 'disciplines' => 'Disciplines', 'categories' => 'Categories', 'events' => 'Events', 'instances' => 'Event Instances', 'live' => 'Live Screen'] as $key => $label): ?>
            <button type="button" class="setup-tab px-4 py-2 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-primary" data-tab="<?= $key ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </nav>
</div>

<!-- ============ Points ============ -->
<section data-panel="points" class="setup-panel mt-6 hidden">
    <div class="max-w-lg rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold text-slate-900">Points per position</h2>
        <p class="text-sm text-slate-500 mt-1">Used to score results and compute standings.</p>
        <form id="pointsForm" class="mt-4 grid grid-cols-2 gap-4">
            <?php foreach (['first' => 'First', 'second' => 'Second', 'third' => 'Third', 'participant' => 'Participant'] as $pos => $label): ?>
                <div>
                    <label class="form-label"><?= $label ?></label>
                    <input type="number" step="0.5" name="<?= $pos ?>" value="<?= e((string) $points[$pos]) ?>" class="form-input">
                </div>
            <?php endforeach; ?>
            <div class="col-span-2 flex justify-end">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Points</button>
            </div>
        </form>
    </div>
</section>

<!-- ============ Live Screen ============ -->
<?php
$liveImg = function (string $field, string $title, string $hint, string $ratioLabel, ?string $current, bool $crop = true) {
    $url = $current ? asset($current) : '';
    ?>
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200" data-crop-field="<?= e($field) ?>">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="font-semibold text-slate-900"><?= e($title) ?></h3>
                <p class="text-xs text-slate-500 mt-0.5"><?= e($hint) ?><?= $crop ? ' · Crop ' . e($ratioLabel) : '' ?></p>
            </div>
            <?php if ($crop): ?><span class="text-[11px] font-medium text-slate-400"><?= e($ratioLabel) ?></span><?php endif; ?>
        </div>
        <div class="mt-3 flex items-center gap-4">
            <div class="crop-preview flex items-center justify-center rounded-lg bg-slate-100 ring-1 ring-slate-200 overflow-hidden" data-preview>
                <img src="<?= e($url) ?>" alt="" data-preview-img class="<?= $url ? '' : 'hidden' ?> max-h-full max-w-full object-contain">
                <span class="text-slate-300 text-xs px-2 <?= $url ? 'hidden' : '' ?>" data-preview-empty>No image</span>
            </div>
            <div class="flex flex-col gap-2">
                <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" data-file>
                <button type="button" class="btn btn-secondary btn-sm" data-pick>
                    <i class="fa-solid <?= $crop ? 'fa-crop-simple' : 'fa-image' ?>"></i> <?= $crop ? 'Choose &amp; crop' : 'Choose image' ?>
                </button>
                <button type="button" class="btn btn-secondary btn-sm text-rose-600 <?= $url ? '' : 'hidden' ?>" data-remove><i class="fa-solid fa-trash"></i> Remove</button>
            </div>
        </div>
        <p class="form-error hidden mt-2" data-error="<?= e($field) ?>"></p>
    </div>
    <?php
};
?>
<section data-panel="live" class="setup-panel mt-6 hidden">
    <div class="rounded-xl bg-blue-50 border border-blue-100 px-4 py-2.5 text-sm text-blue-800 flex items-center gap-2">
        <i class="fa-solid fa-tv"></i> These settings control the live big-screen dashboard.
        <a href="<?= e(url('standings/live/' . (int) $meet['id'])) ?>" target="_blank" class="font-semibold underline">Open live screen ↗</a>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-3">
        <?php
        $liveImg('logo', 'Meet Logo', 'Shown centered in the top banner', '3 : 1 (wide)', $meet['logo_path'] ?? null);
        $liveImg('institution_logo', 'Institution Logo', 'Shown on the left of the banner', '1 : 1 (square)', $meet['institution_logo_path'] ?? null);
        $liveImg('banner', 'Banner Image', 'Optional banner image for the meet (uploaded as-is)', '', $meet['banner_path'] ?? null, false);
        ?>
    </div>

    <div class="mt-4 max-w-lg rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h3 class="font-semibold text-slate-900">Prize Winners scroll speed</h3>
        <p class="text-sm text-slate-500 mt-1">How fast the “Prize Winners by Event” panel auto-scrolls on the live screen.</p>
        <div class="mt-4 flex items-center gap-4">
            <input type="range" id="scrollSpeed" min="5" max="120" step="1" value="<?= (int) ($meet['winners_scroll_speed'] ?? 28) ?>" class="flex-1 accent-primary">
            <span class="inline-flex items-center gap-1 tabular-nums text-sm font-semibold text-slate-700"><span id="scrollSpeedVal"><?= (int) ($meet['winners_scroll_speed'] ?? 28) ?></span> px/s</span>
        </div>
        <div class="mt-2 flex justify-between text-xs text-slate-400"><span>Slower</span><span>Faster</span></div>
    </div>

    <div class="mt-4 flex justify-end">
        <button type="button" id="saveLiveSettings" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Live Screen Settings</button>
    </div>
</section>

<?php
// Reusable section renderer
$renderSection = function (string $entity, string $title, array $rows, array $cols, string $addLabel) use ($meet) {
    ?>
    <section data-panel="<?= $entity ?>" class="setup-panel mt-6 hidden">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between p-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900"><?= e($title) ?></h2>
                <button type="button" class="btn btn-primary btn-sm" data-add="<?= $entity ?>"><i class="fa-solid fa-plus"></i> <?= e($addLabel) ?></button>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <?php foreach ($cols as $c): ?><th><?= e($c['label']) ?></th><?php endforeach; ?>
                        <th class="text-right">Actions</th>
                    </tr></thead>
                    <tbody data-list="<?= $entity ?>">
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="<?= count($cols) + 1 ?>" class="text-center py-8 text-slate-400">No records yet.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($cols as $c):
                                $v = $r[$c['key']] ?? '';
                                if (($c['type'] ?? '') === 'badge') { echo '<td>' . status_badge((string) $v) . '</td>'; }
                                else { echo '<td>' . e((string) $v) . '</td>'; }
                            endforeach; ?>
                            <td class="text-right whitespace-nowrap">
                                <?php if ($entity === 'instances'): ?>
                                    <a href="<?= e(url('instances/' . (int) $r['id'] . '/registrations')) ?>" class="text-slate-500 hover:text-primary px-2" title="Registrations"><i class="fa-solid fa-user-check"></i></a>
                                <?php endif; ?>
                                <button type="button" class="text-slate-500 hover:text-primary px-2" data-edit="<?= $entity ?>" data-record='<?= e(json_encode($r)) ?>' title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button type="button" class="text-slate-500 hover:text-rose-600 px-2" data-del="<?= $entity ?>" data-id="<?= (int) $r['id'] ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php
};

$renderSection('disciplines', 'Disciplines', $disciplines, [
    ['key' => 'name', 'label' => 'Name'], ['key' => 'description', 'label' => 'Description'], ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
], 'Add Discipline');

$renderSection('categories', 'Categories', $categories, [
    ['key' => 'name', 'label' => 'Name'], ['key' => 'description', 'label' => 'Description'], ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
], 'Add Category');

$renderSection('events', 'Events', $events, [
    ['key' => 'name', 'label' => 'Event'], ['key' => 'discipline_name', 'label' => 'Discipline'], ['key' => 'event_type', 'label' => 'Type'], ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
], 'Add Event');

$renderSection('instances', 'Event Instances', $instances, [
    ['key' => 'label', 'label' => 'Label'], ['key' => 'event_name', 'label' => 'Event'], ['key' => 'category_name', 'label' => 'Category'], ['key' => 'instance_date', 'label' => 'Date'], ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
], 'Add Instance');
?>

<!-- ============ Modals ============ -->
<?php
$modal = function (string $entity, string $title, string $fieldsHtml) {
    ?>
    <div id="modal-<?= $entity ?>" class="modal-backdrop hidden" role="dialog" aria-modal="true">
        <div class="modal-panel" role="document">
            <form data-form="<?= $entity ?>" novalidate>
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900" data-title><?= e($title) ?></h2>
                    <button type="button" class="text-slate-400 hover:text-slate-600" data-modal-close><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <input type="hidden" name="id" data-idfield value="">
                    <?= $fieldsHtml ?>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php
};

$statusField = '<div><label class="form-label">Status</label><select name="status" data-field="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select><p class="form-error hidden" data-error="status"></p></div>';

$modal('disciplines', 'Discipline',
    '<div><label class="form-label">Name *</label><input name="name" data-field="name" class="form-input"><p class="form-error hidden" data-error="name"></p></div>'
    . '<div><label class="form-label">Description</label><textarea name="description" data-field="description" rows="2" class="form-textarea"></textarea></div>'
    . $statusField);

$modal('categories', 'Category',
    '<div><label class="form-label">Name *</label><input name="name" data-field="name" class="form-input"><p class="form-error hidden" data-error="name"></p></div>'
    . '<div><label class="form-label">Description</label><textarea name="description" data-field="description" rows="2" class="form-textarea"></textarea></div>'
    . $statusField);

$disciplineSelect = '<select name="discipline_id" data-field="discipline_id" class="form-select">';
foreach ($disciplineOptions as $d) { $disciplineSelect .= '<option value="' . (int) $d['id'] . '">' . e($d['name']) . '</option>'; }
$disciplineSelect .= '</select>';

$modal('events', 'Event',
    '<div><label class="form-label">Event Name *</label><input name="name" data-field="name" class="form-input"><p class="form-error hidden" data-error="name"></p></div>'
    . '<div><label class="form-label">Discipline *</label>' . $disciplineSelect . '<p class="form-error hidden" data-error="discipline_id"></p></div>'
    . '<div><label class="form-label">Type</label><select name="event_type" data-field="event_type" class="form-select"><option value="individual">Individual</option><option value="group">Group</option></select></div>'
    . $statusField);

$eventSelect = '<select name="event_id" data-field="event_id" class="form-select">';
foreach ($events as $ev) { $eventSelect .= '<option value="' . (int) $ev['id'] . '">' . e($ev['discipline_name'] . ' · ' . $ev['name']) . '</option>'; }
$eventSelect .= '</select>';
$categorySelect = '<select name="category_id" data-field="category_id" class="form-select">';
foreach ($categoryOptions as $c) { $categorySelect .= '<option value="' . (int) $c['id'] . '">' . e($c['name']) . '</option>'; }
$categorySelect .= '</select>';

$modal('instances', 'Event Instance',
    '<div><label class="form-label">Label *</label><input name="label" data-field="label" class="form-input" placeholder="e.g. Boys Junior Solo Singing - Final"><p class="form-error hidden" data-error="label"></p></div>'
    . '<div class="grid grid-cols-2 gap-3"><div><label class="form-label">Event *</label>' . $eventSelect . '<p class="form-error hidden" data-error="event_id"></p></div>'
    . '<div><label class="form-label">Category *</label>' . $categorySelect . '<p class="form-error hidden" data-error="category_id"></p></div></div>'
    . '<div class="grid grid-cols-2 gap-3"><div><label class="form-label">Date</label><input type="date" name="instance_date" data-field="instance_date" class="form-input"></div>'
    . '<div><label class="form-label">Time</label><input type="time" name="instance_time" data-field="instance_time" class="form-input"></div></div>'
    . '<div><label class="form-label">Venue</label><input name="venue" data-field="venue" class="form-input"></div>'
    . '<div><label class="form-label">Status</label><select name="status" data-field="status" class="form-select"><option value="scheduled">Scheduled</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>');
?>

<!-- Delete confirm -->
<div id="setupDelete" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal-panel !max-w-md">
        <div class="px-6 py-5 text-center">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600"><i class="fa-solid fa-triangle-exclamation text-xl"></i></span>
            <h2 class="mt-3 text-lg font-semibold text-slate-900">Delete this record?</h2>
            <p class="mt-1 text-sm text-slate-500">This cannot be undone.</p>
        </div>
        <div class="flex justify-center gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-danger" id="setupConfirmDelete"><i class="fa-solid fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<!-- Crop modal (shared by all live-screen images) -->
<div id="cropModal" class="modal-backdrop hidden" role="dialog" aria-modal="true">
    <div class="modal-panel !max-w-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Crop image <span class="text-sm font-normal text-slate-400" id="cropRatioLabel"></span></h2>
            <button type="button" class="text-slate-400 hover:text-slate-600" data-crop-close><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="px-6 py-5">
            <div id="cropStage" class="relative mx-auto select-none overflow-hidden rounded-lg bg-slate-900 touch-none" style="max-width:100%">
                <img id="cropImg" alt="" draggable="false" class="absolute left-0 top-0 will-change-transform" style="transform-origin:0 0">
                <div class="pointer-events-none absolute inset-0 ring-1 ring-white/40"></div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <i class="fa-solid fa-magnifying-glass-minus text-slate-400"></i>
                <input type="range" id="cropZoom" min="1" max="4" step="0.01" value="1" class="flex-1 accent-primary">
                <i class="fa-solid fa-magnifying-glass-plus text-slate-400"></i>
            </div>
            <p class="mt-2 text-xs text-slate-400">Drag the image to reposition; use the slider to zoom.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" class="btn btn-secondary" data-crop-close>Cancel</button>
            <button type="button" class="btn btn-primary" id="cropApply"><i class="fa-solid fa-check"></i> Apply crop</button>
        </div>
    </div>
</div>

<style>.btn-sm{padding:.35rem .7rem;font-size:.8rem}.setup-tab.active{color:#2563EB;border-color:#2563EB}
.crop-preview{width:9rem;height:5rem}</style>
<script>window.MEET = {
    id: <?= (int) $meet['id'] ?>,
    base: <?= json_encode(url('meets/' . (int) $meet['id']), JSON_UNESCAPED_SLASHES) ?>,
    crop: {
        logo:             { aspect: 3,  outW: 900, outH: 300, label: '3 : 1' },
        institution_logo: { aspect: 1,  outW: 512, outH: 512, label: '1 : 1' }
    }
};</script>
<script src="<?= e(asset('js/meet_setup.js')) ?>"></script>
<script src="<?= e(asset('js/meet_live.js')) ?>"></script>
