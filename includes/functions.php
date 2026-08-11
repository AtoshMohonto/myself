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
