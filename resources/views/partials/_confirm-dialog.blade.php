{{--
    Global replacement for the native confirm() dialog — a sliding panel from the top of the
    viewport instead of the browser's blocking modal. Two call shapes, same underlying UI:

    - rpConfirm(event, message) — for `onsubmit="return rpConfirm(event, 'Delete this?')"` on
      a plain <form>, a drop-in swap for the old `onsubmit="return confirm('Delete this?')"`.
      Always returns false to block the native synchronous submit; on confirm it re-submits
      the same form via the native (non-event-firing) HTMLFormElement.prototype.submit, so
      this listener isn't re-triggered.
    - await rpConfirmAsync(message) — for JS call sites that used `if (!confirm(msg)) return;`
      inside an (async) function; becomes `if (! await rpConfirmAsync(msg)) return;`.
--}}
<div
    x-data="confirmDialog()"
    @confirm-action.window="open($event.detail)"
    x-show="visible"
    x-cloak
    class="fixed inset-0 z-[200] flex items-start justify-center pt-24 px-4"
>
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" x-show="visible" x-transition.opacity @click="cancel()"></div>

    <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-8"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-8"
        @keydown.escape.window="cancel()"
        class="relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-2xl w-full max-w-sm p-6"
    >
        <div class="flex items-start gap-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center shrink-0">
                <i class="bx bxs-error-circle text-amber-500 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-gray-900 dark:text-white pt-2" x-text="message"></p>
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" @click="cancel()" class="text-gray-600 dark:text-gray-300 font-bold text-sm px-4 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Cancel</button>
            <button type="button" @click="proceed()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm px-5 py-2 rounded-lg transition-colors">Confirm</button>
        </div>
    </div>
</div>

<script>
    function confirmDialog() {
        return {
            visible: false,
            message: '',
            resolve: null,

            open(detail) {
                this.message = detail.message;
                this.resolve = detail.resolve;
                this.visible = true;
            },

            cancel() {
                this.visible = false;
                if (this.resolve) this.resolve(false);
                this.resolve = null;
            },

            proceed() {
                this.visible = false;
                if (this.resolve) this.resolve(true);
                this.resolve = null;
            },
        };
    }

    // Registered synchronously (parsed before Alpine's own deferred script runs), same
    // ordering guarantee the dashboard/customers pages already rely on for alpine:init.
    function rpConfirmAsync(message) {
        return new Promise((resolve) => {
            window.dispatchEvent(new CustomEvent('confirm-action', { detail: { message, resolve } }));
        });
    }

    function rpConfirm(event, message) {
        const form = event.target;
        rpConfirmAsync(message).then((ok) => {
            if (ok) {
                // Bypasses the native 'submit' event on purpose (see the top of this file), so
                // the global page-loader listener in layouts/sidebar.blade.php can't see this
                // submission — start it explicitly instead.
                if (window.rpShowPageLoader) window.rpShowPageLoader();
                HTMLFormElement.prototype.submit.call(form);
            }
        });
        return false;
    }
</script>
