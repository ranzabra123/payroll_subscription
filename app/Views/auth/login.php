<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<?php
// NOTE: this page renders before a tenant/company is known, so it must
// never call setting()/setting_logo_url() — those read from whatever
// tenant DB happens to be connected, which would leak another company's
// branding onto the shared login page. platform_setting()/
// platform_logo_url() read from the landlord DB instead, which is
// always available and the same for every visitor here, so they're
// safe to use.
$_companyName = platform_setting('site_title', 'SOMAR Payroll Management System');
$_logoUrl = platform_logo_url();
?>

<div class="login-card">
    <div class="login-header">
        <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-bottom: 0.75rem;">
            <?php if ($_logoUrl): ?>
            <img src="<?= esc($_logoUrl) ?>" alt="<?= esc($_companyName) ?> Logo"
                 style="max-height:64px;max-width:150px;object-fit:contain;flex-shrink:0;"/>
            <?php else: ?>
            <div style="font-size:2.5rem;">
                <i class="fa-solid fa-peso-sign"></i>
            </div>
            <?php endif; ?>
            <div>
                <h3 class="fw-bold mb-0"><?= esc($_companyName) ?></h3>
            </div>
        </div>
    </div>
    <div class="login-body">
        <form action="<?= site_url('login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="company_code">
                    <i class="fa fa-building text-muted me-1"></i>Company Code
                </label>
                <input type="text" name="company_code" id="company_code"
                       class="form-control"
                       value="<?= old('company_code') ?>"
                       placeholder="Enter your company code"
                       required autofocus/>
            </div>
            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="fa fa-user text-muted me-1"></i>Username
                </label>
                <input type="text" name="username" id="username"
                       class="form-control"
                       value="<?= old('username') ?>"
                       placeholder="Enter username"
                       required/>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">
                    <i class="fa fa-lock text-muted me-1"></i>Password
                </label>
                <div class="input-group">
                    <input type="password" name="password" id="password"
                           class="form-control"
                           placeholder="Enter password"
                           required/>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePw(this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fa fa-right-to-bracket me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-3 text-muted small">
            <i class="fa fa-shield-halved me-1"></i>Secured login
        </div>
    </div>
</div>

<script>
function togglePw(btn) {
    const inp = btn.previousElementSibling;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').classList.toggle('fa-eye');
    btn.querySelector('i').classList.toggle('fa-eye-slash');
}
</script>

<?= $this->endSection() ?>
