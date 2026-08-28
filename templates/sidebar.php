<?php if (!isset($active_page)) $active_page = ''; ?>
<div class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= BASE_URL ?>index.php">
        <div class="sidebar-brand-icon"><i class="fas fa-user-astronaut"></i></div>
        <div class="sidebar-brand-text">MySelf</div>
    </a>
    <hr class="sidebar-divider my-0">

    <div class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>index.php"><i class="fas fa-fw fa-gauge-high"></i><span>Dashboard</span></a>
    </div>

    <div class="sidebar-heading">Plan &amp; Do</div>
    <div class="nav-item <?= $active_page === 'schedule' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/schedule/index.php"><i class="fas fa-fw fa-calendar-days"></i><span>Schedule</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'tasks' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/tasks/index.php"><i class="fas fa-fw fa-list-check"></i><span>Tasks</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'goals' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/goals/index.php"><i class="fas fa-fw fa-bullseye"></i><span>Goals</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'bucket_list' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/bucket_list/index.php"><i class="fas fa-fw fa-list-ol"></i><span>Bucket List</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'dreams' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/dreams/index.php"><i class="fas fa-fw fa-cloud-moon"></i><span>Dreams</span></a>
    </div>

    <div class="sidebar-heading">Grow</div>
    <div class="nav-item <?= $active_page === 'skills' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/skills/index.php"><i class="fas fa-fw fa-brain"></i><span>Skills</span></a>
    </div>

    <div class="sidebar-heading">Work &amp; Money</div>
    <div class="nav-item <?= $active_page === 'work' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/work/index.php"><i class="fas fa-fw fa-briefcase"></i><span>Work Schedules</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'finance' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/finance/index.php"><i class="fas fa-fw fa-wallet"></i><span>Finance</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'finance_reminders' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/finance/reminders.php"><i class="fas fa-fw fa-bell"></i><span>Payments &amp; Reminders</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'lend_borrow' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/lend_borrow/index.php"><i class="fas fa-fw fa-handshake"></i><span>Lend &amp; Borrow</span></a>
    </div>

    <div class="sidebar-heading">Wellbeing</div>
    <div class="nav-item <?= $active_page === 'health' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/health/index.php"><i class="fas fa-fw fa-heart-pulse"></i><span>Health Records</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'medicines' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/medicines/index.php"><i class="fas fa-fw fa-pills"></i><span>Medicines</span></a>
    </div>

    <div class="sidebar-heading">Online</div>
    <div class="nav-item <?= $active_page === 'social' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/social/index.php"><i class="fas fa-fw fa-hashtag"></i><span>Social Media</span></a>
    </div>

    <div class="sidebar-heading">Security</div>
    <div class="nav-item <?= $active_page === 'digital_locker' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/digital_locker/index.php"><i class="fas fa-fw fa-shield-halved"></i><span>Digital Locker</span></a>
    </div>

    <?php if (is_admin()): ?>
    <div class="sidebar-heading">Admin</div>
    <div class="nav-item <?= $active_page === 'admin_users' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/admin/users.php"><i class="fas fa-fw fa-users-gear"></i><span>Manage Users</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'admin_content' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>modules/admin/content.php"><i class="fas fa-fw fa-bullhorn"></i><span>Site Content</span></a>
    </div>
    <?php endif; ?>

    <div class="sidebar-heading">Account</div>
    <div class="nav-item <?= $active_page === 'profile' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>profile.php"><i class="fas fa-fw fa-user-gear"></i><span>Profile</span></a>
    </div>
    <div class="nav-item <?= $active_page === 'settings' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>settings.php"><i class="fas fa-fw fa-gear"></i><span>Settings</span></a>
    </div>
    <div class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>modules/auth/logout.php" data-confirm="Log out?"><i class="fas fa-fw fa-right-from-bracket"></i><span>Logout</span></a>
    </div>

    <hr class="sidebar-divider d-none d-md-block">
    <div class="text-center d-none d-md-block py-2">
        <button class="btn btn-sm btn-outline-light rounded-circle" id="sidebarToggle" style="width:32px;height:32px;" title="Toggle Sidebar"><i class="fas fa-angle-left"></i></button>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div id="content-wrapper" class="d-flex flex-column">
<div id="content">
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-0 static-top shadow-sm">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle me-1"><i class="fas fa-bars"></i></button>
    <span class="fw-bold text-dark d-none d-md-inline ms-2"><?= isset($page_title) ? sanitize($page_title) : 'MySelf' ?></span>
    <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                <span class="me-2 d-none d-lg-inline text-gray-600 small fw-bold"><?= sanitize(current_user()['full_name'] ?? 'Me') ?></span>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    <span class="text-white fw-bold" style="font-size:0.75rem;"><?= strtoupper(substr(current_user()['full_name'] ?? 'M', 0, 1)) ?></span>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-end shadow">
                <a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>Profile</a>
                <a class="dropdown-item" href="<?= BASE_URL ?>change_password.php"><i class="fas fa-lock fa-sm fa-fw me-2 text-gray-400"></i>Change Password</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>modules/auth/logout.php" data-confirm="Log out?"><i class="fas fa-sign-out-alt fa-sm fa-fw me-2"></i>Logout</a>
            </div>
        </li>
    </ul>
</nav>
<div class="container-fluid py-4">
<?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'success' ?> alert-dismissible fade show" role="alert">
        <?= sanitize($_SESSION['flash_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>
