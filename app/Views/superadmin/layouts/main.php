<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <?php $_platformTitle = platform_setting('site_title', 'SOMAR Payroll Management System'); $_platformLogo = platform_logo_url(); ?>
    <title><?= esc($title ?? 'Superadmin') ?> – <?= esc($_platformTitle) ?></title>
    <?php if ($_platformLogo): ?>
    <link rel="icon" type="image/png" href="<?= esc($_platformLogo) ?>">
    <?php else: ?>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/default-favicon.svg') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>"/>
    <style>
        /* Deliberately distinct from the tenant app's blue theme, so it's
           unmistakable which console is open. */
        :root {
            --sidebar-bg:   #2e1065;
            --sidebar-text: #c4b5fd;
            --accent-color: #7c3aed;
            --topbar-bg:    #ffffff;
        }
        #sidebar { background: var(--sidebar-bg) !important; }
        #sidebar .nav-link, #sidebar .nav-section, #sidebar .sidebar-footer { color: var(--sidebar-text) !important; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: var(--accent-color) !important; color: #fff !important; }
        #sidebar .sidebar-brand h4 { color: #fff !important; }
        #topbar { background: var(--topbar-bg) !important; }
        .btn-primary { background-color: var(--accent-color) !important; border-color: var(--accent-color) !important; }
        a { color: var(--accent-color); }
        .flash-container {
            position: fixed; top: 1rem; right: 1rem;
            width: min(520px, calc(100vw - 2rem)); z-index: 2000;
        }
        .flash-container .alert { box-shadow: 0 10px 24px rgba(15, 23, 42, .18); border-width: 1px; }
    </style>
</head>
<body>

<nav id="sidebar">
    <div class="sidebar-brand">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <i class="fa-solid fa-user-shield" style="font-size: 1.5rem;"></i>
            <h4 style="margin: 0; font-size: 0.95rem;">Superadmin</h4>
        </div>
        <small>SOMAR Console</small>
    </div>

    <div class="sidebar-menu">
        <p class="nav-section">Main</p>
        <a class="nav-link" href="<?= site_url('superadmin') ?>">
            <i class="fa fa-gauge-high"></i> Dashboard
        </a>
        <a class="nav-link" href="<?= site_url('superadmin/companies') ?>">
            <i class="fa fa-building"></i> Companies
        </a>
        <a class="nav-link" href="<?= site_url('superadmin/companies/create') ?>">
            <i class="fa fa-circle-plus"></i> Add Company
        </a>

        <p class="nav-section">Platform</p>
        <a class="nav-link" href="<?= site_url('superadmin/admins') ?>">
            <i class="fa fa-user-shield"></i> Superadmins
        </a>
        <a class="nav-link" href="<?= site_url('superadmin/settings') ?>">
            <i class="fa fa-sliders"></i> Platform Settings
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;border-radius:50%;background:#7c3aed;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;">
                <?= strtoupper(substr(session()->get('superadmin_full_name') ?? 'S', 0, 1)) ?>
            </div>
            <div>
                <div style="color:#e2e8f0;font-size:.82rem;font-weight:500;"><?= esc(session()->get('superadmin_full_name') ?? '') ?></div>
                <div style="font-size:.72rem;">Superadmin</div>
            </div>
        </div>
    </div>
</nav>

<div id="main-content">
    <div id="topbar">
        <button id="sidebar-toggle" title="Toggle sidebar">
            <i class="fa fa-bars"></i>
        </button>
        <span class="topbar-title"><?= esc($title ?? '') ?></span>
        <div class="user-badge">
            <span class="text-muted small"><?= date('D, M j Y') ?></span>
            <a href="<?= site_url('superadmin/logout') ?>" class="btn btn-sm btn-outline-danger ms-3">
                <i class="fa fa-right-from-bracket me-1"></i>Logout
            </a>
        </div>
    </div>

    <div class="flash-container">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-check me-2"></i><?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-xmark me-2"></i><?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-triangle-exclamation me-2"></i>
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>

    <div class="page-body">
        <?= $this->renderSection('content') ?>
    </div>
</div>

<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/custom.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.flash-container .alert').forEach(function (el) {
        setTimeout(function () {
            const alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        }, 5000);
    });
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
