<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'To-Do';
$active_page = 'todo';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/todo/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE todo_items SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/todo/index.php', 'Item deleted.', 'success');
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $item = fetch_one("SELECT * FROM todo_items WHERE id = ? AND user_id = ?", [$id, $uid]);
        if ($item) {
            $newDone = $item['is_done'] ? 0 : 1;
            $completedAt = $newDone ? date('Y-m-d H:i:s') : null;
            db_query("UPDATE todo_items SET is_done = ?, completed_at = ? WHERE id = ? AND user_id = ?", [$newDone, $completedAt, $id, $uid]);
        }
        redirect('modules/todo/index.php' . (isset($_POST['back']) ? '?' . $_POST['back'] : ''), '', '');
    }

    if ($action === 'clear_completed') {
        db_query("UPDATE todo_items SET isDelete = 1 WHERE user_id = ? AND is_done = 1", [$uid]);
        redirect('modules/todo/index.php', 'Completed items cleared.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $content = sanitize($_POST['content'] ?? '');
    $due_date = $_POST['due_date'] ?: null;

    if ($content === '') {
        redirect('modules/todo/index.php', 'Please enter what you need to do.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE todo_items SET content=?, due_date=? WHERE id=? AND user_id=?", [$content, $due_date, $id, $uid]);
        redirect('modules/todo/index.php', 'Item updated.', 'success');
    } else {
        db_query("INSERT INTO todo_items (user_id, content, due_date) VALUES (?,?,?)", [$uid, $content, $due_date]);
        redirect('modules/todo/index.php', 'Added to your to-do list.', 'success');
    }
}

$filter = $_GET['filter'] ?? 'all';
$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = fetch_one("SELECT * FROM todo_items WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}

$sql = "SELECT * FROM todo_items WHERE user_id = ?";
$params = [$uid];
if ($filter === 'active') { $sql .= " AND is_done = 0"; }
elseif ($filter === 'completed') { $sql .= " AND is_done = 1"; }
$sql .= " ORDER BY is_done ASC, (due_date IS NULL), due_date ASC, created_at DESC";
$items = fetch_all($sql, $params);

$counts = fetch_one("SELECT COUNT(*) as total, SUM(is_done=1) as done FROM todo_items WHERE user_id = ?", [$uid]);
$total = (int) ($counts['total'] ?? 0);
$done = (int) ($counts['done'] ?? 0);
$pct = $total > 0 ? round($done / $total * 100) : 0;
$today = date('Y-m-d');
$backQuery = $filter !== 'all' ? 'filter=' . urlencode($filter) : '';

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-square-check text-primary me-2"></i>To-Do</h1>
    <span class="text-muted"><?= $done ?> / <?= $total ?> done</span>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" class="todo-quick-add">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">
            <div class="row g-2 align-items-end">
                <div class="col">
                    <label class="form-label fw-bold small text-muted mb-1">What do you need to do?</label>
                    <input type="text" name="content" class="form-control form-control-lg" placeholder="Add a to-do and press Enter..." value="<?= sanitize($editItem['content'] ?? '') ?>" required autofocus>
                </div>
                <div class="col-auto">
                    <label class="form-label fw-bold small text-muted mb-1">Due</label>
                    <input type="date" name="due_date" class="form-control form-control-lg" value="<?= $editItem['due_date'] ?? '' ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-lg"><i class="fas fa-<?= $editItem ? 'save' : 'plus' ?> me-1"></i> <?= $editItem ? 'Update' : 'Add' ?></button>
                </div>
            </div>
            <?php if ($editItem): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2" style="min-width:200px;">
            <div class="progress flex-grow-1"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div>
            <span class="text-muted small fw-bold"><?= $pct ?>%</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group btn-group-sm">
                <a href="?filter=all" class="btn btn-outline-secondary <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                <a href="?filter=active" class="btn btn-outline-secondary <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
                <a href="?filter=completed" class="btn btn-outline-secondary <?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
            </div>
            <?php if ($done > 0): ?>
            <form method="POST" onsubmit="return confirm('Clear all completed items?');">
                <?= csrf_field() ?><input type="hidden" name="action" value="clear_completed">
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-broom me-1"></i>Clear Completed</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <?php if (empty($items)): ?>
            <div class="text-center text-muted py-5"><i class="fas fa-clipboard-check fa-2x mb-2 d-block"></i>
                <?= $filter === 'completed' ? 'Nothing completed yet.' : 'Nothing to do — enjoy the calm.' ?>
            </div>
        <?php else: foreach ($items as $i):
            $overdue = $i['due_date'] && $i['due_date'] < $today && !$i['is_done'];
            $isToday = $i['due_date'] === $today;
        ?>
            <div class="list-group-item todo-row d-flex align-items-center gap-3 py-3">
                <form method="POST" class="d-inline m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $i['id'] ?>">
                    <input type="hidden" name="back" value="<?= sanitize($backQuery) ?>">
                    <button type="submit" class="todo-check-btn <?= $i['is_done'] ? 'is-done' : '' ?>" title="<?= $i['is_done'] ? 'Mark as not done' : 'Mark as done' ?>">
                        <i class="fas <?= $i['is_done'] ? 'fa-check' : '' ?>"></i>
                    </button>
                </form>
                <span class="flex-grow-1 <?= $i['is_done'] ? 'text-decoration-line-through text-muted' : '' ?>"><?= sanitize($i['content']) ?></span>
                <?php if ($i['due_date']): ?>
                    <span class="badge <?= $overdue ? 'bg-danger' : ($isToday ? 'bg-primary' : 'bg-light text-dark') ?>">
                        <?= $overdue ? 'Overdue · ' : '' ?><?= $isToday ? 'Today' : format_date($i['due_date']) ?>
                    </span>
                <?php endif; ?>
                <div class="text-nowrap">
                    <a href="?edit=<?= $i['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this item?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
