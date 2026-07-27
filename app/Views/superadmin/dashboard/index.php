<?= $this->extend('superadmin/layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Total Companies</div>
            <div class="fs-3 fw-bold"><?= $total ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Trial</div>
            <div class="fs-3 fw-bold text-warning"><?= $counts['trial'] ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Active</div>
            <div class="fs-3 fw-bold text-success"><?= $counts['active'] ?></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Suspended / Cancelled</div>
            <div class="fs-3 fw-bold text-danger"><?= $counts['suspended'] + $counts['cancelled'] ?></div>
        </div></div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Recently Added Companies</h5>
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
                        <th>Name</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No companies yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($c['name']) ?></td>
                        <td class="font-monospace"><?= esc($c['slug']) ?></td>
                        <td>
                            <span class="badge <?= match($c['status']) { 'active' => 'bg-success', 'trial' => 'bg-warning text-dark', default => 'bg-danger' } ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= $c['created_at'] ? date('M j, Y', strtotime($c['created_at'])) : '—' ?></td>
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
