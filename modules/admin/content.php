<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$page_title = 'Site Content';
$active_page = 'admin_content';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/admin/content.php', 'Invalid request.', 'danger');
    }
    save_setting('hero_title', sanitize($_POST['hero_title'] ?? ''));
    save_setting('hero_subtitle', sanitize($_POST['hero_subtitle'] ?? ''));
    save_setting('notice', sanitize($_POST['notice'] ?? ''));
    save_setting('notice_active', isset($_POST['notice_active']) ? '1' : '0');
    log_activity('Updated public site content');
    redirect('modules/admin/content.php', 'Site content updated.', 'success');
}

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-bullhorn text-primary me-2"></i>Public Site Content</h1>
    <a href="<?= BASE_URL ?>site.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-arrow-up-right-from-square me-1"></i> View Homepage</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Hero Title</label>
                <input type="text" name="hero_title" class="form-control" value="<?= sanitize(get_setting('hero_title', 'Track your whole life in one place')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Hero Subtitle</label>
                <textarea name="hero_subtitle" class="form-control" rows="2"><?= sanitize(get_setting('hero_subtitle', 'Schedule, tasks, goals, bucket list, dreams, skills, work, finances, health, medicines, and social media — one dashboard for everything that makes up your life.')) ?></textarea>
            </div>
            <hr>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="noticeActive" name="notice_active" value="1" <?= get_setting('notice_active', '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="noticeActive">Show notice banner on homepage</label>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Notice Text</label>
                <textarea name="notice" class="form-control" rows="3" placeholder="e.g. New signups are approved manually within 24 hours."><?= sanitize(get_setting('notice', '')) ?></textarea>
            </div>
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
