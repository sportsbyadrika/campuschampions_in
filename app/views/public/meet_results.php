<?php
/** @var array $meet @var array $events */
$cell = function (array $list): string {
    if (empty($list)) {
        return '<span class="pw-dash">—</span>';
    }
    $out = '';
    foreach ($list as $w) {
        $out .= '<div class="pw-win"><div class="pw-name">' . e($w['name']) . '</div>';
        $meta = trim(($w['unique'] ?? '') . (($w['unique'] ?? '') && ($w['meta'] ?? '') ? ' · ' : '') . ($w['meta'] ?? ''));
        if ($meta !== '') {
            $out .= '<div class="pw-meta">' . e($meta) . '</div>';
        }
        $out .= '</div>';
    }
    return $out;
};
?>
<!-- Meet header -->
<div class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white shadow-lg">
    <?php if (!empty($meet['banner_path'])): ?>
        <img src="<?= e(asset($meet['banner_path'])) ?>" alt="<?= e($meet['title']) ?>" class="max-h-52 w-full object-cover">
    <?php endif; ?>
    <div class="flex items-center gap-4 p-6">
        <?php if (!empty($meet['logo_path'])): ?>
            <img src="<?= e(asset($meet['logo_path'])) ?>" alt="" class="h-14 max-w-[9rem] object-contain">
        <?php endif; ?>
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-sky-300"><?= e($meet['institution_name']) ?></div>
            <h1 class="text-2xl font-bold"><?= e($meet['title']) ?></h1>
            <p class="mt-0.5 text-sm text-slate-300">
                <i class="fa-regular fa-calendar mr-1"></i><?= e(format_date($meet['start_date'])) ?>
                <?php if (!empty($meet['location'])): ?><span class="mx-1">·</span><i class="fa-solid fa-location-dot mr-1"></i><?= e($meet['location']) ?><?php endif; ?>
            </p>
        </div>
    </div>
</div>

<h2 class="mt-8 flex items-center gap-2 text-lg font-bold text-slate-900"><span>🥇</span> Prize Winners by Event</h2>

<?php if (empty($events)): ?>
    <div class="mt-4 rounded-xl bg-white p-10 text-center text-slate-400 shadow-sm ring-1 ring-slate-200">
        <i class="fa-solid fa-hourglass-half text-2xl mb-2 block"></i> No published results yet for this meet.
    </div>
<?php else: ?>
    <div class="mt-4 overflow-hidden rounded-2xl bg-slate-900 shadow-lg ring-1 ring-white/10">
        <div class="overflow-x-auto">
            <table class="pw-table">
                <thead>
                    <tr>
                        <th style="width:28%">Event Instance</th>
                        <th>🥇 First</th>
                        <th>🥈 Second</th>
                        <th>🥉 Third</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td class="pw-evtcell">
                                <span class="pw-evt"><?= e($ev['label']) ?></span>
                                <small class="pw-sub"><?= e($ev['sub']) ?></small>
                            </td>
                            <td><?= $cell($ev['first']) ?></td>
                            <td><?= $cell($ev['second']) ?></td>
                            <td><?= $cell($ev['third']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
    .pw-table { width: 100%; border-collapse: collapse; color: #e5edf7; }
    .pw-table th, .pw-table td { text-align: left; padding: .85rem 1rem; border-bottom: 1px solid rgba(255,255,255,.08); vertical-align: top; }
    .pw-table thead th { position: sticky; top: 0; background: rgba(15,23,42,.95); color: #93a4bd; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .pw-table tbody tr:nth-child(even) { background: rgba(255,255,255,.04); }
    .pw-evtcell { border-left: 4px solid #38bdf8; }
    .pw-evt { display: block; font-weight: 700; font-size: .82rem; color: #38bdf8; letter-spacing: .2px; }
    .pw-sub { display: block; color: #93a4bd; font-weight: 400; font-size: .72rem; margin-top: .2rem; }
    .pw-win { margin-bottom: .5rem; }
    .pw-win:last-child { margin-bottom: 0; }
    .pw-name { font-weight: 700; font-size: .9rem; color: #fff; }
    .pw-meta { font-size: .72rem; color: #93a4bd; }
    .pw-dash { color: rgba(255,255,255,.25); }
</style>
