<?php
/**
 * Shared error page renderer.
 * Expects: $code, $heading, $message, and optional $exception/$debug in scope.
 */
$appName = function_exists('config') ? config('app.name') : 'Campus Champions';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (int) $code ?> &middot; <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <p class="text-7xl font-black text-blue-600"><?= (int) $code ?></p>
        <h1 class="mt-4 text-2xl font-bold text-slate-900"><?= htmlspecialchars($heading) ?></h1>
        <p class="mt-2 text-slate-500"><?= htmlspecialchars($message) ?></p>

        <?php if (!empty($debug) && !empty($exception)): ?>
            <pre class="mt-6 text-left text-xs bg-slate-900 text-slate-100 rounded-lg p-4 overflow-auto max-h-64"><?php
                echo htmlspecialchars($exception->getMessage() . "\n\n" . $exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString());
            ?></pre>
        <?php endif; ?>

        <div class="mt-8">
            <a href="<?= htmlspecialchars(function_exists('url') ? url('/') : '/') ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                &larr; Back to home
            </a>
        </div>
    </div>
</body>
</html>
