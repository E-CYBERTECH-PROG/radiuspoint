<x-sidebar-layout title="Profile">
    <div class="mb-4">
        <h1 class="mb-0">{{ __('Profile') }}</h1>
    </div>

    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Appearance</h2>
                    <p class="text-muted">Pick the app's accent color. Saved to this browser.</p>

                    @include('profile.partials.color-role-picker', ['themes' => $themes])
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="col-md-8 col-xl-6">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="col-md-8 col-xl-6">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="col-md-8 col-xl-6">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>
