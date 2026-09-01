// Sidebar rail collapse (desktop) and dark-mode toggle. The synchronous anti-flash
// application of both states lives inline in layouts/sidebar.blade.php's <head> (it
// must run before first paint, ahead of this deferred module) — this file only wires
// up the toggle buttons.

document.addEventListener('DOMContentLoaded', function () {
    var sidebarToggle = document.getElementById('rp-sidebar-toggle');
    var sidebar = document.getElementById('rp-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            var collapsed = sidebar.classList.toggle('rp-collapsed');
            localStorage.setItem('rp_sidebar_open', collapsed ? '0' : '1');
        });
    }

    var themeToggles = document.querySelectorAll('.rp-theme-toggle');

    function syncThemeIcons() {
        var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        themeToggles.forEach(function (btn) {
            var icon = btn.querySelector('i');
            if (icon) icon.className = 'ti ' + (isDark ? 'ti-sun' : 'ti-moon') + ' icon';
        });
    }

    themeToggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('rp_dark_mode', next === 'dark' ? '1' : '0');
            syncThemeIcons();
            // Lets any page-specific script (e.g. dashboard Chart.js instances) react to a
            // theme flip without polling — see dashboard/partials/_oneisp-scripts.blade.php.
            document.dispatchEvent(new CustomEvent('rp:theme-changed', { detail: { dark: next === 'dark' } }));
        });
    });

    syncThemeIcons();
});
