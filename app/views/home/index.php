<?php /** @var bool $isLoggedIn */ ?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('app.name')) ?> — Championship Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: { DEFAULT: '#2563EB', 700: '#1D4ED8' } } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="flex min-h-full flex-col bg-slate-950 text-slate-100">
    <!-- decorative background -->
    <div class="pointer-events-none fixed inset-0 -z-10"
         style="background:
             radial-gradient(1100px 600px at 15% -10%, rgba(37,99,235,.35), transparent 60%),
             radial-gradient(900px 500px at 100% 0%, rgba(16,185,129,.18), transparent 55%),
             linear-gradient(160deg, #0b1220 0%, #0f172a 45%, #0b1220 100%);"></div>

    <!-- Header -->
    <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-lg shadow-primary/40"><i class="fa-solid fa-trophy"></i></span>
            <span class="text-xl font-bold"><?= e(config('app.name')) ?></span>
        </div>
        <?php if ($isLoggedIn): ?>
            <a href="<?= e(url('dashboard')) ?>" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/15 hover:bg-white/15">Go to Dashboard <i class="fa-solid fa-arrow-right ml-1"></i></a>
        <?php else: ?>
            <a href="<?= e(url('login')) ?>" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/15 hover:bg-white/15">Institution Login <i class="fa-solid fa-arrow-right-to-bracket ml-1"></i></a>
        <?php endif; ?>
    </header>

    <!-- Hero -->
    <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col items-center justify-center px-6 py-16 text-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-1.5 text-xs font-medium text-sky-300 ring-1 ring-white/10">
            <i class="fa-solid fa-medal"></i> School Championship Management
        </span>
        <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-tight sm:text-5xl">
            Run your meets. <span class="bg-gradient-to-r from-sky-400 to-emerald-400 bg-clip-text text-transparent">Celebrate your champions.</span>
        </h1>
        <p class="mt-5 max-w-2xl text-base text-slate-300 sm:text-lg">
            Manage events, enter results and publish live standings for your institution — all in one place. View published results anytime, from anywhere.
        </p>

        <div class="mt-10 flex flex-col gap-4 sm:flex-row">
            <a href="<?= e(url('login')) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-primary/40 transition hover:bg-primary-700">
                <i class="fa-solid fa-right-to-bracket"></i> Institution Login
            </a>
            <a href="<?= e(url('public-results')) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/10 px-7 py-3.5 text-base font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/15">
                <i class="fa-solid fa-ranking-star"></i> View Results
            </a>
        </div>

        <!-- Feature cards -->
        <div class="mt-16 grid w-full max-w-4xl gap-5 sm:grid-cols-3">
            <?php
            $features = [
                ['fa-calendar-check', 'Organize Meets', 'Configure disciplines, events and categories with ease.'],
                ['fa-square-poll-vertical', 'Live Standings', 'Broadcast results on a big-screen live dashboard.'],
                ['fa-trophy', 'Public Results', 'Share published prize winners with everyone.'],
            ];
            foreach ($features as [$icon, $t, $d]): ?>
                <div class="rounded-2xl bg-white/5 p-6 text-left ring-1 ring-white/10 backdrop-blur">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/20 text-sky-300"><i class="fa-solid <?= $icon ?>"></i></span>
                    <h3 class="mt-4 font-semibold text-white"><?= e($t) ?></h3>
                    <p class="mt-1 text-sm text-slate-400"><?= e($d) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mx-auto w-full max-w-6xl px-6 py-8 text-center text-xs text-slate-400">
        <p>&copy; <?= date('Y') ?>
            <a href="https://sportsbya.com" target="_blank" rel="noopener" class="font-medium text-slate-300 hover:text-white hover:underline">SportsByA Tech (OPC) Private Limited</a>.
            All rights reserved.
        </p>
        <p class="mt-1">Powered by
            <a href="https://sportsmis.com" target="_blank" rel="noopener" class="font-medium text-slate-300 hover:text-white hover:underline">SportsMIS.com<sup>&reg;</sup></a>
        </p>
    </footer>
</body>
</html>
