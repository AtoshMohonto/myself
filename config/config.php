<?php
ob_start();
/**
 * MySelf — Personal Life OS — Global Configuration
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'myself_db');

// Application Settings
define('APP_NAME', 'MySelf');
define('BASE_URL', '/personal/myself/');

// Encryption key for sensitive data (Digital Locker passwords). Keep this secret;
// changing it will make previously stored locker passwords undecryptable.
define('ENCRYPTION_KEY', 'f607333c25d69908c5a8d0cf8866d507e4931fa342821cee4aefedd5eeb1da4b');

// Database Connection
function get_db_connection() {
    static $conn;
    if (!$conn) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Global database query function
function db_query($sql, $params = []) {
    $conn = get_db_connection();
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    if ($params) {
        $types = "";
        foreach ($params as $param) {
            if (is_int($param)) $types .= "i";
            elseif (is_double($param)) $types .= "d";
            else $types .= "s";
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
