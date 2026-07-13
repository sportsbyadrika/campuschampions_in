<?php /** @var string $content */ ?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Results') ?> &middot; <?= e(config('app.name')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: { DEFAULT: '#2563EB', 700: '#1D4ED8' } } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="h-full bg-slate-50 text-slate-800">
<header class="bg-white border-b border-slate-200 print:hidden">
    <div class="mx-auto max-w-6xl px-4 py-4 flex items-center justify-between">
        <a href="<?= e(url('public-results')) ?>" class="flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-white"><i class="fa-solid fa-trophy"></i></span>
            <span class="text-lg font-bold text-slate-900">Campus Champions</span>
        </a>
        <a href="<?= e(url('login')) ?>" class="text-sm font-medium text-primary hover:underline">Staff Login <i class="fa-solid fa-arrow-right-to-bracket ml-1"></i></a>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-8">
    <?= $content ?>
</main>

<footer class="mt-16 border-t border-slate-200 py-6 text-center text-sm text-slate-500 print:hidden">
    &copy; <?= date('Y') ?> Campus Champions
</footer>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
