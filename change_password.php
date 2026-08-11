<?php
require_once __DIR__ . '/includes/functions.php';
check_login();
$page_title = 'Change Password';
$active_page = 'profile';

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('change_password.php', 'Invalid request.', 'danger');
    }
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        redirect('change_password.php', 'Current password is incorrect.', 'danger');
    } elseif (strlen($new) < 6) {
        redirect('change_password.php', 'New password must be at least 6 characters.', 'danger');
    } elseif ($new !== $confirm) {
        redirect('change_password.php', 'New passwords do not match.', 'danger');
    } else {
        db_query("UPDATE users SET password = ? WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        log_activity('Changed password');
        redirect('profile.php', 'Password changed successfully.', 'success');
    }
}

include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>
<div class="page-header">
    <h1><i class="fas fa-lock text-primary me-2"></i>Change Password</h1>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/templates/footer.php'; ?>
