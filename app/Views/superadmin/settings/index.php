<?= $this->extend('superadmin/layouts/main') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 fw-semibold">Platform Settings</h5>
<p class="text-muted">
    Branding shown before any company is known — the shared login page,
    the superadmin console, and the browser tab icon/title.
</p>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-heading me-2"></i>Site Title</div>
            <div class="card-body">
                <form action="<?= site_url('superadmin/settings/general') ?>" method="POST">
                    <?= csrf_field() ?>
                    <label class="form-label fw-medium">Browser Tab Title</label>
                    <input type="text" name="site_title" class="form-control"
                           value="<?= esc($cfg['site_title'] ?? 'SOMAR Payroll Management System') ?>" required/>
                    <div class="form-text">Shown in the browser tab and on the shared login page.</div>
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="fa fa-save me-1"></i>Save
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-image me-2"></i>Platform Logo</div>
            <div class="card-body">
                <?php $logoUrl = platform_logo_url(); ?>
                <div class="mb-4">
                    <label class="form-label fw-medium">Current Logo</label>
                    <div class="border rounded p-3 d-inline-flex align-items-center gap-3"
                         style="background:#f8fafc;min-width:250px;">
                        <?php if ($logoUrl): ?>
                            <img src="<?= esc($logoUrl) ?>" alt="Logo" style="max-height:80px;max-width:200px;object-fit:contain;"/>
                            <div>
                                <div class="text-muted small mb-1">Used as the browser tab icon (favicon)</div>
                                <a href="<?= site_url('superadmin/settings/logo/remove') ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-confirm="Remove the current platform logo?">
                                    <i class="fa fa-trash me-1"></i>Remove
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="fa fa-image fa-2x mb-2 d-block"></i>
                                No logo uploaded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <form action="<?= site_url('superadmin/settings/logo') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label class="form-label fw-medium">Upload New Logo</label>
                    <div class="d-flex gap-2 align-items-start flex-wrap">
                        <div>
                            <input type="file" name="logo" class="form-control" accept="image/*" required/>
                            <div class="form-text">JPG, PNG, GIF, WEBP, SVG or ICO — max 2 MB.</div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-0">
                            <i class="fa fa-upload me-1"></i>Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-life-ring me-2"></i>Onboarding Tour Links</div>
            <div class="card-body">
                <p class="text-muted small">
                    Shown on the completion screen of every tenant's interactive
                    product tour. Leave blank to hide a link rather than point it
                    somewhere unfinished.
                </p>
                <form action="<?= site_url('superadmin/settings/support') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Documentation URL</label>
                        <input type="text" name="docs_url" class="form-control"
                               value="<?= esc($cfg['docs_url'] ?? '') ?>" placeholder="https://docs.example.com"/>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Contact Support Link</label>
                        <input type="text" name="support_url" class="form-control"
                               value="<?= esc($cfg['support_url'] ?? '') ?>" placeholder="mailto:support@example.com"/>
                        <div class="form-text">Any link works — mailto:, tel:, or a URL.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>Save
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
