{{-- Analytics-first layout: revenue and growth charts lead, operational lists sit below. --}}
<x-sidebar-layout title="Dashboard">
    <div x-data="dashboard({{ Illuminate\Support\Js::from($dashboardInitial) }})" x-init="startPolling()">

        @include('dashboard.partials._stat-card', ['delay' => 60])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
            <div class="lg:col-span-2">
                @include('dashboard.partials._revenue-chart', ['delay' => 140])
            </div>
            @include('dashboard.partials._growth-chart', ['delay' => 180])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
            @include('dashboard.partials._top-packages-chart', ['delay' => 220])
            @include('dashboard.partials._router-status', ['delay' => 260])
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div class="lg:col-span-2">
                @include('dashboard.partials._recent-transactions', ['delay' => 300])
            </div>
            <div class="space-y-3">
                @include('dashboard.partials._quick-actions', ['delay' => 340])
                @include('dashboard.partials._status-tiles', ['delay' => 380])
                @include('dashboard.partials._expiring-soon', ['delay' => 420])
            </div>
        </div>

    </div>

    <x-slot name="scripts">
        @include('dashboard.partials._scripts')
    </x-slot>
</x-sidebar-layout>
