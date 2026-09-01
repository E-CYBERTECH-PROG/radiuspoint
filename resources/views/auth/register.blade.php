<x-guest-layout>
    <div class="mb-4 text-center">
        <h1 class="h2 mb-1">Create your ISP account</h1>
        <p class="text-muted">Set up your workspace and start provisioning routers.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="ti ti-alert-circle-filled icon flex-shrink-0"></i>
            <div>
                @foreach ($errors->all() as $message)
                    <p class="mb-0">{{ $message }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="company_name" :value="__('ISP Company Name')" />
            <x-text-input id="company_name" type="text" name="company_name" :value="old('company_name')" required autofocus autocomplete="organization" />
        </div>

        <div class="mb-3">
            <x-input-label for="name" :value="__('Admin Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autocomplete="name" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
        </div>

        <div class="row">
            <div class="col-sm-6 mb-3">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="col-sm-6 mb-3">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>
        </div>

        <x-primary-button class="w-100 mt-2">
            {{ __('Create Account') }}
        </x-primary-button>

        <p class="text-center text-muted small pt-2 mb-0">
            Already have an account?
            <a href="{{ route('login') }}" class="fw-semibold">Log in</a>
        </p>
    </form>
</x-guest-layout>
