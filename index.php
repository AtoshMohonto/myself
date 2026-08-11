<?php
require_once __DIR__ . '/includes/functions.php';
if (!isset($_SESSION['user_id'])) {
    redirect('site.php');
}
$page_title = 'Dashboard';
$active_page = 'dashboard';
$uid = my_id();

$today = date('Y-m-d');

$today_schedule = fetch_all("SELECT * FROM schedule_events WHERE user_id = ? AND event_date = ? ORDER BY start_time ASC", [$uid, $today]);
$pending_tasks = fetch_all("SELECT * FROM tasks WHERE user_id = ? AND status IN ('Pending','In Progress') ORDER BY (due_date IS NULL), due_date ASC LIMIT 6", [$uid]);
$task_counts = fetch_one("SELECT
    SUM(status='Pending') as pending, SUM(status='In Progress') as in_progress,
    SUM(status='Done') as done, SUM(due_date < CURDATE() AND status NOT IN ('Done','Cancelled')) as overdue
    FROM tasks WHERE user_id = ?", [$uid]);

$active_goals = fetch_all("SELECT * FROM goals WHERE user_id = ? AND status = 'Active' ORDER BY (target_date IS NULL), target_date ASC LIMIT 5", [$uid]);
$bucket_counts = fetch_one("SELECT COUNT(*) as total, SUM(status='Done') as done FROM bucket_list WHERE user_id = ?", [$uid]);

$total_balance = fetch_one("SELECT COALESCE(SUM(balance),0) as total FROM finance_accounts WHERE user_id = ?", [$uid])['total'] ?? 0;
$month_income = fetch_one("SELECT COALESCE(SUM(amount),0) as total FROM finance_transactions WHERE user_id = ? AND type='Income' AND transaction_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')", [$uid])['total'] ?? 0;
$month_expense = fetch_one("SELECT COALESCE(SUM(amount),0) as total FROM finance_transactions WHERE user_id = ? AND type='Expense' AND transaction_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')", [$uid])['total'] ?? 0;

$upcoming_work = fetch_all("SELECT * FROM work_schedules WHERE user_id = ? AND status IN ('Upcoming','Ongoing') AND (schedule_date IS NULL OR schedule_date >= ?) ORDER BY (schedule_date IS NULL), schedule_date ASC LIMIT 5", [$uid, $today]);

$low_stock_meds = fetch_all("SELECT * FROM medicines WHERE user_id = ? AND status = 'Active' AND stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 5", [$uid]);
$active_meds_count = fetch_one("SELECT COUNT(*) as cnt FROM medicines WHERE user_id = ? AND status = 'Active'", [$uid])['cnt'] ?? 0;

$skills_count = fetch_one("SELECT SUM(type='Hard Skill') as hard, SUM(type='Soft Skill') as soft FROM skills WHERE user_id = ?", [$uid]);

$upcoming_posts = fetch_all("SELECT * FROM social_posts WHERE user_id = ? AND status IN ('Planned','Scheduled') ORDER BY (scheduled_date IS NULL), scheduled_date ASC LIMIT 5", [$uid]);

include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-gauge-high text-primary me-2"></i>Dashboard</h1>
    <span class="text-muted"><?= date('l, d M Y') ?></span>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Tasks Pending</div>
                    <div class="h4 mb-0 fw-bold"><?= (int) ($task_counts['pending'] ?? 0) + (int) ($task_counts['in_progress'] ?? 0) ?></div>
                    <?php if (($task_counts['overdue'] ?? 0) > 0): ?><small class="text-danger"><?= $task_counts['overdue'] ?> overdue</small><?php endif; ?>
                </div>
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-list-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Net Balance</div>
                    <div class="h5 mb-0 fw-bold"><?= format_currency($total_balance) ?></div>
                    <small class="text-muted">this month: +<?= format_currency($month_income) ?> / -<?= format_currency($month_expense) ?></small>
                </div>
                <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-xs fw-bold text-info text-uppercase mb-1">Active Goals</div>
                    <div class="h4 mb-0 fw-bold"><?= count($active_goals) ?></div>
                    <small class="text-muted">Bucket list: <?= (int) ($bucket_counts['done'] ?? 0) ?>/<?= (int) ($bucket_counts['total'] ?? 0) ?> done</small>
                </div>
                <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="fas fa-bullseye"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">Skills Tracked</div>
                    <div class="h4 mb-0 fw-bold"><?= (int) ($skills_count['hard'] ?? 0) + (int) ($skills_count['soft'] ?? 0) ?></div>
                    <small class="text-muted"><?= (int) ($skills_count['hard'] ?? 0) ?> hard · <?= (int) ($skills_count['soft'] ?? 0) ?> soft</small>
                </div>
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-brain"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-calendar-day me-1"></i> Today's Schedule</h6>
                <a href="modules/schedule/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($today_schedule)): ?>
                    <p class="text-muted text-center py-3 mb-0">Nothing scheduled today.</p>
                <?php else: foreach ($today_schedule as $e): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <span class="fw-bold"><?= sanitize($e['title']) ?></span>
                            <span class="badge bg-light text-dark ms-1"><?= $e['category'] ?></span>
                        </div>
                        <span class="text-muted small"><?= $e['start_time'] ? format_time($e['start_time']) : '' ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-list-check me-1"></i> Tasks To Do</h6>
                <a href="modules/tasks/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($pending_tasks)): ?>
                    <p class="text-muted text-center py-3 mb-0">No pending tasks. 🎉</p>
                <?php else: foreach ($pending_tasks as $t): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span class="fw-bold"><?= sanitize($t['title']) ?></span>
                        <span class="badge bg-<?= status_badge_class($t['priority'] === 'Urgent' ? 'Overdue' : $t['status']) ?>"><?= $t['due_date'] ? format_date($t['due_date']) : $t['priority'] ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-briefcase me-1"></i> Upcoming Work</h6>
                <a href="modules/work/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_work)): ?>
                    <p class="text-muted text-center py-3 mb-0">Nothing on the work calendar.</p>
                <?php else: foreach ($upcoming_work as $w): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div><span class="fw-bold"><?= sanitize($w['title']) ?></span> <span class="badge bg-light text-dark ms-1"><?= $w['work_type'] ?></span></div>
                        <span class="text-muted small"><?= $w['schedule_date'] ? format_date($w['schedule_date']) : 'Recurring' ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-bullseye me-1"></i> Active Goals</h6>
                <a href="modules/goals/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($active_goals)): ?>
                    <p class="text-muted text-center py-3 mb-0">No active goals yet.</p>
                <?php else: foreach ($active_goals as $g): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold small"><?= sanitize($g['title']) ?></span>
                            <span class="text-muted small"><?= (int) $g['progress'] ?>%</span>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width:<?= (int) $g['progress'] ?>%"></div></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-pills me-1"></i> Medicines</h6>
                <a href="modules/medicines/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2"><?= $active_meds_count ?> active medicine(s)</p>
                <?php if (empty($low_stock_meds)): ?>
                    <p class="text-muted text-center py-2 mb-0">Stock levels look fine.</p>
                <?php else: foreach ($low_stock_meds as $m): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span class="fw-bold"><?= sanitize($m['name']) ?></span>
                        <span class="badge bg-danger">Low: <?= (int) $m['stock_quantity'] ?> <?= sanitize($m['unit']) ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-hashtag me-1"></i> Content Calendar</h6>
                <a href="modules/social/posts.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_posts)): ?>
                    <p class="text-muted text-center py-3 mb-0">No posts planned.</p>
                <?php else: foreach ($upcoming_posts as $p): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div><span class="fw-bold"><?= sanitize($p['title']) ?></span> <span class="badge bg-light text-dark ms-1"><?= $p['post_type'] ?></span></div>
                        <span class="badge bg-<?= status_badge_class($p['status']) ?>"><?= $p['status'] ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
