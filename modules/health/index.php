<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Health Records';
$active_page = 'health';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/health/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE health_records SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/health/index.php', 'Record deleted.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $record_type = $_POST['record_type'] ?? 'Checkup';
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $record_date = $_POST['record_date'] ?: date('Y-m-d');
    $doctor_name = sanitize($_POST['doctor_name'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($title === '') {
        redirect('modules/health/index.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE health_records SET record_type=?, title=?, description=?, record_date=?, doctor_name=?, notes=? WHERE id=? AND user_id=?",
            [$record_type, $title, $description, $record_date, $doctor_name, $notes, $id, $uid]);
        redirect('modules/health/index.php', 'Record updated.', 'success');
    } else {
        db_query("INSERT INTO health_records (user_id, record_type, title, description, record_date, doctor_name, notes) VALUES (?,?,?,?,?,?,?)",
            [$uid, $record_type, $title, $description, $record_date, $doctor_name, $notes]);
        log_activity('Added health record: ' . $title);
        redirect('modules/health/index.php', 'Record added.', 'success');
    }
}

$editRecord = null;
if (isset($_GET['edit'])) {
    $editRecord = fetch_one("SELECT * FROM health_records WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$records = fetch_all("SELECT * FROM health_records WHERE user_id = ? ORDER BY record_date DESC", [$uid]);
$categories = category_options_with_current('health_type', $editRecord['record_type'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-heart-pulse text-primary me-2"></i>Health Records</h1>
    <a href="<?= BASE_URL ?>modules/medicines/index.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-pills me-1"></i> Medicines</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editRecord ? 'Edit Record' : 'Add Health Record' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editRecord['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <select name="record_type" class="form-select">
                        <?php foreach ($categories as $t): ?><option value="<?= sanitize($t) ?>" <?= ($editRecord['record_type'] ?? '') === $t ? 'selected' : '' ?>><?= sanitize($t) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editRecord['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" name="record_date" class="form-control" value="<?= $editRecord['record_date'] ?? date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Doctor / Facility</label>
                    <input type="text" name="doctor_name" class="form-control" value="<?= sanitize($editRecord['doctor_name'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editRecord ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($editRecord['description'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editRecord['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editRecord): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Doctor/Facility</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No health records yet.</td></tr>
                    <?php else: foreach ($records as $r): ?>
                        <tr>
                            <td><?= format_date($r['record_date']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($r['record_type']) ?></span></td>
                            <td class="fw-bold"><?= sanitize($r['title']) ?></td>
                            <td><?= sanitize($r['doctor_name'] ?? '-') ?></td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this record?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
