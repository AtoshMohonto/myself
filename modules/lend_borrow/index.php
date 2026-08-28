<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Lend & Borrow';
$active_page = 'lend_borrow';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/lend_borrow/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE lend_borrow SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/lend_borrow/index.php', 'Entry deleted.', 'success');
    }

    if ($action === 'mark_returned') {
        db_query("UPDATE lend_borrow SET status = 'Returned', returned_date = CURDATE() WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/lend_borrow/index.php', 'Marked as returned.', 'success');
    }

    if ($action === 'write_off') {
        db_query("UPDATE lend_borrow SET status = 'Written Off' WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/lend_borrow/index.php', 'Marked as written off.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $direction = ($_POST['direction'] ?? '') === 'Borrowed' ? 'Borrowed' : 'Lent';
    $item_type = ($_POST['item_type'] ?? '') === 'Item' ? 'Item' : 'Money';
    $person_name = sanitize($_POST['person_name'] ?? '');
    $person_contact = sanitize($_POST['person_contact'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $amount = $item_type === 'Money' ? (float) ($_POST['amount'] ?? 0) : null;
    $is_returnable = isset($_POST['is_returnable']) ? 1 : 0;
    $date_given = $_POST['date_given'] ?: date('Y-m-d');
    $due_date = $is_returnable ? (($_POST['due_date'] ?? '') ?: null) : null;
    $notes = sanitize($_POST['notes'] ?? '');

    if ($person_name === '' || $description === '' || ($item_type === 'Money' && $amount <= 0)) {
        redirect('modules/lend_borrow/index.php', 'Person, description, and a positive amount (for money) are required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE lend_borrow SET direction=?, item_type=?, person_name=?, person_contact=?, description=?, amount=?, is_returnable=?, date_given=?, due_date=?, notes=? WHERE id=? AND user_id=?",
            [$direction, $item_type, $person_name, $person_contact, $description, $amount, $is_returnable, $date_given, $due_date, $notes, $id, $uid]);
        redirect('modules/lend_borrow/index.php', 'Entry updated.', 'success');
    } else {
        db_query("INSERT INTO lend_borrow (user_id, direction, item_type, person_name, person_contact, description, amount, is_returnable, date_given, due_date, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$uid, $direction, $item_type, $person_name, $person_contact, $description, $amount, $is_returnable, $date_given, $due_date, $notes]);
        log_activity(($direction === 'Lent' ? 'Lent ' : 'Borrowed ') . $description . ' ' . ($direction === 'Lent' ? 'to' : 'from') . ' ' . $person_name);
        redirect('modules/lend_borrow/index.php', 'Entry added.', 'success');
    }
}

// Auto-mark past-due returnable entries as Overdue
db_query("UPDATE lend_borrow SET status = 'Overdue' WHERE status = 'Pending' AND is_returnable = 1 AND due_date IS NOT NULL AND due_date < CURDATE() AND isDelete = 0 AND user_id = ?", [$uid]);

$filter_direction = $_GET['direction'] ?? '';
$filter_status = $_GET['status'] ?? '';
$editEntry = null;
if (isset($_GET['edit'])) {
    $editEntry = fetch_one("SELECT * FROM lend_borrow WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}

$sql = "SELECT * FROM lend_borrow WHERE user_id = ?";
$params = [$uid];
if ($filter_direction !== '') { $sql .= " AND direction = ?"; $params[] = $filter_direction; }
if ($filter_status !== '') { $sql .= " AND status = ?"; $params[] = $filter_status; }
$sql .= " ORDER BY FIELD(status,'Overdue','Pending','Returned','Written Off'), (due_date IS NULL), due_date ASC, date_given DESC";
$entries = fetch_all($sql, $params);

$totals = fetch_one("SELECT
    COALESCE(SUM(CASE WHEN direction='Lent' AND item_type='Money' AND status IN ('Pending','Overdue') THEN amount END),0) as money_owed_to_you,
    COALESCE(SUM(CASE WHEN direction='Borrowed' AND item_type='Money' AND status IN ('Pending','Overdue') THEN amount END),0) as money_you_owe,
    COALESCE(SUM(CASE WHEN direction='Lent' AND item_type='Item' AND status IN ('Pending','Overdue') THEN 1 END),0) as items_out,
    COALESCE(SUM(CASE WHEN direction='Borrowed' AND item_type='Item' AND status IN ('Pending','Overdue') THEN 1 END),0) as items_held,
    COALESCE(SUM(CASE WHEN status='Overdue' THEN 1 END),0) as overdue_count
    FROM lend_borrow WHERE user_id = ?", [$uid]);
$net_balance = ($totals['money_owed_to_you'] ?? 0) - ($totals['money_you_owe'] ?? 0);

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-handshake text-primary me-2"></i>Lend &amp; Borrow</h1>
    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary d-print-none"><i class="fas fa-print me-1"></i> Print</button>
</div>

<div class="d-none d-print-block mb-4">
    <div class="d-flex justify-content-between align-items-end border-bottom pb-2 mb-3">
        <div>
            <h3 class="fw-bold mb-0"><?= sanitize(APP_NAME) ?> — Lend &amp; Borrow Statement</h3>
            <div class="text-muted small"><?= sanitize(current_user()['full_name'] ?? '') ?></div>
        </div>
        <div class="text-muted small">Generated: <?= date('d M Y, h:i A') ?></div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-left-success shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-success text-uppercase mb-1">Owed To You</div>
            <div class="h5 fw-bold mb-0 text-success"><?= format_currency($totals['money_owed_to_you'] ?? 0) ?></div>
            <small class="text-muted">Money you've lent out, unpaid</small>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-left-danger shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-danger text-uppercase mb-1">You Owe</div>
            <div class="h5 fw-bold mb-0 text-danger"><?= format_currency($totals['money_you_owe'] ?? 0) ?></div>
            <small class="text-muted">Money you've borrowed, unpaid</small>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-left-primary shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Net Balance</div>
            <div class="h5 fw-bold mb-0 <?= $net_balance >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_currency($net_balance) ?></div>
            <small class="text-muted"><?= $net_balance >= 0 ? 'In your favor' : 'You owe overall' ?></small>
        </div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-left-warning shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Items In Play</div>
            <div class="h5 fw-bold mb-0"><?= (int) ($totals['items_out'] ?? 0) ?> out / <?= (int) ($totals['items_held'] ?? 0) ?> held</div>
            <small class="text-muted"><?= (int) ($totals['overdue_count'] ?? 0) ?> overdue total</small>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4 d-print-none">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editEntry ? 'Edit Entry' : 'Add Entry' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editEntry['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Direction</label>
                    <select name="direction" class="form-select">
                        <option value="Lent" <?= ($editEntry['direction'] ?? 'Lent') === 'Lent' ? 'selected' : '' ?>>I lent (gave out)</option>
                        <option value="Borrowed" <?= ($editEntry['direction'] ?? '') === 'Borrowed' ? 'selected' : '' ?>>I borrowed (took)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <select name="item_type" id="lbItemType" class="form-select">
                        <option value="Money" <?= ($editEntry['item_type'] ?? 'Money') === 'Money' ? 'selected' : '' ?>>Money</option>
                        <option value="Item" <?= ($editEntry['item_type'] ?? '') === 'Item' ? 'selected' : '' ?>>Item / Gadget</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Person *</label>
                    <input type="text" name="person_name" class="form-control" placeholder="Name" value="<?= sanitize($editEntry['person_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Contact</label>
                    <input type="text" name="person_contact" class="form-control" placeholder="Phone / email (optional)" value="<?= sanitize($editEntry['person_contact'] ?? '') ?>">
                </div>
                <div class="col-md-2" id="lbAmountWrap">
                    <label class="form-label fw-bold">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" value="<?= $editEntry['amount'] ?? '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Description *</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Cash for rent, Bluetooth speaker, Textbook" value="<?= sanitize($editEntry['description'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" name="date_given" class="form-control" value="<?= $editEntry['date_given'] ?? date('Y-m-d') ?>">
                </div>
                <div class="col-md-2" id="lbDueWrap">
                    <label class="form-label fw-bold">Expected Return</label>
                    <input type="date" name="due_date" class="form-control" value="<?= $editEntry['due_date'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="lbReturnable" name="is_returnable" value="1" <?= !isset($editEntry) || !empty($editEntry['is_returnable']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lbReturnable">Must be returned</label>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editEntry['notes'] ?? '') ?>">
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
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 d-print-none">
        <h6 class="m-0 fw-bold">All Entries</h6>
        <div class="d-flex gap-2 flex-wrap">
            <div class="btn-group btn-group-sm">
                <a href="?direction=" class="btn btn-outline-secondary <?= $filter_direction === '' ? 'active' : '' ?>">All</a>
                <a href="?direction=Lent" class="btn btn-outline-secondary <?= $filter_direction === 'Lent' ? 'active' : '' ?>">Lent</a>
                <a href="?direction=Borrowed" class="btn btn-outline-secondary <?= $filter_direction === 'Borrowed' ? 'active' : '' ?>">Borrowed</a>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="?status=" class="btn btn-outline-secondary <?= $filter_status === '' ? 'active' : '' ?>">Any Status</a>
                <a href="?status=Pending" class="btn btn-outline-secondary <?= $filter_status === 'Pending' ? 'active' : '' ?>">Pending</a>
                <a href="?status=Overdue" class="btn btn-outline-danger <?= $filter_status === 'Overdue' ? 'active' : '' ?>">Overdue</a>
                <a href="?status=Returned" class="btn btn-outline-success <?= $filter_status === 'Returned' ? 'active' : '' ?>">Returned</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Direction</th><th>Person</th><th>Description</th><th class="text-end">Amount</th><th>Return By</th><th>Status</th><th class="d-print-none">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">No lend/borrow entries yet.</td></tr>
                    <?php else: foreach ($entries as $e): ?>
                        <tr class="<?= $e['status'] === 'Overdue' ? 'table-danger' : '' ?>">
                            <td class="text-sm"><?= format_date($e['date_given']) ?></td>
                            <td><span class="badge bg-<?= $e['direction'] === 'Lent' ? 'success' : 'danger' ?>"><?= $e['direction'] ?></span></td>
                            <td class="fw-bold"><?= sanitize($e['person_name']) ?><?= $e['person_contact'] ? '<div class="small text-muted">' . sanitize($e['person_contact']) . '</div>' : '' ?></td>
                            <td><?= sanitize($e['description']) ?><?= $e['notes'] ? '<div class="small text-muted">' . sanitize($e['notes']) . '</div>' : '' ?></td>
                            <td class="text-end fw-bold"><?= $e['item_type'] === 'Money' ? format_currency($e['amount']) : '<span class="text-muted">item</span>' ?></td>
                            <td class="text-sm">
                                <?php if (!$e['is_returnable']): ?><span class="text-muted">Gift</span>
                                <?php elseif ($e['due_date']): ?><?= format_date($e['due_date']) ?><?= (int) days_until($e['due_date']) < 0 && $e['status'] === 'Overdue' ? ' <span class="badge bg-danger d-print-none">' . abs((int) days_until($e['due_date'])) . 'd late</span>' : '' ?>
                                <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= !$e['is_returnable'] ? 'secondary' : status_badge_class($e['status']) ?>"><?= !$e['is_returnable'] ? 'Given' : $e['status'] ?></span></td>
                            <td class="text-nowrap d-print-none">
                                <?php if ($e['is_returnable'] && !in_array($e['status'], ['Returned', 'Written Off'], true)): ?>
                                <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="mark_returned"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-xs btn-outline-success" title="Mark Returned"><i class="fas fa-check"></i></button></form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Write this off as not being returned?');"><?= csrf_field() ?><input type="hidden" name="action" value="write_off"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-xs btn-outline-secondary" title="Write Off"><i class="fas fa-ban"></i></button></form>
                                <?php endif; ?>
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
    var typeSelect = document.getElementById('lbItemType');
    var amountWrap = document.getElementById('lbAmountWrap');
    var returnable = document.getElementById('lbReturnable');
    var dueWrap = document.getElementById('lbDueWrap');
    function syncType() {
        amountWrap.style.display = typeSelect.value === 'Money' ? '' : 'none';
    }
    function syncReturnable() {
        dueWrap.style.display = returnable.checked ? '' : 'none';
    }
    if (typeSelect) { typeSelect.addEventListener('change', syncType); syncType(); }
    if (returnable) { returnable.addEventListener('change', syncReturnable); syncReturnable(); }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
