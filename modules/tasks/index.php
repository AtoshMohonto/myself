<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Tasks';
$active_page = 'tasks';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/tasks/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE tasks SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/tasks/index.php', 'Task deleted.', 'success');
    }

    if ($action === 'set_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $completed_at = $status === 'Done' ? date('Y-m-d H:i:s') : null;
        db_query("UPDATE tasks SET status = ?, completed_at = ? WHERE id = ? AND user_id = ?", [$status, $completed_at, $id, $uid]);
        redirect('modules/tasks/index.php', 'Task updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $priority = $_POST['priority'] ?? 'Medium';
    $due_date = $_POST['due_date'] ?: null;

    if ($title === '') {
        redirect('modules/tasks/index.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE tasks SET title=?, description=?, category=?, priority=?, due_date=? WHERE id=? AND user_id=?",
            [$title, $description, $category, $priority, $due_date, $id, $uid]);
        redirect('modules/tasks/index.php', 'Task updated.', 'success');
    } else {
        db_query("INSERT INTO tasks (user_id, title, description, category, priority, due_date) VALUES (?,?,?,?,?,?)",
            [$uid, $title, $description, $category, $priority, $due_date]);
        log_activity('Added task: ' . $title);
        redirect('modules/tasks/index.php', 'Task added.', 'success');
    }
}

$filter_status = $_GET['status'] ?? '';
$editTask = null;
if (isset($_GET['edit'])) {
    $editTask = fetch_one("SELECT * FROM tasks WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}

$sql = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$uid];
if ($filter_status !== '') { $sql .= " AND status = ?"; $params[] = $filter_status; }
$sql .= " ORDER BY FIELD(status,'In Progress','Pending','Done','Cancelled'), (due_date IS NULL), due_date ASC";
$tasks = fetch_all($sql, $params);
$categories = category_options_with_current('tasks', $editTask['category'] ?? 'General');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-list-check text-primary me-2"></i>Tasks</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editTask ? 'Edit Task' : 'Add Task' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editTask['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editTask['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= sanitize($c) ?>" <?= ($editTask['category'] ?? 'General') === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Priority</label>
                    <select name="priority" class="form-select">
                        <?php foreach (['Low','Medium','High','Urgent'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($editTask['priority'] ?? 'Medium') === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?= $editTask['due_date'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> <?= $editTask ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($editTask['description'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editTask): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold">All Tasks</h6>
        <div class="btn-group btn-group-sm">
            <a href="?status=" class="btn btn-outline-secondary <?= $filter_status === '' ? 'active' : '' ?>">All</a>
            <a href="?status=Pending" class="btn btn-outline-secondary <?= $filter_status === 'Pending' ? 'active' : '' ?>">Pending</a>
            <a href="?status=In Progress" class="btn btn-outline-secondary <?= $filter_status === 'In Progress' ? 'active' : '' ?>">In Progress</a>
            <a href="?status=Done" class="btn btn-outline-secondary <?= $filter_status === 'Done' ? 'active' : '' ?>">Done</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No tasks yet.</td></tr>
                    <?php else: foreach ($tasks as $t):
                        $overdue = $t['due_date'] && $t['due_date'] < date('Y-m-d') && !in_array($t['status'], ['Done','Cancelled']);
                    ?>
                        <tr class="<?= $t['status'] === 'Done' ? 'text-decoration-line-through text-muted' : '' ?>">
                            <td class="fw-bold"><?= sanitize($t['title']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($t['category']) ?></span></td>
                            <td><span class="badge bg-<?= $t['priority'] === 'Urgent' ? 'danger' : ($t['priority'] === 'High' ? 'warning' : 'secondary') ?>"><?= $t['priority'] ?></span></td>
                            <td class="<?= $overdue ? 'text-danger fw-bold' : '' ?>"><?= $t['due_date'] ? format_date($t['due_date']) : '-' ?><?= $overdue ? ' <i class="fas fa-exclamation-triangle"></i>' : '' ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        <?php foreach (['Pending','In Progress','Done','Cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $t['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
