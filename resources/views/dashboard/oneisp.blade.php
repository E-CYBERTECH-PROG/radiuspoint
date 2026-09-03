{{--
    The Dashboard — a card-based overview. Uses the same x-sidebar-layout shell as every
    other page in the app (see layouts/sidebar.blade.php).

    Renders once server-side, no live poll — the customer online split in particular needs
    a couple of extra queries that aren't worth re-running every few seconds.
--}}
<x-sidebar-layout title="Dashboard">
    <div class="row row-cards g-2 mb-2">
        <div class="col-lg-4">
            @include('dashboard.partials._oneisp-hero', ['delay' => 60])
        </div>
        <div class="col-lg-8">
            @include('dashboard.partials._oneisp-customers', ['delay' => 100])
        </div>
    </div>

    <div class="row row-cards g-2 mb-2">
        <div class="col-lg-4">
            {{-- Matches the Revenue Report card's height (via the parent row's default
                 align-items:stretch) then splits it evenly: Subscriptions+Profit fill the
                 upper half, Earnings the lower half — flex:1 1 0 on both rather than relying
                 on Bootstrap's grid, since content height alone won't split 50/50. --}}
            <div class="d-flex flex-column h-100 gap-2">
                <div class="row g-2" style="flex:1 1 0">
                    <div class="col-6">
                        @include('dashboard.partials._oneisp-subscriptions', ['delay' => 140])
                    </div>
                    <div class="col-6">
                        @include('dashboard.partials._oneisp-profit', ['delay' => 160])
                    </div>
                </div>
                <div style="flex:1 1 0">
                    @include('dashboard.partials._oneisp-earnings', ['delay' => 180])
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100 rp-rise" style="--rp-delay: 220ms">
                <div class="row g-0 h-100">
                    <div class="col-lg-8 border-end">
                        @include('dashboard.partials._oneisp-revenue-chart')
                    </div>
                    <div class="col-lg-4">
                        @include('dashboard.partials._oneisp-side-chart')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards g-2">
        <div class="col-lg-8">
            @include('dashboard.partials._oneisp-packages-table', ['delay' => 300])
        </div>
        <div class="col-lg-4">
            @include('dashboard.partials._oneisp-package-performance', ['delay' => 340])
        </div>
    </div>

    <x-slot name="scripts">
        @include('dashboard.partials._oneisp-scripts')
    </x-slot>
</x-sidebar-layout>
