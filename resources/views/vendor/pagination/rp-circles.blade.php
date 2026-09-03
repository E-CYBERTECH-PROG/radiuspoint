{{-- Circular-pill pagination — Laravel's bundled default view renders raw Tailwind utility
     classes, which render unstyled now that this app is on Tabler/Bootstrap, not Tailwind.
     Rolled out to vouchers/index.blade.php only for now via $paginator->links('vendor.pagination.rp-circles'); --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <p class="text-muted small mb-0">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="fw-medium">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="fw-medium">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!}
            <span class="fw-medium">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>

        <ul class="d-flex align-items-center gap-2 mb-0" style="list-style:none;padding-left:0">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="rp-pg-circle rp-pg-disabled" aria-hidden="true"><i class="ti ti-chevron-left"></i></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rp-pg-circle" aria-label="{{ __('pagination.previous') }}"><i class="ti ti-chevron-left"></i></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="rp-pg-circle rp-pg-disabled" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="rp-pg-circle rp-pg-active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="rp-pg-circle" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rp-pg-circle" aria-label="{{ __('pagination.next') }}"><i class="ti ti-chevron-right"></i></a>
                @else
                    <span class="rp-pg-circle rp-pg-disabled" aria-hidden="true"><i class="ti ti-chevron-right"></i></span>
                @endif
            </li>
        </ul>
    </nav>

    @once
        <style>
            .rp-pg-circle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.25rem;
                height: 2.25rem;
                border-radius: 50%;
                border: 1px solid var(--tblr-border-color);
                background: var(--tblr-bg-surface, #fff);
                color: var(--tblr-primary);
                font-size: .8125rem;
                font-weight: 600;
                text-decoration: none;
                transition: background-color .15s ease, color .15s ease, border-color .15s ease;
            }
            a.rp-pg-circle:hover {
                background: var(--tblr-primary-lt, rgba(115, 103, 240, .1));
                border-color: var(--tblr-primary);
            }
            .rp-pg-circle.rp-pg-active {
                background: var(--tblr-primary);
                border-color: var(--tblr-primary);
                color: #fff;
            }
            .rp-pg-circle.rp-pg-disabled {
                color: var(--tblr-secondary, #9ca3af);
                cursor: default;
                opacity: .6;
            }
        </style>
    @endonce
@endif
