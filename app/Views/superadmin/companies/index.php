<?= $this->extend('superadmin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Companies</h5>
    <a href="<?= site_url('superadmin/companies/create') ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-circle-plus me-1"></i>Add Company
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Database</th>
                        <th>Status</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($companies)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No companies found.</td></tr>
                <?php else: ?>
                    <?php foreach ($companies as $i => $c): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= esc($c['name']) ?></td>
                        <td class="font-monospace"><?= esc($c['slug']) ?></td>
                        <td class="font-monospace text-muted small"><?= esc($c['db_name']) ?></td>
                        <td>
                            <span class="badge <?= match($c['status']) { 'active' => 'bg-success', 'trial' => 'bg-warning text-dark', default => 'bg-danger' } ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?= $c['subscription_expires_at'] ? date('M j, Y', strtotime($c['subscription_expires_at'])) : '—' ?>
                        </td>
                        <td>
                            <a href="<?= site_url('superadmin/companies/view/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
