// Replaces the old Alpine toast-array queue with Bootstrap's native Toast component.
// Each call builds one toast node, lets Bootstrap's own autohide handle dismissal, and
// removes the node from the DOM once it finishes hiding.

window.rpToast = function (type, message) {
    var stack = document.getElementById('rp-toast-stack');
    if (!stack) return;

    var bg = type === 'error' ? 'text-bg-danger' : 'text-bg-success';
    var icon = type === 'error' ? 'ti-alert-circle-filled' : 'ti-circle-check-filled';

    var el = document.createElement('div');
    el.className = 'toast align-items-center ' + bg + ' border-0';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.setAttribute('data-bs-autohide', 'true');
    el.setAttribute('data-bs-delay', '5000');
    el.innerHTML =
        '<div class="d-flex">' +
            '<div class="toast-body d-flex align-items-center gap-2">' +
                '<i class="ti ' + icon + '"></i><span></span>' +
            '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
        '</div>';
    el.querySelector('.toast-body span').textContent = message;

    stack.appendChild(el);

    var toast = new bootstrap.Toast(el);
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    toast.show();
};
