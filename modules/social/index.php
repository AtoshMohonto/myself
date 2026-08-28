<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Social Media';
$active_page = 'social';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/social/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE social_accounts SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/social/index.php', 'Account deleted.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $platform = $_POST['platform'] ?? 'Facebook';
    $handle = sanitize($_POST['handle'] ?? '');
    $profile_url = sanitize($_POST['profile_url'] ?? '');
    $followers_count = (int) ($_POST['followers_count'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($handle === '') {
        redirect('modules/social/index.php', 'Handle/username is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE social_accounts SET platform=?, handle=?, profile_url=?, followers_count=?, notes=? WHERE id=? AND user_id=?",
            [$platform, $handle, $profile_url, $followers_count, $notes, $id, $uid]);
        redirect('modules/social/index.php', 'Account updated.', 'success');
    } else {
        db_query("INSERT INTO social_accounts (user_id, platform, handle, profile_url, followers_count, notes) VALUES (?,?,?,?,?,?)",
            [$uid, $platform, $handle, $profile_url, $followers_count, $notes]);
        log_activity('Added social account: ' . $handle);
        redirect('modules/social/index.php', 'Account added.', 'success');
    }
}

$editAcc = null;
if (isset($_GET['edit'])) {
    $editAcc = fetch_one("SELECT * FROM social_accounts WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$accounts = fetch_all("SELECT * FROM social_accounts WHERE user_id = ? ORDER BY platform ASC", [$uid]);
$total_followers = array_sum(array_column($accounts, 'followers_count'));
$categories = category_options_with_current('social_platform', $editAcc['platform'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-hashtag text-primary me-2"></i>Social Media</h1>
    <a href="posts.php" class="btn btn-sm btn-primary"><i class="fas fa-calendar-alt me-1"></i> Content Calendar</a>
</div>

<div class="card border-left-primary shadow-sm mb-4">
    <div class="card-body">
        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Followers Across Accounts</div>
        <div class="h4 fw-bold mb-0"><?= number_format($total_followers) ?></div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editAcc ? 'Edit Account' : 'Add Social Account' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editAcc['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Platform</label>
                    <select name="platform" class="form-select">
                        <?php foreach ($categories as $p): ?><option value="<?= sanitize($p) ?>" <?= ($editAcc['platform'] ?? '') === $p ? 'selected' : '' ?>><?= sanitize($p) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Handle/Username *</label>
                    <input type="text" name="handle" class="form-control" value="<?= sanitize($editAcc['handle'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Profile URL</label>
                    <input type="url" name="profile_url" class="form-control" value="<?= sanitize($editAcc['profile_url'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Followers</label>
                    <input type="number" name="followers_count" class="form-control" value="<?= $editAcc['followers_count'] ?? 0 ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editAcc ? 'Save' : 'Add' ?></button>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editAcc['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editAcc): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Platform</th><th>Handle</th><th>Followers</th><th>Profile</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No social accounts yet.</td></tr>
                    <?php else: foreach ($accounts as $a): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark"><?= sanitize($a['platform']) ?></span></td>
                            <td class="fw-bold">@<?= sanitize($a['handle']) ?></td>
                            <td><?= number_format($a['followers_count']) ?></td>
                            <td><?php if ($a['profile_url']): ?><a href="<?= sanitize($a['profile_url']) ?>" target="_blank" rel="noopener">Visit <i class="fas fa-arrow-up-right-from-square fa-xs"></i></a><?php else: ?>-<?php endif; ?></td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $a['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this account?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
