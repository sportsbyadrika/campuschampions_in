<?php /** @var string $content */ ?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Sign In') ?> &middot; <?= e(config('app.name')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: { DEFAULT: '#2563EB', 700: '#1D4ED8' } } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-slate-100">
<div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
    <div class="mb-8 flex items-center gap-3">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white text-2xl">
            <i class="fa-solid fa-trophy"></i>
        </span>
        <span class="text-2xl font-bold text-slate-900">Campus Champions</span>
    </div>

    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
        <?= $content ?>
    </div>

    <p class="mt-6 text-sm text-slate-500">School Event Management Platform</p>
</div>

<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>
<?php include APP_PATH . '/views/partials/flash.php'; ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
