<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * CSRF Protection
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token() {
    return $_SESSION['csrf_token'];
}

function validate_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . get_csrf_token() . '">';
}

/**
 * Log user activity
 */
function log_activity($action, $entity_type = '', $entity_id = 0, $details = '') {
    db_query("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
        [$_SESSION['user_id'] ?? 0, $action, $entity_type, $entity_id, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

/**
 * Redirect with flash message
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "modules/auth/login.php");
        exit;
    }
}

/**
 * Check if current user is an Admin; redirect home otherwise
 */
function is_admin() {
    return ($_SESSION['role'] ?? '') === 'Admin';
}

function require_admin() {
    check_login();
    if (!is_admin()) {
        redirect('index.php', 'Admins only.', 'danger');
    }
}

/**
 * Current logged-in user's id, for scoping every query to their own data
 */
function my_id() {
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Global SELECT filter for isDelete = 0
 */
function fetch_all($sql, $params = []) {
    $has_join = stripos($sql, 'JOIN') !== false;
    $skip_filter = stripos($sql, 'SKIP_ISDELETE_FILTER') !== false || stripos($sql, 'isDelete') !== false || $has_join;
    if (stripos($sql, 'SKIP_ISDELETE_FILTER') !== false) {
        $sql = str_ireplace('SKIP_ISDELETE_FILTER', '', $sql);
    }
    if (!$skip_filter && stripos($sql, 'SELECT') === 0) {
        if (stripos($sql, 'WHERE') === false) {
            $insert_at = strlen($sql);
            if (preg_match('/\b(ORDER BY|GROUP BY|LIMIT)\b/i', $sql, $matches, PREG_OFFSET_CAPTURE)) {
                $insert_at = $matches[0][1];
            }
            $sql = substr_replace($sql, " WHERE isDelete = 0 ", $insert_at, 0);
        } else {
            $sql = preg_replace('/\bWHERE\b/i', 'WHERE isDelete = 0 AND ', $sql, 1);
        }
    }
    $stmt = db_query($sql, $params);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

/**
 * Fetch a single row
 */
function fetch_one($sql, $params = []) {
    $data = fetch_all($sql, $params);
    return $data ? $data[0] : null;
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data ?? '')));
}

/**
 * Format currency using the logged-in user's own currency symbol preference
 */
function format_currency($amount) {
    $user = current_user();
    $symbol = $user['currency_symbol'] ?? '৳';
    return $symbol . ' ' . number_format((float) $amount, 2);
}

/**
 * Format date / datetime
 */
function format_date($date, $format = 'd M Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function format_time($time) {
    if (!$time) return '';
    return date('h:i A', strtotime($time));
}

function format_datetime($datetime) {
    if (!$datetime) return '';
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Settings (key/value)
 */
function get_setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (fetch_all("SELECT setting_key, setting_value FROM settings") as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function save_setting($key, $value) {
    db_query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$key, $value]);
}

/**
 * Current logged-in user
 */
function current_user() {
    static $user = null;
    if ($user === null && isset($_SESSION['user_id'])) {
        $user = fetch_one("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    return $user;
}

/**
 * Days left until a date (negative = overdue)
 */
function days_until($date) {
    if (!$date) return null;
    $today = new DateTime(date('Y-m-d'));
    $target = new DateTime(date('Y-m-d', strtotime($date)));
    return (int) $today->diff($target)->format('%R%a');
}

/**
 * Output CSV for download
 */
function output_csv($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, array_values((array) $row));
    fclose($out);
    exit;
}

/**
 * Encrypt / decrypt sensitive text (Digital Locker passwords) using AES-256-CBC
 * with a random IV per value, stored as base64(iv . ciphertext).
 */
function encrypt_data($plaintext) {
    if ($plaintext === null || $plaintext === '') return '';
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function decrypt_data($encoded) {
    if (!$encoded) return '';
    $data = base64_decode($encoded, true);
    if ($data === false || strlen($data) < 17) return '';
    $iv = substr($data, 0, 16);
    $cipher = substr($data, 16);
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}

/**
 * Badge color helper for status pills
 */
function status_badge_class($status) {
    $map = [
        'Done' => 'success', 'Completed' => 'success', 'Achieved' => 'success', 'Active' => 'success', 'Posted' => 'success', 'Mastered' => 'success',
        'In Progress' => 'primary', 'Upcoming' => 'primary', 'Scheduled' => 'primary', 'Learning' => 'primary', 'Proficient' => 'primary',
        'Pending' => 'warning', 'Not Started' => 'secondary', 'Planned' => 'secondary', 'Someday' => 'secondary',
        'Overdue' => 'danger', 'Missed' => 'danger', 'Cancelled' => 'danger', 'Abandoned' => 'danger', 'Faded' => 'danger', 'Stopped' => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}

/**
 * Central registry of every user-manageable category/type list in the app, and
 * which table(s)+column a rename should cascade into. Deliberately limited to
 * organizational/classification fields (category, type, platform, form) —
 * workflow state fields (status, priority, recurrence) are excluded since app
 * logic depends on their exact values.
 */
function category_module_map() {
    return [
        'schedule' => ['label' => 'Schedule Categories', 'targets' => [['schedule_events', 'category']],
            'defaults' => ['Personal', 'Work', 'Health', 'Study', 'Social', 'Other']],
        'tasks' => ['label' => 'Task Categories', 'targets' => [['tasks', 'category']],
            'defaults' => ['General']],
        'goals' => ['label' => 'Goal Categories', 'targets' => [['goals', 'category']],
            'defaults' => ['Career', 'Financial', 'Health', 'Education', 'Personal', 'Relationship', 'Spiritual', 'Other']],
        'bucket_list' => ['label' => 'Bucket List Categories', 'targets' => [['bucket_list', 'category']],
            'defaults' => ['General']],
        'dreams' => ['label' => 'Dream Categories', 'targets' => [['dreams', 'category']],
            'defaults' => ['General']],
        'skills' => ['label' => 'Skill Categories', 'targets' => [['skills', 'category']],
            'defaults' => ['General']],
        'work_type' => ['label' => 'Work Types', 'targets' => [['work_schedules', 'work_type']],
            'defaults' => ['Official Job', 'Remote Job', 'Passive Income', 'Tuition', 'Student Consultation', 'Freelance', 'Other']],
        'health_type' => ['label' => 'Health Record Types', 'targets' => [['health_records', 'record_type']],
            'defaults' => ['Checkup', 'Condition', 'Allergy', 'Vaccination', 'Lab Result', 'Vital', 'Other']],
        'medicine_form' => ['label' => 'Medicine Forms', 'targets' => [['medicines', 'form']],
            'defaults' => ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Drops', 'Ointment', 'Other']],
        'social_platform' => ['label' => 'Social Platforms', 'targets' => [['social_accounts', 'platform']],
            'defaults' => ['Facebook', 'Instagram', 'YouTube', 'LinkedIn', 'X (Twitter)', 'TikTok', 'Blog', 'Other']],
        'social_post_type' => ['label' => 'Post Types', 'targets' => [['social_posts', 'post_type']],
            'defaults' => ['Post', 'Reel', 'Story', 'Video', 'Article', 'Other']],
        'finance_account_type' => ['label' => 'Finance Account Types', 'targets' => [['finance_accounts', 'type']],
            'defaults' => ['Bank', 'Cash', 'Mobile Wallet', 'Savings', 'Investment', 'Other']],
        'finance_category' => ['label' => 'Finance Categories', 'targets' => [['finance_transactions', 'category'], ['finance_reminders', 'category']],
            'defaults' => ['Salary', 'Tuition', 'Lend Given', 'Lend Received', 'Rent', 'Utility', 'Insurance', 'Other']],
        'digital_locker_category' => ['label' => 'Digital Locker Categories', 'targets' => [['digital_locker', 'category']],
            'defaults' => ['Social Media', 'Email', 'WiFi', 'App', 'Bank', 'Other']],
        'digital_locker_role' => ['label' => 'Digital Locker Roles', 'targets' => [['digital_locker', 'role']],
            'defaults' => ['Owner', 'Admin', 'Editor', 'Viewer', 'Member', 'Other']],
        'notes' => ['label' => 'Note Categories', 'targets' => [['notes', 'category']],
            'defaults' => ['General', 'Idea', 'Journal', 'Reference', 'Other']],
    ];
}

/**
 * The current user's category list for a module, seeding the defaults on first use.
 */
function get_categories($module) {
    $map = category_module_map();
    $defaults = $map[$module]['defaults'] ?? [];
    $uid = my_id();
    $rows = fetch_all("SELECT name FROM categories WHERE user_id = ? AND module = ? ORDER BY sort_order ASC, name ASC", [$uid, $module]);
    if (empty($rows)) {
        foreach ($defaults as $i => $name) {
            db_query("INSERT INTO categories (user_id, module, name, sort_order) VALUES (?,?,?,?)", [$uid, $module, $name, $i]);
        }
        return $defaults;
    }
    return array_column($rows, 'name');
}

/**
 * A module's category list with the current record's existing value included,
 * even if it's since been removed from the managed list — so editing an older
 * record never silently swaps its category out from under it.
 */
function category_options_with_current($module, $currentValue) {
    $options = get_categories($module);
    if ($currentValue && !in_array($currentValue, $options, true)) {
        $options[] = $currentValue;
    }
    return $options;
}
