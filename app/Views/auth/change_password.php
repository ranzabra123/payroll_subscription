<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="login-card">
    <div class="login-header">
        <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-bottom: 0.75rem;">
            <div style="font-size:2.5rem;">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0">Set Your Password</h3>
                <p class="mb-0 opacity-75 small">Required before you can continue</p>
            </div>
        </div>
    </div>
    <div class="login-body">
        <p class="text-center text-muted mb-4">
            Your account was set up with a temporary password. Please set
            your own password to continue.
        </p>
        <form action="<?= site_url('change-password') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="current_password">
                    <i class="fa fa-lock text-muted me-1"></i>Current (Temporary) Password
                </label>
                <input type="password" name="current_password" id="current_password"
                       class="form-control" placeholder="Enter your current password" required/>
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password">
                    <i class="fa fa-key text-muted me-1"></i>New Password
                </label>
                <input type="password" name="new_password" id="new_password"
                       class="form-control" placeholder="At least 6 characters" required/>
            </div>
            <div class="mb-4">
                <label class="form-label" for="confirm_password">
                    <i class="fa fa-key text-muted me-1"></i>Confirm New Password
                </label>
                <input type="password" name="confirm_password" id="confirm_password"
                       class="form-control" placeholder="Re-enter your new password" required/>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fa fa-check me-2"></i>Set Password &amp; Continue
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= site_url('logout') ?>" class="text-muted small">
                <i class="fa fa-right-from-bracket me-1"></i>Log out instead
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
