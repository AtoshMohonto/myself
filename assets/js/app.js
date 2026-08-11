function isMobile() { return window.innerWidth < 768; }

function openSidebar() {
    document.body.classList.remove('sidebar-hidden');
    document.body.classList.add('sidebar-open');
}
function closeSidebar() {
    document.body.classList.add('sidebar-hidden');
    document.body.classList.remove('sidebar-open');
}
function toggleSidebar() {
    if (isMobile()) {
        if (document.body.classList.contains('sidebar-hidden')) { openSidebar(); } else { closeSidebar(); }
    } else {
        document.body.classList.toggle('sidebar-hidden');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="date"]').forEach(function (e) {
        if (!e.value && e.dataset.autoToday === '1') e.value = new Date().toISOString().split('T')[0];
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });

    var topToggle = document.getElementById('sidebarToggleTop');
    if (topToggle) topToggle.addEventListener('click', function (e) { e.preventDefault(); toggleSidebar(); });

    var sideToggle = document.getElementById('sidebarToggle');
    if (sideToggle) sideToggle.addEventListener('click', toggleSidebar);

    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.addEventListener('click', closeSidebar);

    if (isMobile()) closeSidebar();
});

window.addEventListener('resize', function () {
    if (!isMobile()) {
        document.body.classList.remove('sidebar-open');
        var overlay = document.getElementById('sidebarOverlay');
        if (overlay) overlay.style.display = 'none';
    }
});
