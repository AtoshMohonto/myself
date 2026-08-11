<?php
require_once __DIR__ . '/../../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = null;
$success = null;
$formData = ['full_name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $formData['full_name'] = trim($_POST['full_name'] ?? '');
        $formData['username'] = trim($_POST['username'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($formData['full_name'] === '' || $formData['username'] === '' || $password === '') {
            $error = 'Full name, username, and password are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.]+$/', $formData['username'])) {
            $error = 'Username can only contain letters, numbers, underscores, and dots.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif ($formData['email'] !== '' && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            $exists = fetch_one("SELECT id FROM users WHERE username = ?", [$formData['username']]);
            if ($exists) {
                $error = 'That username is already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db_query("INSERT INTO users (full_name, username, email, password, role, is_active) VALUES (?, ?, ?, ?, 'User', 0)",
                    [$formData['full_name'], $formData['username'], $formData['email'] ?: null, $hash]);
                $newUserId = get_db_connection()->insert_id;
                db_query("INSERT INTO finance_accounts (user_id, name, type, opening_balance, balance) VALUES (?, 'Cash Wallet', 'Cash', 0, 0)", [$newUserId]);
                $success = 'Account created! An admin needs to approve it before you can sign in.';
                $formData = ['full_name' => '', 'username' => '', 'email' => ''];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — <?= sanitize(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg,#0f172a,#1e293b); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; padding:1.5rem 0; }
        .card-box { background:#fff; border-radius:1rem; padding:2.5rem; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,0.35); margin:1rem; }
        @media (max-width:480px) { .card-box { padding:1.75rem 1.25rem; } }
    </style>
</head>
<body>
<div class="card-box">
    <div class="text-center mb-4">
        <div style="width:56px;height:56px;border-radius:50%;background:#6366f1;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;">
            <i class="fas fa-user-plus"></i>
        </div>
        <h4 class="mt-3 fw-bold">Create Your Account</h4>
        <p class="text-muted small">Start tracking your whole life, your way.</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= sanitize($success) ?></div>
        <a href="<?= BASE_URL ?>modules/auth/login.php" class="btn btn-primary w-100">Back to Login</a>
    <?php else: ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= sanitize($formData['full_name']) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= sanitize($formData['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email (optional)</label>
                <input type="email" name="email" class="form-control" value="<?= sanitize($formData['email']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>
        <p class="text-center text-muted small mt-3 mb-1">Already have an account? <a href="<?= BASE_URL ?>modules/auth/login.php">Sign in</a></p>
        <p class="text-center text-muted small mb-0"><a href="<?= BASE_URL ?>site.php">&larr; Back to homepage</a></p>
    <?php endif; ?>
</div>
</body>
</html>
