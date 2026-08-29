<?php
if (!isset($active_page)) $active_page = '';

/**
 * Sidebar nav, grouped into collapsible accordion sections. The group
 * containing the current $active_page is auto-expanded on load; the rest
 * start collapsed so the list stays short instead of requiring a long
 * scroll through every module up front.
 */
$sidebarGroups = [
    'plan' => ['label' => 'Plan', 'items' => [
        ['key' => 'goals', 'url' => 'modules/goals/index.php', 'icon' => 'bullseye', 'label' => 'Goals'],
        ['key' => 'bucket_list', 'url' => 'modules/bucket_list/index.php', 'icon' => 'list-ol', 'label' => 'Bucket List'],
        ['key' => 'dreams', 'url' => 'modules/dreams/index.php', 'icon' => 'cloud-moon', 'label' => 'Dreams'],
        ['key' => 'notes', 'url' => 'modules/notes/index.php', 'icon' => 'note-sticky', 'label' => 'Notes'],
    ]],
    'do' => ['label' => 'Do', 'items' => [
        ['key' => 'schedule', 'url' => 'modules/schedule/index.php', 'icon' => 'calendar-days', 'label' => 'Schedule'],
        ['key' => 'tasks', 'url' => 'modules/tasks/index.php', 'icon' => 'list-check', 'label' => 'Tasks'],
        ['key' => 'todo', 'url' => 'modules/todo/index.php', 'icon' => 'square-check', 'label' => 'To-Do'],
    ]],
    'grow' => ['label' => 'Grow', 'items' => [
        ['key' => 'skills', 'url' => 'modules/skills/index.php', 'icon' => 'brain', 'label' => 'Skills'],
    ]],
    'work_money' => ['label' => 'Work &amp; Money', 'items' => [
        ['key' => 'work', 'url' => 'modules/work/index.php', 'icon' => 'briefcase', 'label' => 'Work Schedules'],
        ['key' => 'finance', 'url' => 'modules/finance/index.php', 'icon' => 'wallet', 'label' => 'Finance'],
        ['key' => 'finance_reminders', 'url' => 'modules/finance/reminders.php', 'icon' => 'bell', 'label' => 'Payments &amp; Reminders'],
        ['key' => 'lend_borrow', 'url' => 'modules/lend_borrow/index.php', 'icon' => 'handshake', 'label' => 'Lend &amp; Borrow'],
    ]],
    'wellbeing' => ['label' => 'Wellbeing', 'items' => [
        ['key' => 'health', 'url' => 'modules/health/index.php', 'icon' => 'heart-pulse', 'label' => 'Health Records'],
        ['key' => 'medicines', 'url' => 'modules/medicines/index.php', 'icon' => 'pills', 'label' => 'Medicines'],
    ]],
    'online' => ['label' => 'Online', 'items' => [
        ['key' => 'social', 'url' => 'modules/social/index.php', 'icon' => 'hashtag', 'label' => 'Social Media'],
    ]],
    'security' => ['label' => 'Security', 'items' => [
        ['key' => 'digital_locker', 'url' => 'modules/digital_locker/index.php', 'icon' => 'shield-halved', 'label' => 'Digital Locker'],
    ]],
];

if (is_admin()) {
    $sidebarGroups['admin'] = ['label' => 'Admin', 'items' => [
        ['key' => 'admin_users', 'url' => 'modules/admin/users.php', 'icon' => 'users-gear', 'label' => 'Manage Users'],
        ['key' => 'admin_content', 'url' => 'modules/admin/content.php', 'icon' => 'bullhorn', 'label' => 'Site Content'],
    ]];
}

$sidebarGroups['account'] = ['label' => 'Account', 'items' => [
    ['key' => 'profile', 'url' => 'profile.php', 'icon' => 'user-gear', 'label' => 'Profile'],
    ['key' => 'settings', 'url' => 'settings.php', 'icon' => 'gear', 'label' => 'Settings'],
    ['key' => '', 'url' => 'modules/auth/logout.php', 'icon' => 'right-from-bracket', 'label' => 'Logout', 'confirm' => 'Log out?'],
]];
?>
<div class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= BASE_URL ?>index.php">
        <div class="sidebar-brand-icon"><i class="fas fa-user-astronaut"></i></div>
        <div class="sidebar-brand-text">MySelf</div>
    </a>
    <hr class="sidebar-divider my-0">

    <div class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
        <a class="nav-link" href="<?= BASE_URL ?>index.php"><i class="fas fa-fw fa-gauge-high"></i><span>Dashboard</span></a>
    </div>

    <div class="accordion sidebar-accordion" id="sidebarAccordion">
        <?php foreach ($sidebarGroups as $groupKey => $group):
            $groupId = 'sidebarGroup-' . $groupKey;
            $hasActive = false;
            foreach ($group['items'] as $it) { if ($it['key'] !== '' && $it['key'] === $active_page) { $hasActive = true; break; } }
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button sidebar-heading-btn <?= $hasActive ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $groupId ?>" aria-expanded="<?= $hasActive ? 'true' : 'false' ?>" aria-controls="<?= $groupId ?>">
                    <?= $group['label'] ?>
                </button>
            </h2>
            <div id="<?= $groupId ?>" class="accordion-collapse collapse <?= $hasActive ? 'show' : '' ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <?php foreach ($group['items'] as $item): ?>
                    <div class="nav-item <?= $item['key'] !== '' && $active_page === $item['key'] ? 'active' : '' ?>">
                        <a class="nav-link" href="<?= BASE_URL . $item['url'] ?>"<?= isset($item['confirm']) ? ' data-confirm="' . sanitize($item['confirm']) . '"' : '' ?>><i class="fas fa-fw fa-<?= $item['icon'] ?>"></i><span><?= sanitize($item['label']) ?></span></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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
