{{--
    The Dashboard — a card-based overview. Uses the same x-sidebar-layout shell as every
    other page in the app (see layouts/sidebar.blade.php).

    Renders once server-side, no live poll — the customer online split in particular needs
    a couple of extra queries that aren't worth re-running every few seconds.
--}}
<x-sidebar-layout title="Dashboard">
    <div class="space-y-3">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
            <div class="lg:col-span-4">
                @include('dashboard.partials._oneisp-hero', ['delay' => 60])
            </div>
            <div class="lg:col-span-8">
                @include('dashboard.partials._oneisp-customers', ['delay' => 100])
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
            <div class="lg:col-span-4 grid grid-rows-2 gap-3">
                <div class="grid grid-cols-2 gap-3">
                    @include('dashboard.partials._oneisp-subscriptions', ['delay' => 140])
                    @include('dashboard.partials._oneisp-profit', ['delay' => 160])
                </div>
                @include('dashboard.partials._oneisp-earnings', ['delay' => 180])
            </div>
            <div class="lg:col-span-8">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm h-full grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 dark:divide-gray-800 rp-rise" style="--rp-delay: 220ms">
                    <div class="lg:col-span-2">
                        @include('dashboard.partials._oneisp-revenue-chart')
                    </div>
                    <div class="lg:col-span-1">
                        @include('dashboard.partials._oneisp-side-chart')
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
            <div class="lg:col-span-8">
                @include('dashboard.partials._oneisp-packages-table', ['delay' => 300])
            </div>
            <div class="lg:col-span-4">
                @include('dashboard.partials._oneisp-package-performance', ['delay' => 340])
            </div>
        </div>

    </div>

    <x-slot name="scripts">
        @include('dashboard.partials._oneisp-scripts')
    </x-slot>
</x-sidebar-layout>
