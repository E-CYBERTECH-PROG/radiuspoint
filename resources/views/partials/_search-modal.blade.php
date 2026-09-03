{{--
    Global search — Ctrl+K or the header search icon opens this instead of navigating to a
    separate /search page. Results come from SearchController::index via fetch (see
    resources/js/rp-search.js); route('search') itself is unused for direct navigation now.
--}}
<div class="modal modal-blur fade" id="rp-search-modal" tabindex="-1" role="dialog" aria-hidden="true" data-search-url="{{ route('search') }}">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="input-icon p-3 border-bottom">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" id="rp-search-input" class="form-control form-control-lg border-0" placeholder="Search phone numbers, routers, transactions…" autocomplete="off">
                </div>
                <div id="rp-search-results" class="p-3" style="max-height:60vh;overflow-y:auto">
                    <p class="text-muted mb-0">Type something above to search.</p>
                </div>
            </div>
        </div>
    </div>
</div>
