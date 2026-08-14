{{-- Balanced default: operations (transactions/quick actions) and analytics (charts) get equal
     billing, one below the other. Pick this if you don't have a strong preference. --}}
<x-sidebar-layout title="Dashboard">
    <div x-data="dashboard({{ Illuminate\Support\Js::from($dashboardInitial) }})" x-init="startPolling()">

        @include('dashboard.partials._stat-card', ['delay' => 60])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
            <div class="lg:col-span-2">
                @include('dashboard.partials._recent-transactions', ['delay' => 220])
            </div>
            <div class="space-y-3">
                @include('dashboard.partials._quick-actions', ['delay' => 260])
                @include('dashboard.partials._status-tiles', ['delay' => 300])
                @include('dashboard.partials._expiring-soon', ['delay' => 340])
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
            <div class="lg:col-span-2">
                @include('dashboard.partials._revenue-chart', ['delay' => 380])
            </div>
            @include('dashboard.partials._router-status', ['delay' => 420])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            @include('dashboard.partials._growth-chart', ['delay' => 460])
            @include('dashboard.partials._top-packages-chart', ['delay' => 500])
        </div>

    </div>

    <x-slot name="scripts">
        @include('dashboard.partials._scripts')
    </x-slot>
</x-sidebar-layout>
