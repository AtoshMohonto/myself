<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Digital Locker';
$active_page = 'digital_locker';
$uid = my_id();
$currentUser = current_user();

if (!empty($currentUser['lock_digital_locker']) && empty($_SESSION['locker_unlocked'])) {
    $unlockError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'unlock_locker') {
        if (!validate_csrf($_POST['csrf_token'] ?? '')) {
            $unlockError = 'Invalid request. Please try again.';
        } elseif (password_verify($_POST['password'] ?? '', $currentUser['password'])) {
            $_SESSION['locker_unlocked'] = true;
            redirect('modules/digital_locker/index.php');
        } else {
            $unlockError = 'Incorrect password.';
        }
    }
    $page_title = 'Digital Locker';
    $active_page = 'digital_locker';
    include __DIR__ . '/../../templates/header.php';
    include __DIR__ . '/../../templates/sidebar.php';
    ?>
    <div class="page-header">
        <h1><i class="fas fa-shield-halved text-primary me-2"></i>Digital Locker</h1>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-lock fa-2x text-primary mb-3"></i>
                    <h5 class="fw-bold mb-2">Locked</h5>
                    <p class="text-muted small mb-4">Re-enter your account password to open the Digital Locker.</p>
                    <?php if ($unlockError): ?><div class="alert alert-danger py-2 small"><?= sanitize($unlockError) ?></div><?php endif; ?>
                    <form method="POST" class="text-start">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="unlock_locker">
                        <input type="password" name="password" class="form-control mb-3" placeholder="Your password" required autofocus>
                        <button class="btn btn-primary w-100"><i class="fas fa-unlock me-1"></i> Unlock</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../templates/footer.php';
    exit;
}

$categories = get_categories('digital_locker_category');
$category_icons = [
    'Social Media' => 'hashtag', 'Email' => 'envelope', 'WiFi' => 'wifi',
    'App' => 'mobile-screen', 'Bank' => 'building-columns', 'Other' => 'key',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/digital_locker/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE digital_locker SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/digital_locker/index.php', 'Entry deleted.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $category = in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Other';
    $title = sanitize($_POST['title'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $url = sanitize($_POST['url'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($title === '' || $password === '') {
        redirect('modules/digital_locker/index.php', 'Title and password are required.', 'danger');
    }

    $encryptedPassword = encrypt_data($password);

    if ($id > 0) {
        db_query("UPDATE digital_locker SET category=?, title=?, username=?, password=?, url=?, notes=? WHERE id=? AND user_id=?",
            [$category, $title, $username, $encryptedPassword, $url, $notes, $id, $uid]);
        redirect('modules/digital_locker/index.php', 'Entry updated.', 'success');
    } else {
        db_query("INSERT INTO digital_locker (user_id, category, title, username, password, url, notes) VALUES (?,?,?,?,?,?,?)",
            [$uid, $category, $title, $username, $encryptedPassword, $url, $notes]);
        log_activity('Added digital locker entry: ' . $title);
        redirect('modules/digital_locker/index.php', 'Entry added.', 'success');
    }
}

$filter_category = $_GET['category'] ?? '';
$editEntry = null;
$editPasswordPlain = '';
if (isset($_GET['edit'])) {
    $editEntry = fetch_one("SELECT * FROM digital_locker WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
    if ($editEntry) {
        $editPasswordPlain = decrypt_data($editEntry['password']);
    }
}

$sql = "SELECT * FROM digital_locker WHERE user_id = ?";
$params = [$uid];
if ($filter_category !== '') { $sql .= " AND category = ?"; $params[] = $filter_category; }
$sql .= " ORDER BY category ASC, title ASC";
$entries = fetch_all($sql, $params);
$formCategories = category_options_with_current('digital_locker_category', $editEntry['category'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-shield-halved text-primary me-2"></i>Digital Locker</h1>
    <span class="text-muted"><?= count($entries) ?> saved</span>
</div>

<div class="alert alert-secondary small"><i class="fas fa-shield-halved me-1"></i> Passwords are encrypted before they're stored and only decrypted when you view or edit them here.</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editEntry ? 'Edit Entry' : 'Add Entry' ?></h6></div>
    <div class="card-body">
        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editEntry['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($formCategories as $c): ?><option value="<?= sanitize($c) ?>" <?= ($editEntry['category'] ?? 'Other') === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Facebook, Gmail, Home WiFi, Chase Bank" value="<?= sanitize($editEntry['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Username / Email</label>
                    <input type="text" name="username" class="form-control" autocomplete="off" value="<?= sanitize($editEntry['username'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Password *</label>
                    <div class="input-group">
                        <input type="password" name="password" id="lockerPasswordInput" class="form-control" autocomplete="new-password" value="<?= sanitize($editPasswordPlain) ?>" required>
                        <button type="button" class="btn btn-outline-secondary" id="lockerTogglePw" title="Show/Hide"><i class="fas fa-eye"></i></button>
                        <button type="button" class="btn btn-outline-secondary" id="lockerGenPw" title="Generate strong password"><i class="fas fa-dice"></i></button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Website / App URL</label>
                    <input type="text" name="url" class="form-control" placeholder="https://..." value="<?= sanitize($editEntry['url'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Security question, PIN, recovery info..." value="<?= sanitize($editEntry['notes'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> <?= $editEntry ? 'Update' : 'Add' ?></button>
                </div>
            </div>
            <?php if ($editEntry): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 fw-bold">Saved Entries</h6>
        <div class="btn-group btn-group-sm flex-wrap">
            <a href="?category=" class="btn btn-outline-secondary <?= $filter_category === '' ? 'active' : '' ?>">All</a>
            <?php foreach ($categories as $c): ?>
                <a href="?category=<?= urlencode($c) ?>" class="btn btn-outline-secondary <?= $filter_category === $c ? 'active' : '' ?>"><?= sanitize($c) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Title</th><th>Category</th><th>Username</th><th>Password</th><th>Website</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Nothing saved yet.</td></tr>
                    <?php else: foreach ($entries as $e):
                        $plain = decrypt_data($e['password']);
                    ?>
                        <tr>
                            <td class="fw-bold"><i class="fas fa-<?= $category_icons[$e['category']] ?? 'key' ?> text-muted me-1"></i><?= sanitize($e['title']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($e['category']) ?></span></td>
                            <td><?= $e['username'] ? sanitize($e['username']) : '<span class="text-muted">-</span>' ?></td>
                            <td>
                                <span class="locker-pw d-inline-flex align-items-center gap-1">
                                    <code class="locker-pw-mask">••••••••</code>
                                    <code class="locker-pw-value d-none"><?= sanitize($plain) ?></code>
                                    <button type="button" class="btn btn-xs btn-outline-secondary locker-toggle" title="Show/Hide"><i class="fas fa-eye"></i></button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary locker-copy" data-value="<?= sanitize($plain) ?>" title="Copy"><i class="fas fa-copy"></i></button>
                                </span>
                            </td>
                            <td>
                                <?php if ($e['url']): ?>
                                    <a href="<?= sanitize($e['url']) ?>" target="_blank" rel="noopener noreferrer">Visit <i class="fas fa-arrow-up-right-from-square fa-xs"></i></a>
                                <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $e['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this entry?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var pwInput = document.getElementById('lockerPasswordInput');
    var toggleBtn = document.getElementById('lockerTogglePw');
    if (toggleBtn && pwInput) {
        toggleBtn.addEventListener('click', function () {
            var showing = pwInput.type === 'text';
            pwInput.type = showing ? 'password' : 'text';
            toggleBtn.querySelector('i').className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }

    var genBtn = document.getElementById('lockerGenPw');
    if (genBtn && pwInput) {
        genBtn.addEventListener('click', function () {
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()-_=+';
            var arr = new Uint32Array(18);
            window.crypto.getRandomValues(arr);
            var out = '';
            for (var i = 0; i < arr.length; i++) out += chars[arr[i] % chars.length];
            pwInput.value = out;
            pwInput.type = 'text';
            toggleBtn.querySelector('i').className = 'fas fa-eye-slash';
        });
    }

    document.querySelectorAll('.locker-pw').forEach(function (wrap) {
        var mask = wrap.querySelector('.locker-pw-mask');
        var value = wrap.querySelector('.locker-pw-value');
        var toggle = wrap.querySelector('.locker-toggle');
        var copy = wrap.querySelector('.locker-copy');
        toggle.addEventListener('click', function () {
            var showing = !value.classList.contains('d-none');
            mask.classList.toggle('d-none', !showing);
            value.classList.toggle('d-none', showing);
            toggle.querySelector('i').className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
        copy.addEventListener('click', function () {
            var text = copy.dataset.value || '';
            if (!text) return;
            navigator.clipboard.writeText(text).then(function () {
                var icon = copy.querySelector('i');
                icon.className = 'fas fa-check';
                setTimeout(function () { icon.className = 'fas fa-copy'; }, 1200);
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
