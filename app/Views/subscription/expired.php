<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="login-card">
    <div class="login-header">
        <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-bottom: 0.75rem;">
            <div style="font-size:2.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0">Subscription Inactive</h3>
                <p class="mb-0 opacity-75 small">Access is currently unavailable</p>
            </div>
        </div>
    </div>
    <div class="login-body">
        <p class="text-center text-muted mb-4">
            Your company's account is not currently active. Please contact your
            administrator or our support team to restore access. Your data is
            safe and will be available as soon as your account is reactivated.
        </p>
        <a href="<?= site_url('logout') ?>" class="btn btn-primary w-100 py-2">
            <i class="fa fa-right-from-bracket me-2"></i>Log Out
        </a>
    </div>
</div>

<?= $this->endSection() ?>
