<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Skills';
$active_page = 'skills';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/skills/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE skills SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/skills/index.php', 'Skill deleted.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'Hard Skill';
    $category = sanitize($_POST['category'] ?? 'General');
    $proficiency_level = $_POST['proficiency_level'] ?? 'Beginner';
    $learning_status = $_POST['learning_status'] ?? 'Learning';
    $resources = sanitize($_POST['resources'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($name === '') {
        redirect('modules/skills/index.php', 'Skill name is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE skills SET name=?, type=?, category=?, proficiency_level=?, learning_status=?, resources=?, notes=? WHERE id=? AND user_id=?",
            [$name, $type, $category, $proficiency_level, $learning_status, $resources, $notes, $id, $uid]);
        redirect('modules/skills/index.php', 'Skill updated.', 'success');
    } else {
        db_query("INSERT INTO skills (user_id, name, type, category, proficiency_level, learning_status, resources, notes) VALUES (?,?,?,?,?,?,?,?)",
            [$uid, $name, $type, $category, $proficiency_level, $learning_status, $resources, $notes]);
        log_activity('Added skill: ' . $name);
        redirect('modules/skills/index.php', 'Skill added.', 'success');
    }
}

$filter_type = $_GET['type'] ?? '';
$editSkill = null;
if (isset($_GET['edit'])) {
    $editSkill = fetch_one("SELECT * FROM skills WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$sql = "SELECT * FROM skills WHERE user_id = ?";
$params = [$uid];
if ($filter_type !== '') { $sql .= " AND type = ?"; $params[] = $filter_type; }
$sql .= " ORDER BY type, name ASC";
$skills = fetch_all($sql, $params);

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-brain text-primary me-2"></i>Skills</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editSkill ? 'Edit Skill' : 'Add Skill' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editSkill['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Skill Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($editSkill['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <?php foreach (['Hard Skill','Soft Skill'] as $t): ?><option value="<?= $t ?>" <?= ($editSkill['type'] ?? 'Hard Skill') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="Programming, Communication..." value="<?= sanitize($editSkill['category'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Proficiency</label>
                    <select name="proficiency_level" class="form-select">
                        <?php foreach (['Beginner','Intermediate','Advanced','Expert'] as $p): ?><option value="<?= $p ?>" <?= ($editSkill['proficiency_level'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="learning_status" class="form-select">
                        <?php foreach (['Learning','Proficient','Mastered'] as $s): ?><option value="<?= $s ?>" <?= ($editSkill['learning_status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editSkill ? 'Save' : 'Add' ?></button>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Learning Resources</label>
                    <input type="text" name="resources" class="form-control" placeholder="Courses, books, links..." value="<?= sanitize($editSkill['resources'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editSkill['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editSkill): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold">All Skills</h6>
        <div class="btn-group btn-group-sm">
            <a href="?type=" class="btn btn-outline-secondary <?= $filter_type === '' ? 'active' : '' ?>">All</a>
            <a href="?type=Hard Skill" class="btn btn-outline-secondary <?= $filter_type === 'Hard Skill' ? 'active' : '' ?>">Hard</a>
            <a href="?type=Soft Skill" class="btn btn-outline-secondary <?= $filter_type === 'Soft Skill' ? 'active' : '' ?>">Soft</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Skill</th><th>Type</th><th>Category</th><th>Proficiency</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($skills)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No skills tracked yet.</td></tr>
                    <?php else: foreach ($skills as $s): ?>
                        <tr>
                            <td class="fw-bold"><?= sanitize($s['name']) ?></td>
                            <td><span class="badge bg-<?= $s['type'] === 'Hard Skill' ? 'primary' : 'info' ?>"><?= $s['type'] ?></span></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($s['category']) ?></span></td>
                            <td><?= $s['proficiency_level'] ?></td>
                            <td><span class="badge bg-<?= status_badge_class($s['learning_status']) ?>"><?= $s['learning_status'] ?></span></td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $s['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this skill?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
