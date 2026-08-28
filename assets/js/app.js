// Keep this in sync with the CSS off-canvas breakpoint (max-width: 991.98px in style.css).
function isMobile() { return window.innerWidth < 992; }

function setToggleIcon(collapsedOrClosed) {
    document.querySelectorAll('#sidebarToggleTop i, #sidebarToggle i').forEach(function (icon) {
        icon.className = collapsedOrClosed ? 'fas fa-bars' : 'fas fa-angle-left';
    });
}

function openSidebar() {
    document.body.classList.remove('sidebar-hidden');
    document.body.classList.add('sidebar-open');
    setToggleIcon(false);
}
function closeSidebar() {
    document.body.classList.add('sidebar-hidden');
    document.body.classList.remove('sidebar-open');
    setToggleIcon(true);
}
function toggleSidebar() {
    if (isMobile()) {
        if (document.body.classList.contains('sidebar-hidden')) { openSidebar(); } else { closeSidebar(); }
    } else {
        var hidden = document.body.classList.toggle('sidebar-hidden');
        setToggleIcon(hidden);
        try { localStorage.setItem('sidebarCollapsed', hidden ? '1' : '0'); } catch (e) {}
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

    if (isMobile()) {
        closeSidebar();
    } else {
        setToggleIcon(document.body.classList.contains('sidebar-hidden'));
    }
});

window.addEventListener('resize', function () {
    if (!isMobile()) {
        // Leaving mobile width: drop the mobile "open" state so the overlay's
        // CSS rule (body.sidebar-open .sidebar-overlay) turns it off on its own.
        // (Do not set overlay.style.display directly — an inline style would
        // permanently win over that CSS rule and the overlay would never show again.)
        document.body.classList.remove('sidebar-open');
        setToggleIcon(document.body.classList.contains('sidebar-hidden'));
    } else {
        setToggleIcon(!document.body.classList.contains('sidebar-open'));
    }
});
