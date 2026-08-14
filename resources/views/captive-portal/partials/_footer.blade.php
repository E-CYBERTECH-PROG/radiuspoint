@if($tenant->support_phone || $tenant->location)
    <div class="footer">
        @if($tenant->support_phone)
            <a href="tel:{{ $tenant->support_phone }}" class="item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.5.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.8 21 3 13.2 3 3.7c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.5.1.4 0 .8-.2 1L6.6 10.8Z" stroke="#6b7280" stroke-width="1.5" stroke-linejoin="round"/></svg>
                <span>{{ $tenant->support_phone }}</span>
            </a>
        @endif
        @if($tenant->location)
            <span class="item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.6 7-11.5A7 7 0 0 0 5 9.5C5 14.4 12 21 12 21Z" stroke="#6b7280" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.2" stroke="#6b7280" stroke-width="1.5"/></svg>
                <span>{{ $tenant->location }}</span>
            </span>
        @endif
    </div>
@endif
