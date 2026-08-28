<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Bucket List';
$active_page = 'bucket_list';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/bucket_list/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE bucket_list SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/bucket_list/index.php', 'Item deleted.', 'success');
    }

    if ($action === 'set_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Not Started';
        $completed_date = $status === 'Done' ? date('Y-m-d') : null;
        db_query("UPDATE bucket_list SET status = ?, completed_date = ? WHERE id = ? AND user_id = ?", [$status, $completed_date, $id, $uid]);
        redirect('modules/bucket_list/index.php', 'Updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $priority = $_POST['priority'] ?? 'Medium';
    $target_date = $_POST['target_date'] ?: null;
    $notes = sanitize($_POST['notes'] ?? '');

    if ($title === '') {
        redirect('modules/bucket_list/index.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE bucket_list SET title=?, description=?, category=?, priority=?, target_date=?, notes=? WHERE id=? AND user_id=?",
            [$title, $description, $category, $priority, $target_date, $notes, $id, $uid]);
        redirect('modules/bucket_list/index.php', 'Item updated.', 'success');
    } else {
        db_query("INSERT INTO bucket_list (user_id, title, description, category, priority, target_date, notes) VALUES (?,?,?,?,?,?,?)",
            [$uid, $title, $description, $category, $priority, $target_date, $notes]);
        log_activity('Added bucket list item: ' . $title);
        redirect('modules/bucket_list/index.php', 'Item added.', 'success');
    }
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = fetch_one("SELECT * FROM bucket_list WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$items = fetch_all("SELECT * FROM bucket_list WHERE user_id = ? ORDER BY FIELD(status,'In Progress','Not Started','Done')", [$uid]);
$total = count($items);
$done = count(array_filter($items, fn($i) => $i['status'] === 'Done'));
$categories = category_options_with_current('bucket_list', $editItem['category'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-list-ol text-primary me-2"></i>Bucket List</h1>
    <span class="text-muted"><?= $done ?> / <?= $total ?> completed</span>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editItem ? 'Edit Item' : 'Add Bucket List Item' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editItem['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $c): ?><option value="<?= sanitize($c) ?>" <?= ($editItem['category'] ?? '') === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Priority</label>
                    <select name="priority" class="form-select">
                        <?php foreach (['Low','Medium','High'] as $p): ?><option value="<?= $p ?>" <?= ($editItem['priority'] ?? 'Medium') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Target Date</label>
                    <input type="date" name="target_date" class="form-control" value="<?= $editItem['target_date'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editItem ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($editItem['description'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editItem['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editItem): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Target</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Nothing on your bucket list yet.</td></tr>
                    <?php else: foreach ($items as $i): ?>
                        <tr class="<?= $i['status'] === 'Done' ? 'text-decoration-line-through text-muted' : '' ?>">
                            <td class="fw-bold"><?= sanitize($i['title']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($i['category']) ?></span></td>
                            <td><span class="badge bg-<?= $i['priority'] === 'High' ? 'danger' : ($i['priority'] === 'Medium' ? 'warning' : 'secondary') ?>"><?= $i['priority'] ?></span></td>
                            <td><?= $i['target_date'] ? format_date($i['target_date']) : '-' ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $i['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        <?php foreach (['Not Started','In Progress','Done'] as $s): ?><option value="<?= $s ?>" <?= $i['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $i['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this item?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
