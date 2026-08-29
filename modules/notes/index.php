<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Notes';
$active_page = 'notes';
$uid = my_id();

$colors = ['default' => 'Default', 'yellow' => 'Yellow', 'green' => 'Green', 'blue' => 'Blue', 'pink' => 'Pink', 'purple' => 'Purple', 'gray' => 'Gray'];
$categories = get_categories('notes');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/notes/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE notes SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/notes/index.php', 'Note deleted.', 'success');
    }

    if ($action === 'toggle_pin') {
        $id = (int) ($_POST['id'] ?? 0);
        $note = fetch_one("SELECT * FROM notes WHERE id = ? AND user_id = ?", [$id, $uid]);
        if ($note) {
            db_query("UPDATE notes SET is_pinned = ? WHERE id = ? AND user_id = ?", [$note['is_pinned'] ? 0 : 1, $id, $uid]);
        }
        redirect('modules/notes/index.php', '', '');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $category = in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'General';
    $color = array_key_exists($_POST['color'] ?? '', $colors) ? $_POST['color'] : 'default';

    if ($content === '') {
        redirect('modules/notes/index.php', 'Note content is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE notes SET title=?, content=?, category=?, color=? WHERE id=? AND user_id=?",
            [$title, $content, $category, $color, $id, $uid]);
        redirect('modules/notes/index.php', 'Note updated.', 'success');
    } else {
        db_query("INSERT INTO notes (user_id, title, content, category, color) VALUES (?,?,?,?,?)",
            [$uid, $title, $content, $category, $color]);
        log_activity('Added note: ' . ($title !== '' ? $title : substr($content, 0, 40)));
        redirect('modules/notes/index.php', 'Note added.', 'success');
    }
}

$filter_category = $_GET['category'] ?? '';
$q = trim($_GET['q'] ?? '');
$editNote = null;
if (isset($_GET['edit'])) {
    $editNote = fetch_one("SELECT * FROM notes WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}

$sql = "SELECT * FROM notes WHERE user_id = ?";
$params = [$uid];
if ($filter_category !== '') { $sql .= " AND category = ?"; $params[] = $filter_category; }
if ($q !== '') { $sql .= " AND (title LIKE ? OR content LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= " ORDER BY is_pinned DESC, updated_at DESC";
$notes = fetch_all($sql, $params);
$formCategories = category_options_with_current('notes', $editNote['category'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-note-sticky text-primary me-2"></i>Notes</h1>
    <span class="text-muted"><?= count($notes) ?> note<?= count($notes) === 1 ? '' : 's' ?></span>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editNote ? 'Edit Note' : 'Add Note' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editNote['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Optional title" value="<?= sanitize($editNote['title'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($formCategories as $c): ?><option value="<?= sanitize($c) ?>" <?= ($editNote['category'] ?? 'General') === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> <?= $editNote ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Note *</label>
                    <textarea name="content" class="form-control" rows="3" placeholder="Write your note..." required><?= sanitize($editNote['content'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold d-block">Color</label>
                    <?php $selColor = $editNote['color'] ?? 'default'; foreach ($colors as $key => $label): ?>
                        <label class="note-swatch note-color-<?= $key ?>" title="<?= $label ?>">
                            <input type="radio" name="color" value="<?= $key ?>" <?= $selColor === $key ? 'checked' : '' ?>>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($editNote): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-3">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group btn-group-sm flex-wrap">
        <a href="?category=<?= $q ? '&q=' . urlencode($q) : '' ?>" class="btn btn-outline-secondary <?= $filter_category === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $c): ?>
            <a href="?category=<?= urlencode($c) ?><?= $q ? '&q=' . urlencode($q) : '' ?>" class="btn btn-outline-secondary <?= $filter_category === $c ? 'active' : '' ?>"><?= sanitize($c) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="GET" class="d-flex gap-2">
        <?php if ($filter_category !== ''): ?><input type="hidden" name="category" value="<?= sanitize($filter_category) ?>"><?php endif; ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search notes..." value="<?= sanitize($q) ?>" style="min-width:200px;">
        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
    </form>
</div>

<?php if (empty($notes)): ?>
    <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">
        <i class="fas fa-note-sticky fa-2x mb-2 d-block"></i>
        <?= $q !== '' || $filter_category !== '' ? 'No notes match your filters.' : 'No notes yet — write your first one above.' ?>
    </div></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($notes as $n): ?>
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="note-card note-color-<?= sanitize($n['color']) ?> h-100">
                    <div class="p-3 d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0 text-truncate" style="max-width:80%;"><?= $n['title'] !== '' && $n['title'] !== null ? sanitize($n['title']) : '<span class="text-muted fst-italic">Untitled</span>' ?></h6>
                            <form method="POST" class="m-0">
                                <?= csrf_field() ?><input type="hidden" name="action" value="toggle_pin"><input type="hidden" name="id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="<?= $n['is_pinned'] ? 'Unpin' : 'Pin' ?>">
                                    <i class="fas fa-thumbtack <?= $n['is_pinned'] ? 'text-primary' : 'text-muted opacity-50' ?>"></i>
                                </button>
                            </form>
                        </div>
                        <p class="note-content small flex-grow-1 mb-3"><?= sanitize($n['content']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-white text-dark border"><?= sanitize($n['category']) ?></span>
                            <div class="text-nowrap">
                                <a href="?edit=<?= $n['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this note?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
