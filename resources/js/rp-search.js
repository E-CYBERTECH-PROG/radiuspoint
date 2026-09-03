// Wires the global search modal (partials/_search-modal.blade.php). Ctrl+K or the header
// search icon opens it; results come from SearchController::index via fetch, debounced
// while typing, instead of navigating to a separate search page.

document.addEventListener('DOMContentLoaded', function () {
    var trigger = document.getElementById('rp-search-trigger');
    var modalEl = document.getElementById('rp-search-modal');
    if (!modalEl) return;

    var input = document.getElementById('rp-search-input');
    var resultsEl = document.getElementById('rp-search-results');
    var searchUrl = modalEl.dataset.searchUrl;
    var modal = null;
    var debounceTimer = null;
    var currentRequest = 0;

    function getModal() {
        if (!modal) modal = new bootstrap.Modal(modalEl);
        return modal;
    }

    function openModal() {
        getModal().show();
    }

    if (trigger) trigger.addEventListener('click', openModal);

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openModal();
        }
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        input.focus();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        input.value = '';
        resultsEl.innerHTML = '<p class="text-muted mb-0">Type something above to search.</p>';
    });

    function group(title, items, render) {
        if (!items.length) return '';
        return (
            '<div class="mb-3">' +
                '<h3 class="text-uppercase text-muted small fw-bold mb-2">' + title + '</h3>' +
                '<div class="list-group">' + items.map(render).join('') + '</div>' +
            '</div>'
        );
    }

    function render(data) {
        var html = '';
        html += group('Hotspot Users', data.hotspot_users, function (u) {
            return '<a href="' + u.url + '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                '<span class="fw-bold">' + u.label + '</span><span class="text-muted text-uppercase small">' + (u.sub || '') + '</span></a>';
        });
        html += group('PPPoE Users', data.pppoe_users, function (u) {
            return '<a href="' + u.url + '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                '<span class="fw-bold">' + u.label + '</span><span class="text-muted text-uppercase small">' + (u.sub || '') + '</span></a>';
        });
        html += group('Routers', data.routers, function (r) {
            return '<a href="' + r.url + '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                '<span class="fw-bold">' + r.label + '</span><span class="text-muted small">' + (r.sub || '') + '</span></a>';
        });
        html += group('Transactions', data.transactions, function (t) {
            return '<a href="' + t.url + '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                '<div><span class="fw-bold">' + t.label + '</span><span class="text-muted small ms-2">' + (t.sub || '') + '</span></div>' +
                '<span class="font-monospace fw-bold small">KES ' + t.amount + '</span></a>';
        });

        if (html === '') html = '<p class="text-muted mb-0">No results for "' + input.value + '".</p>';
        resultsEl.innerHTML = html;
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = input.value.trim();

        if (q === '') {
            resultsEl.innerHTML = '<p class="text-muted mb-0">Type something above to search.</p>';
            return;
        }

        debounceTimer = setTimeout(function () {
            var requestId = ++currentRequest;
            fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (requestId !== currentRequest) return;
                    render(data);
                });
        }, 250);
    });
});
