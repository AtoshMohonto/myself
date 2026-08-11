<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Finance';
$active_page = 'finance';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/finance/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE finance_accounts SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/finance/index.php', 'Account deleted.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'Cash';
    $opening_balance = (float) ($_POST['opening_balance'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($name === '') {
        redirect('modules/finance/index.php', 'Account name is required.', 'danger');
    }

    if ($id > 0) {
        $old = fetch_one("SELECT opening_balance FROM finance_accounts WHERE id = ? AND user_id = ?", [$id, $uid]);
        $diff = $opening_balance - (float) ($old['opening_balance'] ?? 0);
        db_query("UPDATE finance_accounts SET name=?, type=?, opening_balance=?, balance=balance+?, notes=? WHERE id=? AND user_id=?",
            [$name, $type, $opening_balance, $diff, $notes, $id, $uid]);
        redirect('modules/finance/index.php', 'Account updated.', 'success');
    } else {
        db_query("INSERT INTO finance_accounts (user_id, name, type, opening_balance, balance, notes) VALUES (?,?,?,?,?,?)",
            [$uid, $name, $type, $opening_balance, $opening_balance, $notes]);
        log_activity('Added finance account: ' . $name);
        redirect('modules/finance/index.php', 'Account added.', 'success');
    }
}

$editAccount = null;
if (isset($_GET['edit'])) {
    $editAccount = fetch_one("SELECT * FROM finance_accounts WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$accounts = fetch_all("SELECT * FROM finance_accounts WHERE user_id = ? ORDER BY name ASC", [$uid]);
$total_balance = array_sum(array_column($accounts, 'balance'));

$month_income = fetch_one("SELECT COALESCE(SUM(amount),0) as total FROM finance_transactions WHERE user_id = ? AND type='Income' AND transaction_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')", [$uid])['total'] ?? 0;
$month_expense = fetch_one("SELECT COALESCE(SUM(amount),0) as total FROM finance_transactions WHERE user_id = ? AND type='Expense' AND transaction_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')", [$uid])['total'] ?? 0;

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-wallet text-primary me-2"></i>Finance</h1>
    <a href="transactions.php" class="btn btn-sm btn-primary"><i class="fas fa-exchange-alt me-1"></i> Transactions</a>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-left-primary shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Net Balance</div>
            <div class="h4 fw-bold mb-0"><?= format_currency($total_balance) ?></div>
        </div></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-left-success shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-success text-uppercase mb-1">This Month Income</div>
            <div class="h4 fw-bold mb-0 text-success"><?= format_currency($month_income) ?></div>
        </div></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-left-danger shadow-sm h-100"><div class="card-body">
            <div class="text-xs fw-bold text-danger text-uppercase mb-1">This Month Expense</div>
            <div class="h4 fw-bold mb-0 text-danger"><?= format_currency($month_expense) ?></div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editAccount ? 'Edit Account' : 'Add Account' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editAccount['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Account Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($editAccount['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <?php foreach (['Bank','Cash','Mobile Wallet','Savings','Investment','Other'] as $t): ?><option value="<?= $t ?>" <?= ($editAccount['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Opening Balance</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?= $editAccount['opening_balance'] ?? 0 ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editAccount ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editAccount['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editAccount): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3"><h6 class="m-0 fw-bold">Accounts</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Type</th><th class="text-end">Balance</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No accounts yet.</td></tr>
                    <?php else: foreach ($accounts as $a): ?>
                        <tr>
                            <td class="fw-bold"><?= sanitize($a['name']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= $a['type'] ?></span></td>
                            <td class="text-end fw-bold <?= $a['balance'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_currency($a['balance']) ?></td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $a['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this account?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
