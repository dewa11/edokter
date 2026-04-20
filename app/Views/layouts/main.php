<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \App\Helpers\App::e((string) ($title ?? 'Edokter')) ?></title>
    <link rel="icon" type="image/png" href="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'setting/logo/logo.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/css/app.css')) ?>" rel="stylesheet">
    <link href="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/css/dashboard.css')) ?>" rel="stylesheet">
</head>
<body>
<?php $isItUser = \App\Helpers\Auth::isItUser(); ?>
<div class="dashboard-wrapper" id="dashboardWrapper">
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-top">
            <div class="d-flex align-items-center gap-2 mb-3">
                <img src="<?= \App\Helpers\App::e((string) $logoUrl) ?>" alt="Logo" class="sidebar-logo">
                <div>
                    <p class="mb-0 fw-semibold">eDokter</p>
                    <small class="text-secondary">RSU Thalia Irham</small>
                </div>
            </div>
            <nav class="nav flex-column sidebar-nav">
                <a class="nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= \App\Helpers\App::e((string) call_user_func($routePath, $isItUser ? '/it/dashboard' : '/dashboard')) ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <?php if (!$isItUser): ?>
                    <a class="nav-link <?= ($activeMenu ?? '') === 'poli' ? 'active' : '' ?>" href="<?= \App\Helpers\App::e((string) call_user_func($routePath, '/poli')) ?>">
                        <i class="bi bi-hospital"></i>
                        <span>Poli</span>
                    </a>
                <?php endif; ?>
                <a class="nav-link <?= ($activeMenu ?? '') === 'rawat-inap' ? 'active' : '' ?>" href="<?= \App\Helpers\App::e((string) call_user_func($routePath, $isItUser ? '/it/rawat-inap' : '/rawat-inap')) ?>">
                    <i class="bi bi-building"></i>
                    <span>Rawat Inap</span>
                </a>
            </nav>
        </div>
        <div class="sidebar-bottom">
            <a class="nav-link text-danger" href="<?= \App\Helpers\App::e((string) call_user_func($routePath, '/logout')) ?>">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="main-panel">
        <header class="topbar">
            <button class="btn btn-outline-primary btn-sm" id="sidebarToggle" type="button">
                <i class="bi bi-list"></i>
            </button>
            <div class="ms-auto text-end">
                <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e((string) ($pageTitle ?? 'Dashboard')) ?></p>
                <small class="text-secondary">User: <?= \App\Helpers\App::e((string) ($doctorId ?: 'Unknown')) ?></small>
            </div>
        </header>

        <main class="content-area">
            <?= $content ?>
        </main>

        <footer class="app-footer">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-secondary">Selamat datang, <?= \App\Helpers\App::e((string) (($doctorName ?? '') !== '' ? $doctorName : 'Aplikasi eDokter')) ?></small>
                <small class="footer-watermark">Made by RVL</small>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/js/sidebar.js')) ?>"></script>
<script src="<?= \App\Helpers\App::e((string) call_user_func($assetPath, 'assets/js/app.js')) ?>"></script>
</body>
</html>
