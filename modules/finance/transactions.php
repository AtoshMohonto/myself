<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Transactions';
$active_page = 'finance';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/finance/transactions.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $txn = fetch_one("SELECT * FROM finance_transactions WHERE id = ? AND user_id = ?", [$id, $uid]);
        if ($txn) {
            $reverse = $txn['type'] === 'Income' ? -1 : 1;
            db_query("UPDATE finance_accounts SET balance = balance + ? WHERE id = ? AND user_id = ?", [$reverse * (float) $txn['amount'], $txn['account_id'], $uid]);
            db_query("UPDATE finance_transactions SET isDelete = 1 WHERE id = ? AND user_id = ?", [$id, $uid]);
        }
        redirect('modules/finance/transactions.php', 'Transaction deleted.', 'success');
    }

    $account_id = (int) ($_POST['account_id'] ?? 0);
    $type = $_POST['type'] ?? 'Expense';
    $category = sanitize($_POST['category'] ?? 'General');
    $amount = (float) ($_POST['amount'] ?? 0);
    $transaction_date = $_POST['transaction_date'] ?: date('Y-m-d');
    $description = sanitize($_POST['description'] ?? '');

    // Confirm the account is actually this user's before touching it
    $ownedAccount = $account_id ? fetch_one("SELECT id FROM finance_accounts WHERE id = ? AND user_id = ?", [$account_id, $uid]) : null;

    if (!$ownedAccount || $amount <= 0) {
        redirect('modules/finance/transactions.php', 'Account and a positive amount are required.', 'danger');
    }

    db_query("INSERT INTO finance_transactions (user_id, account_id, type, category, amount, transaction_date, description) VALUES (?,?,?,?,?,?,?)",
        [$uid, $account_id, $type, $category, $amount, $transaction_date, $description]);

    $delta = $type === 'Income' ? $amount : -$amount;
    db_query("UPDATE finance_accounts SET balance = balance + ? WHERE id = ? AND user_id = ?", [$delta, $account_id, $uid]);
    log_activity("Recorded $type of $amount");
    redirect('modules/finance/transactions.php', 'Transaction recorded.', 'success');
}

$accounts = fetch_all("SELECT * FROM finance_accounts WHERE user_id = ? ORDER BY name ASC", [$uid]);
$filter_account = (int) ($_GET['account_id'] ?? 0);
$filter_type = $_GET['type'] ?? '';

$sql = "SELECT t.*, a.name as account_name FROM finance_transactions t JOIN finance_accounts a ON t.account_id = a.id WHERE t.isDelete = 0 AND t.user_id = ?";
$params = [$uid];
if ($filter_account) { $sql .= " AND t.account_id = ?"; $params[] = $filter_account; }
if ($filter_type) { $sql .= " AND t.type = ?"; $params[] = $filter_type; }
$sql .= " ORDER BY t.transaction_date DESC, t.id DESC LIMIT 200";
$transactions = fetch_all($sql, $params);

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-exchange-alt text-primary me-2"></i>Transactions</h1>
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Accounts</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold">Record Transaction</h6></div>
    <div class="card-body">
        <?php if (empty($accounts)): ?>
            <p class="text-muted mb-0">Add a <a href="index.php">finance account</a> first.</p>
        <?php else: ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Account *</label>
                    <select name="account_id" class="form-select" required>
                        <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>"><?= sanitize($a['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <option value="Income">Income</option>
                        <option value="Expense">Expense</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="Salary, Food, Tuition..." value="General">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Amount *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add</button>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control">
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 fw-bold">Transaction History</h6>
        <form method="GET" class="d-flex gap-2">
            <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="0">All Accounts</option>
                <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>" <?= $filter_account === (int) $a['id'] ? 'selected' : '' ?>><?= sanitize($a['name']) ?></option><?php endforeach; ?>
            </select>
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="Income" <?= $filter_type === 'Income' ? 'selected' : '' ?>>Income</option>
                <option value="Expense" <?= $filter_type === 'Expense' ? 'selected' : '' ?>>Expense</option>
            </select>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No transactions yet.</td></tr>
                    <?php else: foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= format_date($t['transaction_date']) ?></td>
                            <td><?= sanitize($t['account_name']) ?></td>
                            <td><span class="badge bg-<?= $t['type'] === 'Income' ? 'success' : 'danger' ?>"><?= $t['type'] ?></span></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($t['category']) ?></span></td>
                            <td class="text-sm"><?= sanitize($t['description']) ?></td>
                            <td class="text-end fw-bold <?= $t['type'] === 'Income' ? 'text-success' : 'text-danger' ?>"><?= $t['type'] === 'Income' ? '+' : '-' ?><?= format_currency($t['amount']) ?></td>
                            <td><form method="POST" onsubmit="return confirm('Delete this transaction? This will reverse the account balance.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
