<?= $this->extend('superadmin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('superadmin/companies') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Add Company</h5>
</div>

<div class="card" style="max-width:760px;">
    <div class="card-body">
        <form action="<?= site_url('superadmin/companies/store') ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="company_name" class="form-control"
                       value="<?= old('name') ?>" required/>
            </div>

            <div class="mb-3">
                <label class="form-label">Company Code <span class="text-danger">*</span></label>
                <input type="text" name="slug" id="company_slug" class="form-control font-monospace"
                       value="<?= old('slug') ?>" pattern="[a-z0-9_]{2,40}" required/>
                <div class="form-text">
                    Lowercase letters, numbers, underscores only. This is what the company
                    types on the login page, and becomes database <code>payroll_&lt;code&gt;</code>.
                </div>
            </div>

            <hr class="my-4"/>
            <h6 class="fw-semibold mb-3">Subscription Plan</h6>
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
                        <?php $_selectedPlan = old('plan', 'starter'); ?>
                        <?php foreach ($plans as $key => $p): ?>
                        <tr>
                            <td>
                                <input type="radio" name="plan" value="<?= esc($key) ?>" class="form-check-input"
                                       <?= $_selectedPlan === $key ? 'checked' : '' ?> required/>
                            </td>
                            <td class="fw-semibold"><?= esc($p['label']) ?></td>
                            <td><?= esc(\App\Libraries\SubscriptionPlans::employeesLabel($key)) ?></td>
                            <td><?= esc(\App\Libraries\SubscriptionPlans::branchesLabel($key)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <hr class="my-4"/>
            <h6 class="fw-semibold mb-3">Initial Company Admin Account</h6>

            <div class="mb-3">
                <label class="form-label">Admin Full Name <span class="text-danger">*</span></label>
                <input type="text" name="admin_full_name" class="form-control"
                       value="<?= old('admin_full_name') ?>" required/>
            </div>

            <div class="row g-3 mb-3">
                <div class="col">
                    <label class="form-label">Admin Username <span class="text-danger">*</span></label>
                    <input type="text" name="admin_username" class="form-control"
                           value="<?= old('admin_username') ?>" required/>
                </div>
                <div class="col">
                    <label class="form-label">Admin Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="admin_password" id="admin_password" class="form-control" required/>
                        <button type="button" class="btn btn-outline-secondary" onclick="generateTempPassword()">
                            <i class="fa fa-dice me-1"></i>Generate
                        </button>
                    </div>
                    <div class="form-text">At least 6 characters. The admin will be required to set their own password on first login.</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-database me-1"></i>Provision Company
                </button>
                <a href="<?= site_url('superadmin/companies') ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Convenience only — the server always re-validates the code strictly.
document.getElementById('company_name').addEventListener('input', function () {
    const slugField = document.getElementById('company_slug');
    if (slugField.dataset.touched) return;
    slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 40);
});
document.getElementById('company_slug').addEventListener('input', function () {
    this.dataset.touched = '1';
});

function generateTempPassword() {
    // Excludes visually ambiguous characters (0/O, 1/l/I) since this is
    // meant to be read off the screen and typed/copied to the client.
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    let pwd = '';
    for (let i = 0; i < 8; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const field = document.getElementById('admin_password');
    field.type = 'text';
    field.value = pwd;
}
</script>

<?= $this->endSection() ?>
