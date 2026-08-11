<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Goals';
$active_page = 'goals';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/goals/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE goals SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/goals/index.php', 'Goal deleted.', 'success');
    }

    if ($action === 'update_progress') {
        $id = (int) ($_POST['id'] ?? 0);
        $progress = max(0, min(100, (int) ($_POST['progress'] ?? 0)));
        $status = $progress >= 100 ? 'Achieved' : 'Active';
        db_query("UPDATE goals SET progress = ?, status = ? WHERE id = ? AND user_id = ?", [$progress, $status, $id, $uid]);
        redirect('modules/goals/index.php', 'Progress updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'Personal';
    $priority = $_POST['priority'] ?? 'Medium';
    $target_date = $_POST['target_date'] ?: null;

    if ($title === '') {
        redirect('modules/goals/index.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE goals SET title=?, description=?, category=?, priority=?, target_date=? WHERE id=? AND user_id=?",
            [$title, $description, $category, $priority, $target_date, $id, $uid]);
        redirect('modules/goals/index.php', 'Goal updated.', 'success');
    } else {
        db_query("INSERT INTO goals (user_id, title, description, category, priority, target_date) VALUES (?,?,?,?,?,?)",
            [$uid, $title, $description, $category, $priority, $target_date]);
        log_activity('Added goal: ' . $title);
        redirect('modules/goals/index.php', 'Goal added.', 'success');
    }
}

$editGoal = null;
if (isset($_GET['edit'])) {
    $editGoal = fetch_one("SELECT * FROM goals WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$goals = fetch_all("SELECT * FROM goals WHERE user_id = ? ORDER BY FIELD(status,'Active','Achieved','Abandoned'), (target_date IS NULL), target_date ASC", [$uid]);
$categories = ['Career','Financial','Health','Education','Personal','Relationship','Spiritual','Other'];

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-bullseye text-primary me-2"></i>Goals</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editGoal ? 'Edit Goal' : 'Add Goal' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editGoal['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editGoal['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $c): ?><option value="<?= $c ?>" <?= ($editGoal['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Priority</label>
                    <select name="priority" class="form-select">
                        <?php foreach (['Low','Medium','High'] as $p): ?><option value="<?= $p ?>" <?= ($editGoal['priority'] ?? 'Medium') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Target Date</label>
                    <input type="date" name="target_date" class="form-control" value="<?= $editGoal['target_date'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editGoal ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($editGoal['description'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editGoal): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="row">
    <?php if (empty($goals)): ?>
        <div class="col-12"><div class="card shadow-sm"><div class="card-body text-center text-muted py-5">No goals yet. Set your first one above.</div></div></div>
    <?php else: foreach ($goals as $g): ?>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-1"><?= sanitize($g['title']) ?></h6>
                            <span class="badge bg-light text-dark"><?= $g['category'] ?></span>
                            <span class="badge bg-<?= status_badge_class($g['status']) ?>"><?= $g['status'] ?></span>
                            <span class="badge bg-<?= $g['priority'] === 'High' ? 'danger' : ($g['priority'] === 'Medium' ? 'warning' : 'secondary') ?>"><?= $g['priority'] ?></span>
                        </div>
                        <div class="text-nowrap">
                            <a href="?edit=<?= $g['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this goal?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                        </div>
                    </div>
                    <?php if ($g['description']): ?><p class="text-muted small mb-2"><?= sanitize($g['description']) ?></p><?php endif; ?>
                    <?php if ($g['target_date']): ?><p class="small text-muted mb-2"><i class="fas fa-flag-checkered me-1"></i> Target: <?= format_date($g['target_date']) ?></p><?php endif; ?>
                    <form method="POST" class="d-flex align-items-center gap-2">
                        <?= csrf_field() ?><input type="hidden" name="action" value="update_progress"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <div class="progress flex-grow-1"><div class="progress-bar" style="width:<?= (int) $g['progress'] ?>%"></div></div>
                        <input type="number" name="progress" min="0" max="100" value="<?= (int) $g['progress'] ?>" class="form-control form-control-sm" style="width:70px;">
                        <button class="btn btn-sm btn-outline-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
