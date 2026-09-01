{{--
    Global replacement for the native confirm() dialog — a Bootstrap modal instead of the
    browser's blocking modal. Two call shapes, same underlying UI:

    - rpConfirm(event, message) — for `onsubmit="return rpConfirm(event, 'Delete this?')"` on
      a plain <form>, a drop-in swap for the old `onsubmit="return confirm('Delete this?')"`.
      Always returns false to block the native synchronous submit; on confirm it re-submits
      the same form via the native (non-event-firing) HTMLFormElement.prototype.submit, so
      this listener isn't re-triggered.
    - await rpConfirmAsync(message) — for JS call sites that used `if (!confirm(msg)) return;`
      inside an (async) function; becomes `if (! await rpConfirmAsync(msg)) return;`.
--}}
<div class="modal modal-blur fade" id="rp-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle icon mb-2 text-warning icon-lg"></i>
                <p class="mb-0" id="rp-confirm-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn w-100 me-2" data-bs-dismiss="modal" id="rp-confirm-cancel">Cancel</button>
                <button type="button" class="btn btn-danger w-100" id="rp-confirm-proceed">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modalEl = document.getElementById('rp-confirm-modal');
        var messageEl = document.getElementById('rp-confirm-message');
        var proceedBtn = document.getElementById('rp-confirm-proceed');
        var modal = null;
        var resolveFn = null;

        function getModal() {
            if (!modal) modal = new bootstrap.Modal(modalEl);
            return modal;
        }

        // Registered synchronously (parsed before the deferred module bundle runs), same
        // ordering guarantee other inline call sites rely on.
        window.rpConfirmAsync = function (message) {
            return new Promise(function (resolve) {
                resolveFn = resolve;
                messageEl.textContent = message;
                getModal().show();
            });
        };

        window.rpConfirm = function (event, message) {
            var form = event.target;
            window.rpConfirmAsync(message).then(function (ok) {
                if (ok) {
                    // Bypasses the native 'submit' event on purpose (see the top of this file),
                    // so the global page-loader listener in layouts/sidebar.blade.php can't see
                    // this submission — start it explicitly instead.
                    if (window.rpShowPageLoader) window.rpShowPageLoader();
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
            return false;
        };

        proceedBtn.addEventListener('click', function () {
            getModal().hide();
            if (resolveFn) resolveFn(true);
            resolveFn = null;
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            if (resolveFn) resolveFn(false);
            resolveFn = null;
        });
    })();
</script>
