<?php
require_once __DIR__ . '/includes/functions.php';
check_login();
$page_title = 'Profile';
$active_page = 'profile';

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('profile.php', 'Invalid request.', 'danger');
    }
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $timezone = sanitize($_POST['timezone'] ?? 'Asia/Dhaka');
    $currency_symbol = sanitize($_POST['currency_symbol'] ?? '৳');

    if ($full_name === '') {
        redirect('profile.php', 'Full name is required.', 'danger');
    }

    db_query("UPDATE users SET full_name = ?, email = ?, timezone = ?, currency_symbol = ? WHERE id = ?", [$full_name, $email, $timezone, $currency_symbol, $user['id']]);
    log_activity('Updated profile');
    redirect('profile.php', 'Profile updated.', 'success');
}

include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-gear text-primary me-2"></i>Profile</h1>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= sanitize($user['full_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= sanitize($user['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Timezone</label>
                        <input type="text" name="timezone" class="form-control" value="<?= sanitize($user['timezone'] ?? 'Asia/Dhaka') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" value="<?= sanitize($user['currency_symbol'] ?? '৳') ?>" maxlength="5">
                    </div>
                    <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Account</h6>
                <p class="mb-1"><strong>Username:</strong> <?= sanitize($user['username']) ?></p>
                <p class="mb-1"><strong>Member since:</strong> <?= format_date($user['created_at']) ?></p>
                <a href="change_password.php" class="btn btn-outline-secondary btn-sm mt-2"><i class="fas fa-lock me-1"></i> Change Password</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
