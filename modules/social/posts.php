<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Content Calendar';
$active_page = 'social';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/social/posts.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE social_posts SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/social/posts.php', 'Post deleted.', 'success');
    }

    if ($action === 'set_status') {
        db_query("UPDATE social_posts SET status = ? WHERE id = ? AND user_id = ?", [$_POST['status'] ?? 'Planned', (int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/social/posts.php', 'Updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $social_account_id = (int) ($_POST['social_account_id'] ?? 0) ?: null;
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $post_type = $_POST['post_type'] ?? 'Post';
    $scheduled_date = $_POST['scheduled_date'] ?: null;
    $notes = sanitize($_POST['notes'] ?? '');

    // Only allow linking to an account this user actually owns
    if ($social_account_id !== null && !fetch_one("SELECT id FROM social_accounts WHERE id = ? AND user_id = ?", [$social_account_id, $uid])) {
        $social_account_id = null;
    }

    if ($title === '') {
        redirect('modules/social/posts.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE social_posts SET social_account_id=?, title=?, content=?, post_type=?, scheduled_date=?, notes=? WHERE id=? AND user_id=?",
            [$social_account_id, $title, $content, $post_type, $scheduled_date, $notes, $id, $uid]);
        redirect('modules/social/posts.php', 'Post updated.', 'success');
    } else {
        db_query("INSERT INTO social_posts (user_id, social_account_id, title, content, post_type, scheduled_date, notes) VALUES (?,?,?,?,?,?,?)",
            [$uid, $social_account_id, $title, $content, $post_type, $scheduled_date, $notes]);
        log_activity('Planned post: ' . $title);
        redirect('modules/social/posts.php', 'Post added.', 'success');
    }
}

$accounts = fetch_all("SELECT * FROM social_accounts WHERE user_id = ? ORDER BY platform ASC", [$uid]);
$editPost = null;
if (isset($_GET['edit'])) {
    $editPost = fetch_one("SELECT * FROM social_posts WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$posts = fetch_all("SELECT p.*, a.platform, a.handle FROM social_posts p LEFT JOIN social_accounts a ON p.social_account_id = a.id WHERE p.user_id = ? ORDER BY (p.scheduled_date IS NULL), p.scheduled_date ASC", [$uid]);
$categories = category_options_with_current('social_post_type', $editPost['post_type'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-alt text-primary me-2"></i>Content Calendar</h1>
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Accounts</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editPost ? 'Edit Post' : 'Plan a Post' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editPost['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Account</label>
                    <select name="social_account_id" class="form-select">
                        <option value="0">— Unassigned —</option>
                        <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>" <?= (int) ($editPost['social_account_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= sanitize($a['platform']) ?> @<?= sanitize($a['handle']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editPost['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <select name="post_type" class="form-select">
                        <?php foreach ($categories as $t): ?><option value="<?= sanitize($t) ?>" <?= ($editPost['post_type'] ?? '') === $t ? 'selected' : '' ?>><?= sanitize($t) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Scheduled Date</label>
                    <input type="date" name="scheduled_date" class="form-control" value="<?= $editPost['scheduled_date'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editPost ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Content / Caption</label>
                    <textarea name="content" class="form-control" rows="2"><?= sanitize($editPost['content'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editPost['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editPost): ?><a href="posts.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Title</th><th>Account</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No posts planned yet.</td></tr>
                    <?php else: foreach ($posts as $p): ?>
                        <tr>
                            <td><?= $p['scheduled_date'] ? format_date($p['scheduled_date']) : '-' ?></td>
                            <td class="fw-bold"><?= sanitize($p['title']) ?></td>
                            <td><?= $p['platform'] ? sanitize($p['platform']) . ' @' . sanitize($p['handle']) : '<span class="text-muted">Unassigned</span>' ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($p['post_type']) ?></span></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        <?php foreach (['Planned','Scheduled','Posted','Cancelled'] as $s): ?><option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this post?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
