<?= $this->extend('superadmin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('superadmin/admins') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit Superadmin</h5>
</div>

<div class="card" style="max-width:540px;">
    <div class="card-body">
        <form action="<?= site_url('superadmin/admins/update/' . $admin['id']) ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= esc(old('full_name', $admin['full_name'])) ?>" required/>
            </div>

            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control"
                       value="<?= esc(old('username', $admin['username'])) ?>" required/>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password"/>
                <div class="form-text">Leave blank to keep current password.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active"   <?= old('status', $admin['status']) === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= old('status', $admin['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <?php if ((int) $admin['id'] === (int) session()->get('superadmin_id')): ?>
                <div class="form-text">You can't deactivate yourself if you're the last active superadmin.</div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>Update Superadmin
                </button>
                <a href="<?= site_url('superadmin/admins') ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
