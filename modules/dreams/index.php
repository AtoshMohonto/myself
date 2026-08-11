<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Dreams';
$active_page = 'dreams';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/dreams/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE dreams SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/dreams/index.php', 'Dream deleted.', 'success');
    }

    if ($action === 'set_status') {
        db_query("UPDATE dreams SET status = ? WHERE id = ? AND user_id = ?", [$_POST['status'] ?? 'Active', (int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/dreams/index.php', 'Updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $timeframe = $_POST['timeframe'] ?? 'Someday';
    $notes = sanitize($_POST['notes'] ?? '');

    if ($title === '') {
        redirect('modules/dreams/index.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE dreams SET title=?, description=?, category=?, timeframe=?, notes=? WHERE id=? AND user_id=?",
            [$title, $description, $category, $timeframe, $notes, $id, $uid]);
        redirect('modules/dreams/index.php', 'Dream updated.', 'success');
    } else {
        db_query("INSERT INTO dreams (user_id, title, description, category, timeframe, notes) VALUES (?,?,?,?,?,?)",
            [$uid, $title, $description, $category, $timeframe, $notes]);
        log_activity('Added dream: ' . $title);
        redirect('modules/dreams/index.php', 'Dream added.', 'success');
    }
}

$editDream = null;
if (isset($_GET['edit'])) {
    $editDream = fetch_one("SELECT * FROM dreams WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$dreams = fetch_all("SELECT * FROM dreams WHERE user_id = ? ORDER BY FIELD(status,'Active','Achieved','Faded'), FIELD(timeframe,'Short-term','Long-term','Someday')", [$uid]);

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cloud-moon text-primary me-2"></i>Dreams</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editDream ? 'Edit Dream' : 'Add a Dream' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editDream['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editDream['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="Career, Travel, Financial..." value="<?= sanitize($editDream['category'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Timeframe</label>
                    <select name="timeframe" class="form-select">
                        <?php foreach (['Short-term','Long-term','Someday'] as $t): ?><option value="<?= $t ?>" <?= ($editDream['timeframe'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editDream ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($editDream['description'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editDream['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editDream): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="row">
    <?php if (empty($dreams)): ?>
        <div class="col-12"><div class="card shadow-sm"><div class="card-body text-center text-muted py-5">No dreams recorded yet.</div></div></div>
    <?php else: foreach ($dreams as $d): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0"><?= sanitize($d['title']) ?></h6>
                        <div class="text-nowrap">
                            <a href="?edit=<?= $d['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this dream?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark"><?= sanitize($d['category']) ?></span>
                    <span class="badge bg-secondary"><?= $d['timeframe'] ?></span>
                    <?php if ($d['description']): ?><p class="text-muted small mt-2 mb-2"><?= sanitize($d['description']) ?></p><?php endif; ?>
                    <form method="POST" class="mt-2">
                        <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach (['Active','Achieved','Faded'] as $s): ?><option value="<?= $s ?>" <?= $d['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
