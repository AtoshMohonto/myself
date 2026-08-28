<?php
/**
 * MySelf — Database Installer
 * Visit this once to create the database, tables, and your owner account.
 * Safe to re-run: uses CREATE TABLE IF NOT EXISTS and never drops data.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('SETUP_PIN', '2468');

$pinOk = isset($_GET['pin']) && $_GET['pin'] === SETUP_PIN;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MySelf — Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#0f172a;color:#e2e8f0;} .card{background:#1e293b;border:1px solid #334155;} .form-control{background:#0f172a;color:#fff;border-color:#334155;}</style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div class="card p-4 shadow" style="max-width:520px;width:100%;">
<h3 class="mb-3"><i class="fas fa-user"></i> MySelf — Setup</h3>

<?php if (!$pinOk): ?>
    <p class="text-muted">Enter the setup PIN to install/update the database.</p>
    <form method="get" class="d-flex gap-2">
        <input type="password" name="pin" class="form-control" placeholder="Setup PIN" required autofocus>
        <button class="btn btn-primary">Go</button>
    </form>
<?php else:
    $host = 'localhost'; $user = 'root'; $pass = ''; $dbName = 'myself_db';
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) { die('<div class="alert alert-danger">Connection failed: ' . $conn->connect_error . '</div>'); }

    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbName);
    echo '<div class="alert alert-success">Database ready.</div>';

    $tables = [
    "users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(150) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('Admin','User') NOT NULL DEFAULT 'User',
        is_active TINYINT(1) NOT NULL DEFAULT 0,
        photo VARCHAR(255) DEFAULT NULL,
        timezone VARCHAR(50) DEFAULT 'Asia/Dhaka',
        currency_symbol VARCHAR(10) NOT NULL DEFAULT '৳',
        lock_digital_locker TINYINT(1) NOT NULL DEFAULT 0,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        isDelete TINYINT(1) DEFAULT 0
    )",
    "activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT 0,
        action VARCHAR(150),
        entity_type VARCHAR(50),
        entity_id INT DEFAULT 0,
        details TEXT,
        ip_address VARCHAR(50),
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "schedule_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category VARCHAR(50) DEFAULT 'Personal',
        event_date DATE NOT NULL,
        start_time TIME DEFAULT NULL,
        end_time TIME DEFAULT NULL,
        is_recurring TINYINT(1) DEFAULT 0,
        recurrence_type ENUM('None','Daily','Weekly','Monthly') DEFAULT 'None',
        status ENUM('Upcoming','Done','Missed') DEFAULT 'Upcoming',
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category VARCHAR(50) DEFAULT 'General',
        priority ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
        due_date DATE DEFAULT NULL,
        status ENUM('Pending','In Progress','Done','Cancelled') DEFAULT 'Pending',
        completed_at DATETIME DEFAULT NULL,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category VARCHAR(50) DEFAULT 'Personal',
        target_date DATE DEFAULT NULL,
        progress TINYINT DEFAULT 0,
        status ENUM('Active','Achieved','Abandoned') DEFAULT 'Active',
        priority ENUM('Low','Medium','High') DEFAULT 'Medium',
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "bucket_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category VARCHAR(50) DEFAULT 'General',
        priority ENUM('Low','Medium','High') DEFAULT 'Medium',
        status ENUM('Not Started','In Progress','Done') DEFAULT 'Not Started',
        target_date DATE DEFAULT NULL,
        completed_date DATE DEFAULT NULL,
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "dreams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category VARCHAR(50) DEFAULT 'General',
        timeframe ENUM('Short-term','Long-term','Someday') DEFAULT 'Someday',
        status ENUM('Active','Achieved','Faded') DEFAULT 'Active',
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        type ENUM('Hard Skill','Soft Skill') DEFAULT 'Hard Skill',
        category VARCHAR(50) DEFAULT 'General',
        proficiency_level ENUM('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Beginner',
        learning_status ENUM('Learning','Proficient','Mastered') DEFAULT 'Learning',
        resources TEXT,
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "work_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        work_type VARCHAR(50) DEFAULT 'Official Job',
        title VARCHAR(200) NOT NULL,
        organization VARCHAR(150) DEFAULT NULL,
        schedule_date DATE DEFAULT NULL,
        start_time TIME DEFAULT NULL,
        end_time TIME DEFAULT NULL,
        is_recurring TINYINT(1) DEFAULT 0,
        recurrence_type ENUM('None','Daily','Weekly','Monthly') DEFAULT 'None',
        status ENUM('Upcoming','Done','Missed','Ongoing') DEFAULT 'Upcoming',
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "finance_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(50) DEFAULT 'Cash',
        opening_balance DECIMAL(15,2) DEFAULT 0,
        balance DECIMAL(15,2) DEFAULT 0,
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "finance_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        account_id INT NOT NULL,
        type ENUM('Income','Expense') DEFAULT 'Expense',
        category VARCHAR(80) DEFAULT 'General',
        amount DECIMAL(15,2) NOT NULL,
        transaction_date DATE NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "health_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        record_type VARCHAR(50) DEFAULT 'Checkup',
        title VARCHAR(200) NOT NULL,
        description TEXT,
        record_date DATE NOT NULL,
        doctor_name VARCHAR(100) DEFAULT NULL,
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        dosage VARCHAR(80) DEFAULT NULL,
        form VARCHAR(50) DEFAULT 'Tablet',
        stock_quantity INT DEFAULT 0,
        unit VARCHAR(30) DEFAULT 'pcs',
        frequency VARCHAR(100) DEFAULT NULL,
        schedule_times VARCHAR(150) DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        status ENUM('Active','Completed','Stopped') DEFAULT 'Active',
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "social_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        platform VARCHAR(50) DEFAULT 'Facebook',
        handle VARCHAR(100) DEFAULT NULL,
        profile_url VARCHAR(255) DEFAULT NULL,
        followers_count INT DEFAULT 0,
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "social_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        social_account_id INT DEFAULT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT,
        post_type VARCHAR(50) DEFAULT 'Post',
        scheduled_date DATE DEFAULT NULL,
        status ENUM('Planned','Scheduled','Posted','Cancelled') DEFAULT 'Planned',
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "finance_reminders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        type ENUM('Income','Expense') DEFAULT 'Expense',
        category VARCHAR(50) DEFAULT 'Other',
        amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        due_date DATE NOT NULL,
        status ENUM('Upcoming','Paid','Overdue') DEFAULT 'Upcoming',
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "digital_locker (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(50) DEFAULT 'Other',
        title VARCHAR(150) NOT NULL,
        username VARCHAR(150) DEFAULT NULL,
        password TEXT NOT NULL,
        url VARCHAR(255) DEFAULT NULL,
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module VARCHAR(40) NOT NULL,
        name VARCHAR(100) NOT NULL,
        sort_order INT DEFAULT 0,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "lend_borrow (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        direction ENUM('Lent','Borrowed') NOT NULL DEFAULT 'Lent',
        item_type ENUM('Money','Item') NOT NULL DEFAULT 'Money',
        person_name VARCHAR(150) NOT NULL,
        person_contact VARCHAR(100) DEFAULT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(15,2) DEFAULT NULL,
        is_returnable TINYINT(1) NOT NULL DEFAULT 1,
        date_given DATE NOT NULL,
        due_date DATE DEFAULT NULL,
        returned_date DATE DEFAULT NULL,
        status ENUM('Pending','Returned','Overdue','Written Off') NOT NULL DEFAULT 'Pending',
        notes TEXT,
        isDelete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    ];

    foreach ($tables as $ddl) {
        $tableName = trim(explode(' ', trim($ddl))[0]);
        if ($conn->query("CREATE TABLE IF NOT EXISTS $ddl") === true) {
            echo '<div class="small text-success">✓ table `' . htmlspecialchars($tableName) . '` ready</div>';
        } else {
            echo '<div class="small text-danger">✗ ' . htmlspecialchars($tableName) . ': ' . $conn->error . '</div>';
        }
    }

    // --- Migrations for existing tables (safe to re-run) ---
    // Work schedules: add Custom recurrence option (choose your own weekdays + time)
    $conn->query("ALTER TABLE work_schedules MODIFY COLUMN recurrence_type ENUM('None','Daily','Weekly','Monthly','Custom') DEFAULT 'None'");
    $colRes = $conn->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_schedules' AND COLUMN_NAME = 'custom_days'");
    $colRow = $colRes->fetch_assoc();
    if ((int) ($colRow['c'] ?? 0) === 0) {
        $conn->query("ALTER TABLE work_schedules ADD COLUMN custom_days VARCHAR(20) DEFAULT NULL AFTER recurrence_type");
        echo '<div class="small text-success">✓ work_schedules.custom_days added (Custom repeat days)</div>';
    }

    // Users: Digital Locker re-auth preference
    $colRes2 = $conn->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'lock_digital_locker'");
    $colRow2 = $colRes2->fetch_assoc();
    if ((int) ($colRow2['c'] ?? 0) === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN lock_digital_locker TINYINT(1) NOT NULL DEFAULT 0 AFTER currency_symbol");
        echo '<div class="small text-success">✓ users.lock_digital_locker added (Digital Locker re-auth)</div>';
    }

    // Categories: user-manageable category/type lists (Settings > Categories).
    // Convert the columns that used to be fixed ENUMs to plain VARCHAR so any
    // custom category name can be stored. Existing values are preserved as-is.
    $conn->query("ALTER TABLE schedule_events MODIFY COLUMN category VARCHAR(50) DEFAULT 'Personal'");
    $conn->query("ALTER TABLE goals MODIFY COLUMN category VARCHAR(50) DEFAULT 'Personal'");
    $conn->query("ALTER TABLE work_schedules MODIFY COLUMN work_type VARCHAR(50) DEFAULT 'Official Job'");
    $conn->query("ALTER TABLE health_records MODIFY COLUMN record_type VARCHAR(50) DEFAULT 'Checkup'");
    $conn->query("ALTER TABLE medicines MODIFY COLUMN form VARCHAR(50) DEFAULT 'Tablet'");
    $conn->query("ALTER TABLE social_accounts MODIFY COLUMN platform VARCHAR(50) DEFAULT 'Facebook'");
    $conn->query("ALTER TABLE social_posts MODIFY COLUMN post_type VARCHAR(50) DEFAULT 'Post'");
    $conn->query("ALTER TABLE finance_accounts MODIFY COLUMN type VARCHAR(50) DEFAULT 'Cash'");
    $conn->query("ALTER TABLE digital_locker MODIFY COLUMN category VARCHAR(50) DEFAULT 'Other'");
    echo '<div class="small text-success">✓ category columns converted to editable lists</div>';

    // Seed owner account — first user is always an active Admin
    $ownerCheck = $conn->query("SELECT id FROM users LIMIT 1");
    $ownerId = null;
    if ($ownerCheck && $ownerCheck->num_rows === 0) {
        $hash = password_hash('myself123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, is_active) VALUES (?, ?, ?, 'Admin', 1)");
        $fullName = 'Myself'; $username = 'me';
        $stmt->bind_param('sss', $fullName, $username, $hash);
        $stmt->execute();
        $ownerId = $conn->insert_id;
        echo '<div class="alert alert-success mt-3">Owner account created — username: <strong>me</strong> / password: <strong>myself123</strong>. Change this after logging in.</div>';
    }
    if ($ownerId === null) {
        $row = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch_assoc();
        $ownerId = (int) ($row['id'] ?? 1);
    }

    // Seed default public-site content settings (admin-controlled, shared across all visitors)
    $defaults = [
        'hero_title' => 'Track your whole life in one place',
        'hero_subtitle' => 'Schedule, tasks, goals, bucket list, dreams, skills, work, finances, health, medicines, and social media — one dashboard for everything that makes up your life.',
        'notice_active' => '0',
    ];
    foreach ($defaults as $k => $v) {
        $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param('ss', $k, $v);
        $stmt->execute();
    }

    // Seed one default finance account for the owner so the finance module isn't empty
    $accCheck = $conn->query("SELECT id FROM finance_accounts WHERE user_id = $ownerId LIMIT 1");
    if ($accCheck && $accCheck->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO finance_accounts (user_id, name, type, opening_balance, balance) VALUES (?, 'Cash Wallet', 'Cash', 0, 0)");
        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
    }

    $conn->close();
    echo '<hr><a href="' . 'modules/auth/login.php' . '" class="btn btn-primary w-100">Go to Login</a>';
endif; ?>
</div>
</body>
</html>
