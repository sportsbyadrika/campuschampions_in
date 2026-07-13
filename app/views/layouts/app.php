<?php
/** @var string $content */
use App\Core\Auth;
$user = Auth::user();
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Dashboard') ?> &middot; <?= e(config('app.name')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#2563EB', 600: '#2563EB', 700: '#1D4ED8', 50: '#EFF6FF' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">

<!-- ===================== Top Navigation ===================== -->
<nav class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Left: logo + primary links -->
            <div class="flex items-center gap-8">
                <a href="<?= e(url('dashboard')) ?>" class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-white">
                        <i class="fa-solid fa-trophy"></i>
                    </span>
                    <span class="text-lg font-bold text-slate-900">Campus Champions</span>
                </a>

                <div class="hidden md:flex items-center gap-1" id="mainNav">
                    <a href="<?= e(url('dashboard')) ?>" class="nav-link <?= active_if('/dashboard') ?>">
                        <i class="fa-solid fa-gauge-high mr-1.5"></i>Dashboard
                    </a>

                    <?php if (can('super_admin')): ?>
                        <a href="<?= e(url('institutions')) ?>" class="nav-link <?= active_if('/institutions') ?>">
                            <i class="fa-solid fa-building-columns mr-1.5"></i>Institutions
                        </a>
                    <?php endif; ?>

                    <?php if (can('super_admin', 'campus_admin')): ?>
                        <!-- Masters dropdown -->
                        <div class="relative" data-dropdown>
                            <button type="button" class="nav-link" data-dropdown-toggle>
                                <i class="fa-solid fa-database mr-1.5"></i>Masters
                                <i class="fa-solid fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div class="dropdown-menu hidden" data-dropdown-menu>
                                <a href="<?= e(url('courses')) ?>" class="dropdown-item">Courses</a>
                                <a href="<?= e(url('divisions')) ?>" class="dropdown-item">Divisions</a>
                                <a href="<?= e(url('houses')) ?>" class="dropdown-item">Houses</a>
                                <a href="<?= e(url('course-category-groups')) ?>" class="dropdown-item">Category Groups</a>
                                <div class="my-1 border-t border-slate-100"></div>
                                <a href="<?= e(url('contestants')) ?>" class="dropdown-item">Contestants</a>
                                <a href="<?= e(url('users')) ?>" class="dropdown-item">Users</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="<?= e(url('meets')) ?>" class="nav-link <?= active_if('/meets') ?>">
                        <i class="fa-solid fa-calendar-days mr-1.5"></i>Meets
                    </a>

                    <?php if (can('super_admin', 'campus_admin', 'event_user')): ?>
                        <a href="<?= e(url('results')) ?>" class="nav-link <?= active_if('/results') ?>">
                            <i class="fa-solid fa-ranking-star mr-1.5"></i>Results
                        </a>
                    <?php endif; ?>

                    <a href="<?= e(url('standings')) ?>" class="nav-link <?= active_if('/standings') ?>">
                        <i class="fa-solid fa-medal mr-1.5"></i>Standings
                    </a>

                    <?php if (can('super_admin')): ?>
                        <a href="<?= e(url('audit-logs')) ?>" class="nav-link <?= active_if('/audit-logs') ?>">
                            <i class="fa-solid fa-clipboard-list mr-1.5"></i>Audit
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: user menu -->
            <div class="flex items-center gap-3">
                <div class="relative" data-dropdown>
                    <button type="button" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-100" data-dropdown-toggle>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">
                            <?= e(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))) ?>
                        </span>
                        <span class="hidden sm:block text-left leading-tight">
                            <span class="block text-sm font-semibold text-slate-900"><?= e($user['full_name'] ?? '') ?></span>
                            <span class="block text-xs text-slate-500"><?= e(Auth::roleLabel()) ?></span>
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </button>
                    <div class="dropdown-menu hidden right-0" data-dropdown-menu>
                        <a href="<?= e(url('profile')) ?>" class="dropdown-item"><i class="fa-solid fa-user mr-2"></i>Profile</a>
                        <a href="<?= e(url('change-password')) ?>" class="dropdown-item"><i class="fa-solid fa-key mr-2"></i>Change Password</a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="post" action="<?= e(url('logout')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item w-full text-left text-rose-600"><i class="fa-solid fa-right-from-bracket mr-2"></i>Logout</button>
                        </form>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <button type="button" class="md:hidden rounded-lg p-2 hover:bg-slate-100" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile nav -->
    <div class="md:hidden hidden border-t border-slate-200 px-4 py-2 space-y-1" id="mobileNav">
        <a href="<?= e(url('dashboard')) ?>" class="mobile-link">Dashboard</a>
        <?php if (can('super_admin')): ?><a href="<?= e(url('institutions')) ?>" class="mobile-link">Institutions</a><?php endif; ?>
        <?php if (can('super_admin', 'campus_admin')): ?>
            <a href="<?= e(url('courses')) ?>" class="mobile-link">Courses</a>
            <a href="<?= e(url('contestants')) ?>" class="mobile-link">Contestants</a>
            <a href="<?= e(url('users')) ?>" class="mobile-link">Users</a>
        <?php endif; ?>
        <a href="<?= e(url('meets')) ?>" class="mobile-link">Meets</a>
        <a href="<?= e(url('results')) ?>" class="mobile-link">Results</a>
        <a href="<?= e(url('standings')) ?>" class="mobile-link">Standings</a>
    </div>
</nav>

<!-- ===================== Page content ===================== -->
<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
    <?= $content ?>
</main>

<!-- ===================== Toasts ===================== -->
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

<?php include APP_PATH . '/views/partials/flash.php'; ?>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
