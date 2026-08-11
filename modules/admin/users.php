<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Manage Users';
$active_page = 'admin_users';
$myUid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/admin/users.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'approve') {
        db_query("UPDATE users SET is_active = 1 WHERE id = ?", [$id]);
        log_activity('Approved user #' . $id);
        redirect('modules/admin/users.php', 'User approved.', 'success');
    }

    if ($action === 'deactivate') {
        if ($id === $myUid) {
            redirect('modules/admin/users.php', 'You cannot deactivate your own account.', 'danger');
        }
        db_query("UPDATE users SET is_active = 0 WHERE id = ?", [$id]);
        log_activity('Deactivated user #' . $id);
        redirect('modules/admin/users.php', 'User deactivated.', 'success');
    }

    if ($action === 'make_admin') {
        db_query("UPDATE users SET role = 'Admin' WHERE id = ?", [$id]);
        log_activity('Promoted user #' . $id . ' to Admin');
        redirect('modules/admin/users.php', 'User promoted to Admin.', 'success');
    }

    if ($action === 'remove_admin') {
        if ($id === $myUid) {
            redirect('modules/admin/users.php', 'You cannot remove your own admin role.', 'danger');
        }
        db_query("UPDATE users SET role = 'User' WHERE id = ?", [$id]);
        log_activity('Removed Admin role from user #' . $id);
        redirect('modules/admin/users.php', 'Admin role removed.', 'success');
    }

    if ($action === 'delete') {
        if ($id === $myUid) {
            redirect('modules/admin/users.php', 'You cannot delete your own account.', 'danger');
        }
        db_query("UPDATE users SET isDelete = 1, is_active = 0 WHERE id = ?", [$id]);
        log_activity('Deleted user #' . $id);
        redirect('modules/admin/users.php', 'User deleted.', 'success');
    }
}

$pending = fetch_all("SELECT * FROM users WHERE is_active = 0 ORDER BY created_at ASC");
$active = fetch_all("SELECT * FROM users WHERE is_active = 1 ORDER BY role DESC, full_name ASC");

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users-gear text-primary me-2"></i>Manage Users</h1>
</div>

<?php if (!empty($pending)): ?>
<div class="card shadow-sm mb-4 border-left-warning">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><i class="fas fa-user-clock me-1 text-warning"></i> Pending Approval (<?= count($pending) ?>)</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($pending as $u): ?>
                    <tr>
                        <td class="fw-bold"><?= sanitize($u['full_name']) ?></td>
                        <td><?= sanitize($u['username']) ?></td>
                        <td class="text-sm"><?= sanitize($u['email'] ?? '-') ?></td>
                        <td class="text-sm"><?= format_date($u['created_at']) ?></td>
                        <td class="text-nowrap">
                            <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-xs btn-success"><i class="fas fa-check me-1"></i>Approve</button></form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this pending registration?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header py-3"><h6 class="m-0 fw-bold">Active Users (<?= count($active) ?>)</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($active as $u): ?>
                    <tr>
                        <td class="fw-bold"><?= sanitize($u['full_name']) ?><?= $u['id'] === $myUid ? ' <span class="badge bg-primary">You</span>' : '' ?></td>
                        <td><?= sanitize($u['username']) ?></td>
                        <td><span class="badge bg-<?= $u['role'] === 'Admin' ? 'primary' : 'secondary' ?>"><?= $u['role'] ?></span></td>
                        <td class="text-sm"><?= format_date($u['created_at']) ?></td>
                        <td class="text-nowrap">
                            <?php if ($u['role'] === 'Admin'): ?>
                                <?php if ($u['id'] !== $myUid): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Remove admin role from this user?');"><?= csrf_field() ?><input type="hidden" name="action" value="remove_admin"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-xs btn-outline-secondary">Remove Admin</button></form>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="make_admin"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-xs btn-outline-primary">Make Admin</button></form>
                            <?php endif; ?>
                            <?php if ($u['id'] !== $myUid): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Deactivate this account? They will need to be re-approved to log in again.');"><?= csrf_field() ?><input type="hidden" name="action" value="deactivate"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-xs btn-outline-warning">Deactivate</button></form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user? Their data stays but they lose access.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
