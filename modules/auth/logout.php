<?php
require_once __DIR__ . '/../../includes/functions.php';
if (isset($_SESSION['user_id'])) {
    log_activity('Logged out');
}
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . 'modules/auth/login.php');
exit;
