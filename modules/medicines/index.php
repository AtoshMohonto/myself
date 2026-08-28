<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Medicines';
$active_page = 'medicines';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/medicines/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE medicines SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/medicines/index.php', 'Medicine deleted.', 'success');
    }

    if ($action === 'adjust_stock') {
        $id = (int) ($_POST['id'] ?? 0);
        $delta = (int) ($_POST['delta'] ?? 0);
        db_query("UPDATE medicines SET stock_quantity = GREATEST(0, stock_quantity + ?) WHERE id = ? AND user_id = ?", [$delta, $id, $uid]);
        redirect('modules/medicines/index.php', 'Stock updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $dosage = sanitize($_POST['dosage'] ?? '');
    $form = $_POST['form'] ?? 'Tablet';
    $stock_quantity = (int) ($_POST['stock_quantity'] ?? 0);
    $unit = sanitize($_POST['unit'] ?? 'pcs');
    $frequency = sanitize($_POST['frequency'] ?? '');
    $schedule_times = sanitize($_POST['schedule_times'] ?? '');
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $expiry_date = $_POST['expiry_date'] ?: null;
    $status = $_POST['status'] ?? 'Active';
    $notes = sanitize($_POST['notes'] ?? '');

    if ($name === '') {
        redirect('modules/medicines/index.php', 'Medicine name is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE medicines SET name=?, dosage=?, form=?, stock_quantity=?, unit=?, frequency=?, schedule_times=?, start_date=?, end_date=?, expiry_date=?, status=?, notes=? WHERE id=? AND user_id=?",
            [$name, $dosage, $form, $stock_quantity, $unit, $frequency, $schedule_times, $start_date, $end_date, $expiry_date, $status, $notes, $id, $uid]);
        redirect('modules/medicines/index.php', 'Medicine updated.', 'success');
    } else {
        db_query("INSERT INTO medicines (user_id, name, dosage, form, stock_quantity, unit, frequency, schedule_times, start_date, end_date, expiry_date, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$uid, $name, $dosage, $form, $stock_quantity, $unit, $frequency, $schedule_times, $start_date, $end_date, $expiry_date, $status, $notes]);
        log_activity('Added medicine: ' . $name);
        redirect('modules/medicines/index.php', 'Medicine added.', 'success');
    }
}

$editMed = null;
if (isset($_GET['edit'])) {
    $editMed = fetch_one("SELECT * FROM medicines WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$medicines = fetch_all("SELECT * FROM medicines WHERE user_id = ? ORDER BY FIELD(status,'Active','Completed','Stopped'), name ASC", [$uid]);
$categories = category_options_with_current('medicine_form', $editMed['form'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-pills text-primary me-2"></i>Medicines</h1>
    <a href="<?= BASE_URL ?>modules/health/index.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-heart-pulse me-1"></i> Health Records</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editMed ? 'Edit Medicine' : 'Add Medicine' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editMed['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($editMed['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Dosage</label>
                    <input type="text" name="dosage" class="form-control" placeholder="500mg" value="<?= sanitize($editMed['dosage'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Form</label>
                    <select name="form" class="form-select">
                        <?php foreach ($categories as $f): ?><option value="<?= sanitize($f) ?>" <?= ($editMed['form'] ?? '') === $f ? 'selected' : '' ?>><?= sanitize($f) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Stock</label>
                    <input type="number" name="stock_quantity" class="form-control" value="<?= $editMed['stock_quantity'] ?? 0 ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-bold">Unit</label>
                    <input type="text" name="unit" class="form-control" value="<?= sanitize($editMed['unit'] ?? 'pcs') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Active','Completed','Stopped'] as $s): ?><option value="<?= $s ?>" <?= ($editMed['status'] ?? 'Active') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Frequency</label>
                    <input type="text" name="frequency" class="form-control" placeholder="Twice daily" value="<?= sanitize($editMed['frequency'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Schedule Times</label>
                    <input type="text" name="schedule_times" class="form-control" placeholder="08:00, 20:00" value="<?= sanitize($editMed['schedule_times'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $editMed['start_date'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $editMed['end_date'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= $editMed['expiry_date'] ?? '' ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editMed['notes'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editMed ? 'Update' : 'Add' ?></button>
                </div>
            </div>
            <?php if ($editMed): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Dosage</th><th>Frequency</th><th>Stock</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No medicines tracked yet.</td></tr>
                    <?php else: foreach ($medicines as $m):
                        $expiringSoon = $m['expiry_date'] && strtotime($m['expiry_date']) < strtotime('+30 days');
                        $lowStock = $m['stock_quantity'] <= 5;
                    ?>
                        <tr>
                            <td class="fw-bold"><?= sanitize($m['name']) ?> <span class="text-muted small"><?= sanitize($m['form']) ?></span></td>
                            <td><?= sanitize($m['dosage'] ?? '-') ?></td>
                            <td class="text-sm"><?= sanitize($m['frequency'] ?? '-') ?><?php if ($m['schedule_times']): ?><br><span class="text-muted"><?= sanitize($m['schedule_times']) ?></span><?php endif; ?></td>
                            <td>
                                <span class="badge bg-<?= $lowStock ? 'danger' : 'success' ?>"><?= (int) $m['stock_quantity'] ?> <?= sanitize($m['unit']) ?></span>
                                <div class="btn-group btn-group-sm mt-1">
                                    <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="adjust_stock"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="delta" value="-1"><button class="btn btn-xs btn-outline-secondary">-</button></form>
                                    <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="adjust_stock"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="delta" value="1"><button class="btn btn-xs btn-outline-secondary">+</button></form>
                                </div>
                            </td>
                            <td class="<?= $expiringSoon ? 'text-danger fw-bold' : '' ?>"><?= $m['expiry_date'] ? format_date($m['expiry_date']) : '-' ?></td>
                            <td><span class="badge bg-<?= status_badge_class($m['status']) ?>"><?= $m['status'] ?></span></td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $m['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this medicine?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
