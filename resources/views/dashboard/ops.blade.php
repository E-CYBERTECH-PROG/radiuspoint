{{-- Ops-first layout: expiring customers, payments, and router health lead over charts.
     Uses the split stat-card treatment (5 tiles) instead of the consolidated card. --}}
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
