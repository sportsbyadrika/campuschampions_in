<?php
/** @var array $meets */
?>
<div class="text-center">
    <h1 class="text-3xl font-bold text-slate-900">Active Meets</h1>
    <p class="mt-2 text-sm text-slate-500">Select a meet to view its published results.</p>
</div>

<?php if (empty($meets)): ?>
    <div class="mt-10 rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200 text-slate-400">
        <i class="fa-solid fa-calendar-xmark text-3xl mb-3 block"></i>
        No published results are available yet. Please check back later.
    </div>
<?php else: ?>
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($meets as $m): $link = url('public-results/' . (int) $m['id']); ?>
            <a href="<?= e($link) ?>" class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-primary/40">
                <div class="relative aspect-[4/1.2] w-full overflow-hidden bg-gradient-to-br from-primary to-blue-800">
                    <?php if (!empty($m['banner_path'])): ?>
                        <img src="<?= e(asset($m['banner_path'])) ?>" alt="<?= e($m['title']) ?>" class="h-full w-full object-cover transition group-hover:scale-105">
                    <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center px-4 text-center">
                            <span class="text-lg font-bold text-white/90 drop-shadow"><?= e($m['title']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <div class="text-xs font-medium uppercase tracking-wide text-primary"><?= e($m['institution_name']) ?></div>
                    <h2 class="mt-1 text-lg font-bold text-slate-900 group-hover:text-primary"><?= e($m['title']) ?></h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <i class="fa-regular fa-calendar mr-1"></i>
                        <?= e(format_date($m['start_date'])) ?><?= !empty($m['end_date']) && $m['end_date'] !== $m['start_date'] ? ' – ' . e(format_date($m['end_date'])) : '' ?>
                        <?php if (!empty($m['location'])): ?>
                            <span class="mx-1">·</span><i class="fa-solid fa-location-dot mr-1"></i><?= e($m['location']) ?>
                        <?php endif; ?>
                    </p>
                    <div class="mt-4 flex-1"></div>
                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-primary">
                        View Results <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1"></i>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
