{{-- Ops-first: what needs your attention right now — expiring customers, recent payments,
     router/gateway health — comes before any historical chart. Pick this if you check the
     dashboard mainly to handle today's work, not to review trends. Also the one layout using
     the split stat-card treatment (5 separate tiles) instead of the consolidated single card —
     bigger, more distinct numbers for a quick scan. --}}
<x-sidebar-layout title="Dashboard">
    <div x-data="dashboard({{ Illuminate\Support\Js::from($dashboardInitial) }})" x-init="startPolling()">

        @include('dashboard.partials._stat-card-split', ['delay' => 60])

        <div class="mb-3">
            @include('dashboard.partials._expiring-soon', ['delay' => 140])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
            <div class="lg:col-span-2">
                @include('dashboard.partials._recent-transactions', ['delay' => 180])
            </div>
            @include('dashboard.partials._quick-actions', ['delay' => 220])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
            @include('dashboard.partials._router-status', ['delay' => 260])
            @include('dashboard.partials._status-tiles', ['delay' => 300])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            @include('dashboard.partials._revenue-chart', ['delay' => 340])
            @include('dashboard.partials._growth-chart', ['delay' => 380])
            @include('dashboard.partials._top-packages-chart', ['delay' => 420])
        </div>

    </div>

    <x-slot name="scripts">
        @include('dashboard.partials._scripts')
    </x-slot>
</x-sidebar-layout>
