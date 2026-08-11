<?php
require_once __DIR__ . '/includes/functions.php';
$is_logged_in = isset($_SESSION['user_id']);

$hero_title = get_setting('hero_title', 'Track your whole life in one place');
$hero_subtitle = get_setting('hero_subtitle', 'Schedule, tasks, goals, bucket list, dreams, skills, work, finances, health, medicines, and social media — one dashboard for everything that makes up your life.');
$notice = get_setting('notice', '');
$notice_active = get_setting('notice_active', '0') === '1';

$features = [
    ['calendar-days', 'Schedule & Activities', 'Daily and recurring events, never miss what matters.'],
    ['list-check', 'Tasks', 'To-dos with priority and due dates.'],
    ['bullseye', 'Goals', 'Track progress toward what you\'re working for.'],
    ['list-ol', 'Bucket List', 'Everything you want to do before you\'re done.'],
    ['cloud-moon', 'Dreams', 'Keep your aspirations somewhere real.'],
    ['brain', 'Skills', 'Hard skills and soft skills, tracked as you grow.'],
    ['briefcase', 'Work Schedules', 'Job, remote work, tuition, consulting — all your income streams.'],
    ['wallet', 'Finance', 'Accounts, income, and expenses in one balance.'],
    ['heart-pulse', 'Health Records', 'Checkups, conditions, vaccinations, labs.'],
    ['pills', 'Medicines', 'Stock, dosage, schedule, expiry — never run out.'],
    ['hashtag', 'Social Media', 'Accounts and a content calendar for what you post.'],
];

$page_title = 'MySelf — Your Whole Life, Organized';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body>
<div class="site-page">
<nav class="navbar navbar-expand-lg bg-white shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>site.php">
            <span style="width:36px;height:36px;border-radius:0.6rem;background:#6366f1;display:inline-flex;align-items:center;justify-content:center;color:#fff;"><i class="fas fa-user-astronaut"></i></span>
            MySelf
        </a>
        <div class="ms-auto d-flex gap-2">
            <?php if ($is_logged_in): ?>
                <a href="<?= BASE_URL ?>index.php" class="btn btn-primary btn-sm"><i class="fas fa-gauge-high me-1"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>modules/auth/login.php" class="btn btn-outline-primary btn-sm">Sign In</a>
                <a href="<?= BASE_URL ?>modules/auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="hero-section py-5">
    <div class="container py-4 text-center">
        <p class="text-uppercase fw-bold small mb-3" style="letter-spacing:0.15rem; color:#a5b4fc;">Personal Life OS</p>
        <h1 class="fw-bold text-white mb-3" style="font-size:clamp(1.8rem, 5vw, 3rem);"><?= sanitize($hero_title) ?></h1>
        <p class="mx-auto mb-4" style="max-width:640px; color:rgba(255,255,255,0.85); font-size:1.05rem;"><?= sanitize($hero_subtitle) ?></p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php if ($is_logged_in): ?>
                <a href="<?= BASE_URL ?>index.php" class="btn btn-light btn-lg fw-bold"><i class="fas fa-arrow-right me-2"></i>Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>modules/auth/register.php" class="btn btn-light btn-lg fw-bold"><i class="fas fa-user-plus me-2"></i>Create Free Account</a>
                <a href="<?= BASE_URL ?>modules/auth/login.php" class="btn btn-outline-light btn-lg">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($notice_active && trim($notice) !== ''): ?>
<div class="container mt-4">
    <div class="alert alert-warning d-flex align-items-start gap-2">
        <i class="fas fa-bullhorn mt-1"></i>
        <div><?= nl2br(sanitize($notice)) ?></div>
    </div>
</div>
<?php endif; ?>

<section class="container py-5">
    <div class="text-center mb-5">
        <p class="text-uppercase fw-bold small text-primary">Everything, one place</p>
        <h2 class="fw-bold">All the parts of your life, organized</h2>
    </div>
    <div class="row g-4">
        <?php foreach ($features as $f): [$icon, $title, $desc] = $f; ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm feature-card">
                <div class="card-body">
                    <div class="feature-icon mb-3"><i class="fas fa-<?= $icon ?>"></i></div>
                    <h5 class="fw-bold"><?= sanitize($title) ?></h5>
                    <p class="text-muted small mb-0"><?= sanitize($desc) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="py-5" style="background:#eef2ff;">
    <div class="container text-center">
        <h3 class="fw-bold mb-3">Your data, your account</h3>
        <p class="text-muted mx-auto mb-4" style="max-width:560px;">Every account is private — nobody sees your schedule, finances, or health records but you. New accounts are reviewed before activation.</p>
        <?php if (!$is_logged_in): ?>
            <a href="<?= BASE_URL ?>modules/auth/register.php" class="btn btn-primary btn-lg fw-bold"><i class="fas fa-user-plus me-2"></i>Create Your Account</a>
        <?php endif; ?>
    </div>
</section>

<footer class="py-4" style="background:#0f172a;color:rgba(255,255,255,0.6);">
    <div class="container text-center small">
        <div class="fw-semibold text-white mb-1">MySelf</div>
        Your whole life, organized. &copy; <?= date('Y') ?>
    </div>
</footer>
</div>

<style>
body { background: #fff; }
.hero-section { background: linear-gradient(135deg, #0f172a, #312e81); }
.feature-icon { width:52px; height:52px; border-radius:0.8rem; background:rgba(99,102,241,0.12); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
.feature-card { transition: transform 0.15s ease; }
.feature-card:hover { transform: translateY(-3px); }
</style>
</body>
</html>
