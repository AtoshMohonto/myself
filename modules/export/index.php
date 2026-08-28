<?php
require_once __DIR__ . '/../../includes/functions.php';
check_login();
$uid = my_id();

$exports = [
    'schedule' => [
        'sql' => "SELECT * FROM schedule_events WHERE user_id = ? ORDER BY event_date DESC",
        'cols' => ['title' => 'Title', 'description' => 'Description', 'category' => 'Category', 'event_date' => 'Date', 'start_time' => 'Start', 'end_time' => 'End', 'is_recurring' => 'Recurring', 'recurrence_type' => 'Repeats', 'status' => 'Status'],
    ],
    'tasks' => [
        'sql' => "SELECT * FROM tasks WHERE user_id = ? ORDER BY (due_date IS NULL), due_date ASC",
        'cols' => ['title' => 'Title', 'description' => 'Description', 'category' => 'Category', 'priority' => 'Priority', 'due_date' => 'Due Date', 'status' => 'Status', 'completed_at' => 'Completed At'],
    ],
    'goals' => [
        'sql' => "SELECT * FROM goals WHERE user_id = ? ORDER BY (target_date IS NULL), target_date ASC",
        'cols' => ['title' => 'Title', 'description' => 'Description', 'category' => 'Category', 'priority' => 'Priority', 'target_date' => 'Target Date', 'progress' => 'Progress %', 'status' => 'Status'],
    ],
    'bucket_list' => [
        'sql' => "SELECT * FROM bucket_list WHERE user_id = ? ORDER BY (target_date IS NULL), target_date ASC",
        'cols' => ['title' => 'Title', 'description' => 'Description', 'category' => 'Category', 'priority' => 'Priority', 'status' => 'Status', 'target_date' => 'Target Date', 'completed_date' => 'Completed Date', 'notes' => 'Notes'],
    ],
    'dreams' => [
        'sql' => "SELECT * FROM dreams WHERE user_id = ? ORDER BY title ASC",
        'cols' => ['title' => 'Title', 'description' => 'Description', 'category' => 'Category', 'timeframe' => 'Timeframe', 'status' => 'Status', 'notes' => 'Notes'],
    ],
    'skills' => [
        'sql' => "SELECT * FROM skills WHERE user_id = ? ORDER BY type, name ASC",
        'cols' => ['name' => 'Skill', 'type' => 'Type', 'category' => 'Category', 'proficiency_level' => 'Proficiency', 'learning_status' => 'Status', 'resources' => 'Resources', 'notes' => 'Notes'],
    ],
    'work' => [
        'sql' => "SELECT * FROM work_schedules WHERE user_id = ? ORDER BY (schedule_date IS NULL), schedule_date DESC",
        'cols' => ['work_type' => 'Type', 'title' => 'Title', 'organization' => 'Organization', 'schedule_date' => 'Date', 'start_time' => 'Start', 'end_time' => 'End', 'is_recurring' => 'Recurring', 'recurrence_type' => 'Repeats', 'custom_days' => 'Custom Days', 'status' => 'Status', 'notes' => 'Notes'],
    ],
    'finance_accounts' => [
        'sql' => "SELECT * FROM finance_accounts WHERE user_id = ? ORDER BY name ASC",
        'cols' => ['name' => 'Name', 'type' => 'Type', 'opening_balance' => 'Opening Balance', 'balance' => 'Current Balance', 'notes' => 'Notes'],
    ],
    'finance_transactions' => [
        'sql' => "SELECT t.*, a.name AS account_name FROM finance_transactions t JOIN finance_accounts a ON t.account_id = a.id WHERE t.isDelete = 0 AND t.user_id = ? ORDER BY t.transaction_date DESC",
        'cols' => ['transaction_date' => 'Date', 'account_name' => 'Account', 'type' => 'Type', 'category' => 'Category', 'amount' => 'Amount', 'description' => 'Description'],
    ],
    'finance_reminders' => [
        'sql' => "SELECT * FROM finance_reminders WHERE user_id = ? ORDER BY due_date ASC",
        'cols' => ['title' => 'Title', 'type' => 'Type', 'category' => 'Category', 'amount' => 'Amount', 'due_date' => 'Due Date', 'status' => 'Status', 'notes' => 'Notes'],
    ],
    'health' => [
        'sql' => "SELECT * FROM health_records WHERE user_id = ? ORDER BY record_date DESC",
        'cols' => ['record_type' => 'Type', 'title' => 'Title', 'description' => 'Description', 'record_date' => 'Date', 'doctor_name' => 'Doctor/Facility', 'notes' => 'Notes'],
    ],
    'medicines' => [
        'sql' => "SELECT * FROM medicines WHERE user_id = ? ORDER BY name ASC",
        'cols' => ['name' => 'Name', 'dosage' => 'Dosage', 'form' => 'Form', 'stock_quantity' => 'Stock', 'unit' => 'Unit', 'frequency' => 'Frequency', 'schedule_times' => 'Schedule Times', 'start_date' => 'Start Date', 'end_date' => 'End Date', 'expiry_date' => 'Expiry Date', 'status' => 'Status', 'notes' => 'Notes'],
    ],
    'social_accounts' => [
        'sql' => "SELECT * FROM social_accounts WHERE user_id = ? ORDER BY platform ASC",
        'cols' => ['platform' => 'Platform', 'handle' => 'Handle', 'profile_url' => 'Profile URL', 'followers_count' => 'Followers', 'notes' => 'Notes'],
    ],
    'social_posts' => [
        'sql' => "SELECT p.*, a.platform, a.handle FROM social_posts p LEFT JOIN social_accounts a ON p.social_account_id = a.id WHERE p.isDelete = 0 AND p.user_id = ? ORDER BY (p.scheduled_date IS NULL), p.scheduled_date ASC",
        'cols' => ['title' => 'Title', 'content' => 'Content', 'post_type' => 'Type', 'platform' => 'Platform', 'handle' => 'Handle', 'scheduled_date' => 'Scheduled Date', 'status' => 'Status', 'notes' => 'Notes'],
    ],
    // Passwords are intentionally excluded — never export decrypted credentials to a plaintext file.
    'digital_locker' => [
        'sql' => "SELECT * FROM digital_locker WHERE user_id = ? ORDER BY category ASC, title ASC",
        'cols' => ['category' => 'Category', 'title' => 'Title', 'username' => 'Username', 'url' => 'Website', 'notes' => 'Notes'],
    ],
    'lend_borrow' => [
        'sql' => "SELECT * FROM lend_borrow WHERE user_id = ? ORDER BY date_given DESC",
        'cols' => ['direction' => 'Direction', 'item_type' => 'Type', 'person_name' => 'Person', 'person_contact' => 'Contact', 'description' => 'Description', 'amount' => 'Amount', 'is_returnable' => 'Must Return', 'date_given' => 'Date', 'due_date' => 'Return By', 'returned_date' => 'Returned On', 'status' => 'Status', 'notes' => 'Notes'],
    ],
];

$type = $_GET['type'] ?? '';
if (!isset($exports[$type])) {
    redirect('settings.php', 'Unknown export type.', 'danger');
}

$cfg = $exports[$type];
$rows = fetch_all($cfg['sql'], [$uid]);

$headers = array_values($cfg['cols']);
$outRows = array_map(function ($row) use ($cfg) {
    $out = [];
    foreach (array_keys($cfg['cols']) as $col) {
        $out[] = $row[$col] ?? '';
    }
    return $out;
}, $rows);

log_activity('Exported data: ' . $type);
output_csv($type . '_export_' . date('Y-m-d') . '.csv', $headers, $outRows);
