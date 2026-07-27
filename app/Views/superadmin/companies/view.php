<?= $this->extend('superadmin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('superadmin/companies') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold"><?= esc($company['name']) ?></h5>
    <span class="badge <?= match($company['status']) { 'active' => 'bg-success', 'trial' => 'bg-warning text-dark', default => 'bg-danger' } ?>">
        <?= ucfirst($company['status']) ?>
    </span>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Company Details</h6>
                <table class="table table-sm mb-0">
                    <tr><th class="text-muted" style="width:40%;">Login Code</th><td class="font-monospace"><?= esc($company['slug']) ?></td></tr>
                    <tr>
                        <th class="text-muted">Plan</th>
                        <td>
                            <?php if (\App\Libraries\SubscriptionPlans::exists($company['subscription_plan'])): ?>
                            <span class="fw-semibold"><?= esc(\App\Libraries\SubscriptionPlans::label($company['subscription_plan'])) ?></span>
                            <span class="text-muted small">
                                (<?= esc(\App\Libraries\SubscriptionPlans::employeesLabel($company['subscription_plan'])) ?> employees,
                                <?= esc(\App\Libraries\SubscriptionPlans::branchesLabel($company['subscription_plan'])) ?>)
                            </span>
                            <?php else: ?>
                            <span class="text-muted">No plan assigned</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><th class="text-muted">Database</th><td class="font-monospace"><?= esc($company['db_name']) ?></td></tr>
                    <tr><th class="text-muted">DB Host</th><td class="font-monospace"><?= esc($company['db_host']) ?></td></tr>
                    <tr><th class="text-muted">Trial Ends</th><td><?= $company['trial_ends_at'] ? date('M j, Y', strtotime($company['trial_ends_at'])) : '—' ?></td></tr>
                    <tr><th class="text-muted">Subscription Expires</th><td><?= $company['subscription_expires_at'] ? date('M j, Y', strtotime($company['subscription_expires_at'])) : '—' ?></td></tr>
                    <tr><th class="text-muted">Created</th><td><?= $company['created_at'] ? date('M j, Y g:i a', strtotime($company['created_at'])) : '—' ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Update Status</h6>
                <form action="<?= site_url('superadmin/companies/status/' . $company['id']) ?>" method="POST" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <select name="status" class="form-select">
                        <option value="trial"     <?= $company['status'] === 'trial'     ? 'selected' : '' ?>>Trial</option>
                        <option value="active"    <?= $company['status'] === 'active'    ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $company['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="cancelled" <?= $company['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary text-nowrap">Save</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Extend Subscription</h6>
                <form action="<?= site_url('superadmin/companies/expiry/' . $company['id']) ?>" method="POST" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <input type="date" name="subscription_expires_at" class="form-control"
                           value="<?= $company['subscription_expires_at'] ? date('Y-m-d', strtotime($company['subscription_expires_at'])) : '' ?>"/>
                    <button type="submit" class="btn btn-primary text-nowrap">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Upgrade or Downgrade Plan</h6>
                <form action="<?= site_url('superadmin/companies/plan/' . $company['id']) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:2.5rem;"></th>
                                    <th>Plan</th>
                                    <th>Employees</th>
                                    <th>Branches</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $key => $p): ?>
                                <tr>
                                    <td>
                                        <input type="radio" name="plan" value="<?= esc($key) ?>" class="form-check-input"
                                               <?= $company['subscription_plan'] === $key ? 'checked' : '' ?> required/>
                                    </td>
                                    <td class="fw-semibold"><?= esc($p['label']) ?></td>
                                    <td><?= esc(\App\Libraries\SubscriptionPlans::employeesLabel($key)) ?></td>
                                    <td><?= esc(\App\Libraries\SubscriptionPlans::branchesLabel($key)) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-right-left me-1"></i>Update Plan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Update User Credentials</h6>
                <?php if (empty($tenantUsers)): ?>
                <p class="text-muted mb-0">
                    <i class="fa fa-triangle-exclamation me-1"></i>
                    Could not load this company's users (tenant database unreachable).
                </p>
                <?php else: ?>
                <form action="<?= site_url('superadmin/companies/reset-credentials/' . $company['id']) ?>" method="POST" class="row g-3 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-md-3">
                        <label class="form-label">User</label>
                        <select name="user_id" id="cred_user_id" class="form-select" required>
                            <?php foreach ($tenantUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" data-username="<?= esc($u['username']) ?>">
                                <?= esc($u['username']) ?> (<?= esc(ucfirst($u['role'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="cred_username" class="form-control"
                               value="<?= esc($tenantUsers[0]['username']) ?>" required/>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="cred_password" class="form-control"
                                   placeholder="Leave blank to keep current password"/>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateTempPassword()">
                                <i class="fa fa-dice me-1"></i>Generate
                            </button>
                        </div>
                        <div class="form-text">Auto-generated passwords are 8 characters, mixed case + digits. Shown once — copy it before saving.</div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa fa-save me-1"></i>Save
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('cred_user_id')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('cred_username').value = opt.dataset.username || '';
    document.getElementById('cred_password').value = '';
});

function generateTempPassword() {
    // Excludes visually ambiguous characters (0/O, 1/l/I) since this is
    // meant to be read off the screen and typed/copied to the client.
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    let pwd = '';
    for (let i = 0; i < 8; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const field = document.getElementById('cred_password');
    field.type = 'text';
    field.value = pwd;
}
</script>

<?= $this->endSection() ?>
