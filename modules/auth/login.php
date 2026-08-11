<?php
require_once __DIR__ . '/../../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            $user = fetch_one("SELECT * FROM users WHERE username = ?", [$username]);
            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid username or password.';
            } elseif ((int) $user['is_active'] !== 1) {
                $error = 'Your account is pending admin approval. Please check back later.';
            } else {
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                log_activity('Logged in');
                redirect('index.php');
            }
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — <?= sanitize(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg,#0f172a,#1e293b); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; }
        .login-card { background:#fff; border-radius:1rem; padding:2.5rem; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.35); }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <div style="width:56px;height:56px;border-radius:50%;background:#6366f1;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;">
            <i class="fas fa-user-astronaut"></i>
        </div>
        <h4 class="mt-3 fw-bold">MySelf</h4>
        <p class="text-muted small">Your whole life, in one place.</p>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= sanitize($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign In</button>
    </form>
    <p class="text-center text-muted small mt-3 mb-1">New here? <a href="<?= BASE_URL ?>modules/auth/register.php">Create an account</a></p>
    <p class="text-center text-muted small mb-0"><a href="<?= BASE_URL ?>site.php">&larr; Back to homepage</a></p>
</div>
</body>
</html>
