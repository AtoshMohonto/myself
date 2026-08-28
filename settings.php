<?php
require_once __DIR__ . '/includes/functions.php';
check_login();
$page_title = 'Settings';
$active_page = 'settings';
$uid = my_id();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('settings.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_locker_lock') {
        $enabled = isset($_POST['lock_digital_locker']) ? 1 : 0;
        db_query("UPDATE users SET lock_digital_locker = ? WHERE id = ?", [$enabled, $uid]);
        if (!$enabled) {
            unset($_SESSION['locker_unlocked']);
        }
        log_activity($enabled ? 'Enabled Digital Locker re-authentication' : 'Disabled Digital Locker re-authentication');
        redirect('settings.php', 'Digital Locker security updated.', 'success');
    }

    if ($action === 'clear_activity_log') {
        db_query("UPDATE activity_logs SET isDelete = 1 WHERE user_id = ?", [$uid]);
        log_activity('Cleared activity log');
        redirect('settings.php', 'Activity log cleared.', 'success');
    }

    $categoryMap = category_module_map();
    $postModule = $_POST['module'] ?? '';
    $backTo = 'settings.php' . (isset($categoryMap[$postModule]) ? '?module=' . urlencode($postModule) . '#categories' : '#categories');

    if ($action === 'add_category') {
        $name = sanitize($_POST['name'] ?? '');
        if (isset($categoryMap[$postModule]) && $name !== '') {
            $existing = get_categories($postModule);
            if (!in_array($name, $existing, true)) {
                db_query("INSERT INTO categories (user_id, module, name, sort_order) VALUES (?,?,?,?)", [$uid, $postModule, $name, count($existing)]);
                log_activity('Added category "' . $name . '" to ' . $categoryMap[$postModule]['label']);
                redirect($backTo, 'Category added.', 'success');
            }
            redirect($backTo, 'That category already exists.', 'danger');
        }
        redirect($backTo, 'Invalid category.', 'danger');
    }

    if ($action === 'rename_category') {
        $catId = (int) ($_POST['id'] ?? 0);
        $newName = sanitize($_POST['name'] ?? '');
        if (isset($categoryMap[$postModule]) && $newName !== '' && $catId > 0) {
            $cat = fetch_one("SELECT * FROM categories WHERE id = ? AND user_id = ? AND module = ?", [$catId, $uid, $postModule]);
            if ($cat && $cat['name'] !== $newName) {
                db_query("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?", [$newName, $catId, $uid]);
                foreach ($categoryMap[$postModule]['targets'] as [$table, $column]) {
                    db_query("UPDATE $table SET $column = ? WHERE $column = ? AND user_id = ?", [$newName, $cat['name'], $uid]);
                }
                log_activity('Renamed category "' . $cat['name'] . '" to "' . $newName . '" in ' . $categoryMap[$postModule]['label']);
                redirect($backTo, 'Category renamed everywhere it was used.', 'success');
            }
        }
        redirect($backTo, 'Rename failed.', 'danger');
    }

    if ($action === 'remove_category') {
        $catId = (int) ($_POST['id'] ?? 0);
        if (isset($categoryMap[$postModule]) && $catId > 0) {
            db_query("UPDATE categories SET isDelete = 1 WHERE id = ? AND user_id = ? AND module = ?", [$catId, $uid, $postModule]);
            log_activity('Removed a category from ' . $categoryMap[$postModule]['label']);
        }
        redirect($backTo, 'Category removed.', 'success');
    }
}

$categoryMap = category_module_map();
$activeModule = $_GET['module'] ?? 'schedule';
if (!isset($categoryMap[$activeModule])) $activeModule = 'schedule';
get_categories($activeModule); // ensure defaults are seeded before listing
$activeCategories = fetch_all("SELECT * FROM categories WHERE user_id = ? AND module = ? ORDER BY sort_order ASC, name ASC", [$uid, $activeModule]);

$exportTypes = [
    'schedule' => 'Schedule Events', 'tasks' => 'Tasks', 'goals' => 'Goals', 'bucket_list' => 'Bucket List',
    'dreams' => 'Dreams', 'skills' => 'Skills', 'work' => 'Work Schedules',
    'finance_accounts' => 'Finance Accounts', 'finance_transactions' => 'Finance Transactions', 'finance_reminders' => 'Payment Reminders',
    'health' => 'Health Records', 'medicines' => 'Medicines',
    'social_accounts' => 'Social Accounts', 'social_posts' => 'Content Calendar',
    'digital_locker' => 'Digital Locker (no passwords)', 'lend_borrow' => 'Lend & Borrow',
];

$activity = fetch_all("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50", [$uid]);

include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-gear text-primary me-2"></i>Settings</h1>
</div>

<div class="row">
    <div class="col-12 mb-4" id="categories">
        <div class="card shadow-sm">
            <div class="card-header py-3"><h6 class="m-0 fw-bold"><i class="fas fa-tags me-1 text-primary"></i> Manage Categories</h6></div>
            <div class="card-body">
                <p class="text-muted small">Add, rename, or remove the category/type options offered throughout the app. Renaming updates every existing record that used it; removing only stops it being offered for new entries.</p>
                <div class="btn-group btn-group-sm flex-wrap mb-3">
                    <?php foreach ($categoryMap as $key => $cfg): ?>
                        <a href="settings.php?module=<?= urlencode($key) ?>#categories" class="btn btn-outline-secondary <?= $activeModule === $key ? 'active' : '' ?>"><?= sanitize($cfg['label']) ?></a>
                    <?php endforeach; ?>
                </div>

                <form method="POST" class="d-flex gap-2 mb-3" style="max-width:420px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_category">
                    <input type="hidden" name="module" value="<?= sanitize($activeModule) ?>">
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="New category name" maxlength="100" required>
                    <button class="btn btn-sm btn-primary text-nowrap"><i class="fas fa-plus me-1"></i>Add</button>
                </form>

                <div class="list-group">
                    <?php if (empty($activeCategories)): ?>
                        <div class="text-muted text-center py-3">No categories yet.</div>
                    <?php else: foreach ($activeCategories as $c): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <form method="POST" class="d-flex align-items-center gap-2 flex-grow-1" style="min-width:220px;max-width:420px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="rename_category">
                                <input type="hidden" name="module" value="<?= sanitize($activeModule) ?>">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <input type="text" name="name" class="form-control form-control-sm" value="<?= sanitize($c['name']) ?>" maxlength="100" required>
                                <button class="btn btn-xs btn-outline-primary text-nowrap"><i class="fas fa-pen me-1"></i>Rename</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Remove this category? Existing records keep it, but it won\'t be offered for new ones.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove_category">
                                <input type="hidden" name="module" value="<?= sanitize($activeModule) ?>">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button class="btn btn-xs btn-outline-danger" title="Remove"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3"><h6 class="m-0 fw-bold"><i class="fas fa-shield-halved me-1 text-primary"></i> Digital Locker Security</h6></div>
            <div class="card-body">
                <p class="text-muted small">When enabled, you'll need to re-enter your account password each session before the Digital Locker will open.</p>
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_locker_lock">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="lockDigitalLocker" name="lock_digital_locker" value="1" <?= !empty($user['lock_digital_locker']) ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label fw-bold" for="lockDigitalLocker">Require password to open Digital Locker</label>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3"><h6 class="m-0 fw-bold"><i class="fas fa-file-csv me-1 text-primary"></i> Export Your Data</h6></div>
            <div class="card-body">
                <p class="text-muted small">Download any of your data as a CSV file. Digital Locker exports never include passwords.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($exportTypes as $type => $label): ?>
                        <a href="modules/export/index.php?type=<?= urlencode($type) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i><?= sanitize($label) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold"><i class="fas fa-clock-rotate-left me-1 text-primary"></i> Recent Activity</h6>
                <?php if (!empty($activity)): ?>
                <form method="POST" onsubmit="return confirm('Clear your entire activity log? This cannot be undone.');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="clear_activity_log">
                    <button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash me-1"></i>Clear Log</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead><tr><th>When</th><th>Action</th><th>Details</th></tr></thead>
                        <tbody>
                            <?php if (empty($activity)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No activity recorded yet.</td></tr>
                            <?php else: foreach ($activity as $a): ?>
                                <tr>
                                    <td class="text-nowrap text-sm"><?= format_datetime($a['created_at']) ?></td>
                                    <td><?= sanitize($a['action']) ?></td>
                                    <td class="text-muted text-sm"><?= sanitize($a['details'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
