@props(['name', 'title' => 'Filters', 'clearUrl' => null])

{{--
    A filter panel, meant to sit *inside* the page's own `<form method="GET">` alongside the
    table-toolbar's search/per-page fields (see customers/routers/receipts index views) —
    Apply just submits that shared form. Same offcanvas chrome as every other side panel in
    the app (Add Customer, Compose SMS, …) — right-side, Tabler's default width, same backdrop
    — not a centered modal, so it reads as one consistent panel pattern app-wide.
--}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-filters-{{ $name }}" aria-hidden="true">
    <div class="offcanvas-header border-bottom">
        <h3 class="offcanvas-title">{{ $title }}</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="row g-3">
            {{ $slot }}
        </div>
    </div>
    <div class="offcanvas-footer p-3 border-top d-flex align-items-center gap-3">
        @if($clearUrl)
            <a href="{{ $clearUrl }}" class="btn w-100">Clear Filters</a>
        @endif
        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
    </div>
</div>
