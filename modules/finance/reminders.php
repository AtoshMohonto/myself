<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Payments & Reminders';
$active_page = 'finance';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/finance/reminders.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE finance_reminders SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/finance/reminders.php', 'Reminder deleted.', 'success');
    }

    if ($action === 'set_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Upcoming';
        if ($status === 'Paid') {
            $reminder = fetch_one("SELECT * FROM finance_reminders WHERE id = ? AND user_id = ?", [$id, $uid]);
            if ($reminder) {
                $account_id = (int) ($_POST['account_id'] ?? 0);
                if ($account_id > 0) {
                    $ownedAccount = fetch_one("SELECT id FROM finance_accounts WHERE id = ? AND user_id = ?", [$account_id, $uid]);
                    if ($ownedAccount) {
                        $txn_type = $reminder['type'] === 'Income' ? 'Income' : 'Expense';
                        db_query("INSERT INTO finance_transactions (user_id, account_id, type, category, amount, transaction_date, description) VALUES (?,?,?,?,?,?,?)",
                            [$uid, $account_id, $txn_type, $reminder['category'], (float) $reminder['amount'], date('Y-m-d'), 'Payment: ' . $reminder['title']]);
                        $delta = $txn_type === 'Income' ? (float) $reminder['amount'] : -((float) $reminder['amount']);
                        db_query("UPDATE finance_accounts SET balance = balance + ? WHERE id = ? AND user_id = ?", [$delta, $account_id, $uid]);
                    }
                }
                db_query("UPDATE finance_reminders SET status = 'Paid' WHERE id = ? AND user_id = ?", [$id, $uid]);
                redirect('modules/finance/reminders.php', 'Marked as paid.', 'success');
            }
        } else {
            db_query("UPDATE finance_reminders SET status = ? WHERE id = ? AND user_id = ?", [$status, $id, $uid]);
            redirect('modules/finance/reminders.php', 'Reminder updated.', 'success');
        }
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'Expense';
    $category = sanitize($_POST['category'] ?? 'Other');
    $amount = (float) ($_POST['amount'] ?? 0);
    $due_date = $_POST['due_date'] ?: date('Y-m-d');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($title === '' || $amount <= 0) {
        redirect('modules/finance/reminders.php', 'Title and a positive amount are required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE finance_reminders SET title=?, type=?, category=?, amount=?, due_date=?, notes=? WHERE id=? AND user_id=?",
            [$title, $type, $category, $amount, $due_date, $notes, $id, $uid]);
        redirect('modules/finance/reminders.php', 'Reminder updated.', 'success');
    } else {
        db_query("INSERT INTO finance_reminders (user_id, title, type, category, amount, due_date, notes) VALUES (?,?,?,?,?,?,?)",
            [$uid, $title, $type, $category, $amount, $due_date, $notes]);
        log_activity('Added payment reminder: ' . $title);
        redirect('modules/finance/reminders.php', 'Reminder added.', 'success');
    }
}

// Auto-mark past-due unpaid reminders as Overdue
db_query("UPDATE finance_reminders SET status = 'Overdue' WHERE status = 'Upcoming' AND due_date < CURDATE() AND isDelete = 0 AND user_id = ?", [$uid]);

$filter_status = $_GET['status'] ?? '';
$editReminder = null;
if (isset($_GET['edit'])) {
    $editReminder = fetch_one("SELECT * FROM finance_reminders WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}

$sql = "SELECT * FROM finance_reminders WHERE user_id = ?";
$params = [$uid];
if ($filter_status !== '') { $sql .= " AND status = ?"; $params[] = $filter_status; }
$sql .= " ORDER BY FIELD(status,'Overdue','Upcoming','Paid'), (due_date IS NULL), due_date ASC, id DESC";
$reminders = fetch_all($sql, $params);

$totals = fetch_one("SELECT
    COALESCE(SUM(CASE WHEN type='Income' AND status='Upcoming' THEN amount END),0) as to_receive,
    COALESCE(SUM(CASE WHEN type='Expense' AND status='Upcoming' THEN amount END),0) as to_pay,
    COALESCE(SUM(CASE WHEN status='Overdue' THEN amount END),0) as overdue,
    COALESCE(SUM(CASE WHEN type='Income' AND status='Paid' THEN amount END),0) as received,
    COALESCE(SUM(CASE WHEN type='Expense' AND status='Paid' THEN amount END),0) as paid
    FROM finance_reminders WHERE user_id = ?", [$uid]);

$accounts = fetch_all("SELECT * FROM finance_accounts WHERE user_id = ? ORDER BY name ASC", [$uid]);
$reminder_categories = category_options_with_current('finance_category', $editReminder['category'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-bell text-primary me-2"></i>Payments &amp; Reminders</h1>
    <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary d-print-none"><i class="fas fa-print me-1"></i> Print</button>
        <a href="transactions.php" class="btn btn-sm btn-outline-secondary d-print-none"><i class="fas fa-exchange-alt me-1"></i> Transactions</a>
    </div>
</div>

<div class="d-none d-print-block mb-4">
    <div class="d-flex justify-content-between align-items-end border-bottom pb-2 mb-3">
        <div>
            <h3 class="fw-bold mb-0"><?= sanitize(APP_NAME) ?> — Payments &amp; Reminders Statement</h3>
            <div class="text-muted small"><?= sanitize(current_user()['full_name'] ?? '') ?></div>
        </div>
        <div class="text-muted small">Generated: <?= date('d M Y, h:i A') ?></div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-left-primary shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-primary text-uppercase mb-1">To Receive</div>
            <div class="h5 fw-bold mb-0 text-primary"><?= format_currency($totals['to_receive'] ?? 0) ?></div>
            <small class="text-muted">Salary, lend, etc.</small>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-left-danger shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-danger text-uppercase mb-1">To Pay</div>
            <div class="h5 fw-bold mb-0 text-danger"><?= format_currency($totals['to_pay'] ?? 0) ?></div>
            <small class="text-muted">Tuition, rent, etc.</small>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-left-warning shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Overdue</div>
            <div class="h5 fw-bold mb-0 text-warning"><?= format_currency($totals['overdue'] ?? 0) ?></div>
            <small class="text-muted">Needs attention</small>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-left-success shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-success text-uppercase mb-1">Received / Paid</div>
            <div class="h5 fw-bold mb-0 text-success"><?= format_currency(($totals['received'] ?? 0) + ($totals['paid'] ?? 0)) ?></div>
            <small class="text-muted">Completed so far</small>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4 d-print-none">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editReminder ? 'Edit Reminder' : 'Add Reminder' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editReminder['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Tuition fee, Salary, Lend to Rahim" value="<?= sanitize($editReminder['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <option value="Income" <?= ($editReminder['type'] ?? '') === 'Income' ? 'selected' : '' ?>>Income (to receive)</option>
                        <option value="Expense" <?= ($editReminder['type'] ?? '') === 'Expense' ? 'selected' : '' ?>>Expense (to pay)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($reminder_categories as $c): ?><option value="<?= sanitize($c) ?>" <?= ($editReminder['category'] ?? '') === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Amount *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= $editReminder['amount'] ?? '' ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Due Date *</label>
                    <input type="date" name="due_date" class="form-control" value="<?= $editReminder['due_date'] ?? date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-10">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editReminder['notes'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> <?= $editReminder ? 'Update' : 'Add' ?></button>
                </div>
            </div>
            <?php if ($editReminder): ?><a href="reminders.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 fw-bold">Reminders</h6>
        <div class="btn-group btn-group-sm d-print-none">
            <a href="?status=" class="btn btn-outline-secondary <?= $filter_status === '' ? 'active' : '' ?>">All</a>
            <a href="?status=Overdue" class="btn btn-outline-danger <?= $filter_status === 'Overdue' ? 'active' : '' ?>">Overdue</a>
            <a href="?status=Upcoming" class="btn btn-outline-primary <?= $filter_status === 'Upcoming' ? 'active' : '' ?>">Upcoming</a>
            <a href="?status=Paid" class="btn btn-outline-success <?= $filter_status === 'Paid' ? 'active' : '' ?>">Paid</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Due Date</th><th>Title</th><th>Type</th><th>Category</th><th class="text-end">Amount</th><th>Status</th><th class="d-print-none">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($reminders)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No reminders yet.</td></tr>
                    <?php else: foreach ($reminders as $r): ?>
                        <tr class="<?= $r['status'] === 'Overdue' ? 'table-danger' : '' ?>">
                            <td class="fw-bold"><?= format_date($r['due_date']) ?><?= (int) days_until($r['due_date']) < 0 && $r['status'] !== 'Paid' ? ' <span class="badge bg-danger">' . abs((int) days_until($r['due_date'])) . 'd late</span>' : '' ?></td>
                            <td class="fw-bold"><?= sanitize($r['title']) ?><?= $r['notes'] ? '<div class="small text-muted">' . sanitize($r['notes']) . '</div>' : '' ?></td>
                            <td><span class="badge bg-<?= $r['type'] === 'Income' ? 'success' : 'danger' ?>"><?= $r['type'] ?></span></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($r['category']) ?></span></td>
                            <td class="text-end fw-bold <?= $r['type'] === 'Income' ? 'text-success' : 'text-danger' ?>"><?= $r['type'] === 'Income' ? '+' : '-' ?><?= format_currency($r['amount']) ?></td>
                            <td><span class="badge bg-<?= status_badge_class($r['status']) ?>"><?= $r['status'] ?></span></td>
                            <td class="text-nowrap d-print-none">
                                <?php if ($r['status'] !== 'Paid'): ?>
                                <form method="POST" class="d-inline-flex align-items-center gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="Paid">
                                    <?php if ($accounts): ?>
                                    <select name="account_id" class="form-select form-select-sm d-inline-block w-auto" title="Record transaction to account (optional)">
                                        <option value="0">Mark paid only</option>
                                        <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>"><?= sanitize($a['name']) ?></option><?php endforeach; ?>
                                    </select>
                                    <?php endif; ?>
                                    <button class="btn btn-xs btn-outline-success" title="Mark Paid"><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <a href="?edit=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this reminder?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
