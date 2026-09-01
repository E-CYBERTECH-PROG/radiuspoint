// Wires the notifications bell dropdown in layouts/sidebar.blade.php. The notification
// list and unread count are server-seeded (see the #rp-notif-data JSON island in that
// layout) — this module only renders them and preserves the same two server mutations
// the old Alpine component made: mark one read, mark all read.

document.addEventListener('DOMContentLoaded', function () {
    var dataEl = document.getElementById('rp-notif-data');
    var listEl = document.getElementById('rp-notif-list');
    var badgeEl = document.getElementById('rp-notif-badge');
    var markAllBtn = document.getElementById('rp-notif-mark-all');

    if (!dataEl || !listEl) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var config = JSON.parse(dataEl.textContent);
    var notifications = config.items || [];
    var unread = config.unread || 0;

    function render() {
        badgeEl.style.display = unread > 0 ? '' : 'none';
        markAllBtn.style.display = unread > 0 ? '' : 'none';

        if (notifications.length === 0) {
            listEl.innerHTML = '<p class="text-muted text-center py-4 mb-0 small">No notifications yet.</p>';
            return;
        }

        listEl.innerHTML = notifications.map(function (n) {
            return (
                '<button type="button" class="list-group-item list-group-item-action text-start' + (!n.read ? ' bg-blue-lt' : '') + '" data-id="' + n.id + '" data-url="' + (n.url || '') + '" data-read="' + (n.read ? '1' : '0') + '">' +
                    '<div class="text-body">' + n.message + '</div>' +
                    '<div class="text-muted small">' + n.created_at_human + '</div>' +
                '</button>'
            );
        }).join('');
    }

    listEl.addEventListener('click', function (e) {
        var item = e.target.closest('[data-id]');
        if (!item) return;

        var id = item.getAttribute('data-id');
        var url = item.getAttribute('data-url');
        var wasRead = item.getAttribute('data-read') === '1';

        if (!wasRead) {
            fetch('/notifications/' + id + '/mark-read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            var n = notifications.find(function (x) { return String(x.id) === String(id); });
            if (n) n.read = true;
            unread = Math.max(0, unread - 1);
            render();
        }

        if (url) window.location.href = url;
    });

    markAllBtn.addEventListener('click', function () {
        fetch(config.markAllUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        }).then(function () {
            unread = 0;
            notifications.forEach(function (n) { n.read = true; });
            render();
        });
    });

    render();
});
