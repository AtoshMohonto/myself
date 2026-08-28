<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$page_title = 'Work Schedules';
$active_page = 'work';
$uid = my_id();

$weekday_names = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

function format_custom_days($custom_days) {
    if (!$custom_days) return '';
    $names = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
    $out = [];
    foreach (explode(',', $custom_days) as $d) {
        if (isset($names[(int) $d])) $out[] = $names[(int) $d];
    }
    return implode(', ', $out);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        redirect('modules/work/index.php', 'Invalid request.', 'danger');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        db_query("UPDATE work_schedules SET isDelete = 1 WHERE id = ? AND user_id = ?", [(int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/work/index.php', 'Entry deleted.', 'success');
    }

    if ($action === 'set_status') {
        db_query("UPDATE work_schedules SET status = ? WHERE id = ? AND user_id = ?", [$_POST['status'] ?? 'Upcoming', (int) ($_POST['id'] ?? 0), $uid]);
        redirect('modules/work/index.php', 'Updated.', 'success');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $work_type = $_POST['work_type'] ?? 'Official Job';
    $title = sanitize($_POST['title'] ?? '');
    $organization = sanitize($_POST['organization'] ?? '');
    $schedule_date = $_POST['schedule_date'] ?: null;
    $start_time = $_POST['start_time'] ?: null;
    $end_time = $_POST['end_time'] ?: null;
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_type = $is_recurring ? ($_POST['recurrence_type'] ?? 'Weekly') : 'None';
    $notes = sanitize($_POST['notes'] ?? '');

    $custom_days = '';
    if ($recurrence_type === 'Custom') {
        $days = [];
        foreach ((array) ($_POST['custom_days'] ?? []) as $d) {
            $d = (int) $d;
            if ($d >= 0 && $d <= 6 && !in_array($d, $days)) $days[] = $d;
        }
        sort($days);
        $custom_days = implode(',', $days);
    }

    if ($title === '') {
        redirect('modules/work/index.php', 'Title is required.', 'danger');
    }

    if ($id > 0) {
        db_query("UPDATE work_schedules SET work_type=?, title=?, organization=?, schedule_date=?, start_time=?, end_time=?, is_recurring=?, recurrence_type=?, custom_days=?, notes=? WHERE id=? AND user_id=?",
            [$work_type, $title, $organization, $schedule_date, $start_time, $end_time, $is_recurring, $recurrence_type, $custom_days, $notes, $id, $uid]);
        redirect('modules/work/index.php', 'Entry updated.', 'success');
    } else {
        db_query("INSERT INTO work_schedules (user_id, work_type, title, organization, schedule_date, start_time, end_time, is_recurring, recurrence_type, custom_days, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$uid, $work_type, $title, $organization, $schedule_date, $start_time, $end_time, $is_recurring, $recurrence_type, $custom_days, $notes]);
        log_activity('Added work schedule: ' . $title);
        redirect('modules/work/index.php', 'Entry added.', 'success');
    }
}

db_query("UPDATE work_schedules SET status = 'Missed' WHERE status = 'Upcoming' AND is_recurring = 0 AND schedule_date < CURDATE() AND isDelete = 0 AND user_id = ?", [$uid]);

$filter_type = $_GET['type'] ?? '';
$editWork = null;
if (isset($_GET['edit'])) {
    $editWork = fetch_one("SELECT * FROM work_schedules WHERE id = ? AND user_id = ?", [(int) $_GET['edit'], $uid]);
}
$sql = "SELECT * FROM work_schedules WHERE user_id = ?";
$params = [$uid];
if ($filter_type !== '') { $sql .= " AND work_type = ?"; $params[] = $filter_type; }
$sql .= " ORDER BY (schedule_date IS NULL), schedule_date DESC, start_time ASC";
$works = fetch_all($sql, $params);
$work_types = get_categories('work_type');
$form_work_types = category_options_with_current('work_type', $editWork['work_type'] ?? '');

include __DIR__ . '/../../templates/header.php';
include __DIR__ . '/../../templates/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fas fa-briefcase text-primary me-2"></i>Work Schedules</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3"><h6 class="m-0 fw-bold"><?= $editWork ? 'Edit Entry' : 'Add Work Entry' ?></h6></div>
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editWork['id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Work Type</label>
                    <select name="work_type" class="form-select">
                        <?php foreach ($form_work_types as $t): ?><option value="<?= sanitize($t) ?>" <?= ($editWork['work_type'] ?? '') === $t ? 'selected' : '' ?>><?= sanitize($t) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Client call, Math tuition" value="<?= sanitize($editWork['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Organization / Client</label>
                    <input type="text" name="organization" class="form-control" value="<?= sanitize($editWork['organization'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" name="schedule_date" class="form-control" value="<?= $editWork['schedule_date'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Start</label>
                    <input type="time" name="start_time" class="form-control" value="<?= $editWork['start_time'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">End</label>
                    <input type="time" name="end_time" class="form-control" value="<?= $editWork['end_time'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="workRecurring" name="is_recurring" value="1" <?= !empty($editWork['is_recurring']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="workRecurring">Recurring</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Repeats</label>
                    <select name="recurrence_type" id="workRecurrenceType" class="form-select">
                        <?php foreach (['Daily','Weekly','Monthly','Custom'] as $r): ?><option value="<?= $r ?>" <?= ($editWork['recurrence_type'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><?= $editWork ? 'Update' : 'Add' ?></button>
                </div>
                <div class="col-md-12" id="workCustomDays" style="display:none;">
                    <label class="form-label fw-bold d-block">Repeat on Days</label>
                    <?php
                    $saved_days = $editWork ? array_map('intval', array_filter(explode(',', $editWork['custom_days'] ?? ''))) : [];
                    foreach ($weekday_names as $d => $name):
                    ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="custom_days[]" id="wd<?= $d ?>" value="<?= $d ?>" <?= in_array($d, $saved_days, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="wd<?= $d ?>"><?= $name ?></label>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-text">Pick the weekdays this repeats on. Time is set via Start/End above.</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= sanitize($editWork['notes'] ?? '') ?>">
                </div>
            </div>
            <?php if ($editWork): ?><a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">Cancel Edit</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="m-0 fw-bold">All Work Entries</h6>
        <div class="btn-group btn-group-sm flex-wrap">
            <a href="?type=" class="btn btn-outline-secondary <?= $filter_type === '' ? 'active' : '' ?>">All</a>
            <?php foreach ($work_types as $t): ?>
                <a href="?type=<?= urlencode($t) ?>" class="btn btn-outline-secondary <?= $filter_type === $t ? 'active' : '' ?>"><?= sanitize($t) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Title</th><th>Type</th><th>Org/Client</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($works)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No work entries yet.</td></tr>
                    <?php else: foreach ($works as $w): ?>
                        <tr>
                            <td class="fw-bold"><?= sanitize($w['title']) ?><?php if ($w['is_recurring']): ?><i class="fas fa-repeat text-muted ms-1" title="<?= $w['recurrence_type'] ?><?= $w['recurrence_type'] === 'Custom' && $w['custom_days'] ? ': ' . format_custom_days($w['custom_days']) : '' ?>"></i><?php endif; ?></td>
                            <td><span class="badge bg-light text-dark"><?= sanitize($w['work_type']) ?></span></td>
                            <td><?= sanitize($w['organization'] ?? '-') ?></td>
                            <td>
                                <?php if ($w['recurrence_type'] === 'Custom' && $w['custom_days']): ?>
                                    <span class="badge bg-info-subtle text-dark"><?= format_custom_days($w['custom_days']) ?></span>
                                <?php else: ?>
                                    <?= $w['schedule_date'] ? format_date($w['schedule_date']) : '-' ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm"><?= $w['start_time'] ? format_time($w['start_time']) : '-' ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= $w['id'] ?>">
                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        <?php foreach (['Upcoming','Ongoing','Done','Missed'] as $s): ?><option value="<?= $s ?>" <?= $w['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-nowrap">
                                <a href="?edit=<?= $w['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fas fa-pen"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this entry?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $w['id'] ?>"><button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>
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
    var recurType = document.getElementById('workRecurrenceType');
    var customDays = document.getElementById('workCustomDays');
    function syncCustomDays() {
        var show = recurType.value === 'Custom';
        customDays.style.display = show ? '' : 'none';
    }
    if (recurType && customDays) {
        recurType.addEventListener('change', syncCustomDays);
        syncCustomDays();
    }
});
</script>
<?php include __DIR__ . '/../../templates/footer.php'; ?>
