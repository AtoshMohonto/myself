<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Schedule';
$active_page = 'schedule';
$uid = my_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/schedule/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE schedule_events SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/schedule/index.php', 'Event deleted.', 'success');
    }

    if ($action === 'mark_done') {
        db_query("UPDATE schedule_events SET status = 'Done' WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/schedule/index.php', 'Marked as done.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'Personal';
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?: null;
    $end_time = $_POST['end_time'] ?: null;
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_type = $is_recurring ? ($_POST['recurrence_type'] ?? 'Daily') : 'None';

    if ($title === '' || $event_date === '') {
        redirect('modules/schedule/index.php', 'Title and date are required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE schedule_events SET title=?, description=?, category=?, event_date=?, start_time=?, end_time=?, is_recurring=?, recurrence_type=? WHERE id=? AND user_id=?",
            [$title, $description, $category, $event_date, $start_time, $end_time, $is_recurring, $recurrence_type, $id, $uid]);
        redirect('modules/schedule/index.php', 'Event updated.', 'success');
    } else {
        db_query("INSERT INTO schedule_events (user_id, title, description, category, event_date, start_time, end_time, is_recurring, recurrence_type) VALUES (?,?,?,?,?,?,?,?,?)",
            [$uid, $title, $description, $category, $event_date, $start_time, $end_time, $is_recurring, $recurrence_type]);
        log_activity('Added schedule event: ' . $title);
        redirect('modules/schedule/index.php', 'Event added.', 'success');
    }
}

// Auto-mark past Upcoming events as Missed (this user only)
db_query("UPDATE schedule_events SET status = 'Missed' WHERE status = 'Upcoming' AND event_date < CURDATE() AND isDelete = 0 AND user_id = ?", [$uid]);

$filter_date = $_GET['date'] ?? '';
$editEvent = null;
if (isset($_GET['edit'])) {
    $editEvent = fetch_one("SELECT * FROM schedule_events WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}

$sql = "SELECT * FROM schedule_events WHERE user_id = ?";
$params = [$uid];
if ($filter_date !== '') { $sql .= " AND event_date = ?"; $params[] = $filter_date; }
$sql .= " ORDER BY event_date DESC, start_time ASC";
$events = fetch_all($sql, $params);
$categories = category_options_with_current('schedule', $editEvent['category'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-days text-primary me-2"></i>Schedule &amp; Activities</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editEvent ? 'Edit Event' : 'Add Event' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editEvent['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" value="<?= sanitize($editEvent['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= sanitize($c) ?>" <?= ($editEvent['category'] ?? '') === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date *</label>
                    <input type="date" name="event_date" class="form-control" value="<?= $editEvent['event_date'] ?? date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Start</label>
                    <input type="time" name="start_time" class="form-control" value="<?= $editEvent['start_time'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">End</label>
                    <input type="time" name="end_time" class="form-control" value="<?= $editEvent['end_time'] ?? '' ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($editEvent['description'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="isRecurring" name="is_recurring" value="1" <?= !empty($editEvent['is_recurring']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isRecurring">Recurring</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Repeats</label>
                    <select name="recurrence_type" class="form-select">
                        <?php foreach (['Daily','Weekly','Monthly'] as $r): ?>
                            <option value="<?= $r ?>" <?= ($editEvent['recurrence_type'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary"><i class="fas fa-save me-1"></i> <?= $editEvent ? 'Update' : 'Add' ?> Event</button>
                <?php if ($editEvent): ?><a href="index.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold">All Events</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="date" class="form-control form-control-sm" value="<?= sanitize($filter_date) ?>">
            <button class="btn btn-sm btn-outline-primary">Filter</button>
            <?php if ($filter_date): ?><a href="index.php" class="btn btn-sm btn-outline-secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Time</th><th>Title</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No events yet.</td></tr>
                    <?php else: foreach ($events as $e): ?>
                        <tr>
                            <td><?= format_date($e['event_date']) ?></td>
                            <td class="text-sm"><?= $e['start_time'] ? format_time($e['start_time']) : '-' ?><?= $e['end_time'] ? ' – ' . format_time($e['end_time']) : '' ?></td>
                            <td class="fw-bold"><?= sanitize($e['title']) ?><?= $e['is_recurring'] ? ' <i class="fas fa-repeat text-muted ms-1" title="Recurring: ' . $e['recurrence_type'] . '"></i>' : '' ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($e['category']) ?></span></td>
                            <td><span class="badge bg-<?= status_badge_class($e['status']) ?>"><?= $e['status'] ?></span></td>
                            <td class="text-nowrap">
                                <?php if ($e['status'] !== 'Done'): ?>
                                <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="mark_done"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-xs btn-outline-success" title="Mark Done"><i class="fas fa-check"></i></button></form>
                                <?php endif; ?>
                                <a href="?edit=<?= $e['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this event?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
