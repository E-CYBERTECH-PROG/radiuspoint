<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-gray-900">Create your ISP account</h1>
        <p class="text-sm text-gray-500 mt-1">Set up your workspace and start provisioning routers.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-2.5 bg-red-50 border border-red-100 text-red-700 text-sm rounded-lg px-4 py-3">
            <i class="bx bxs-error-circle text-lg shrink-0 mt-0.5"></i>
            <div>
                @foreach ($errors->all() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="company_name" :value="__('ISP Company Name')" />
            <x-text-input id="company_name" type="text" name="company_name" :value="old('company_name')" required autofocus autocomplete="organization" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Admin Name')" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autocomplete="name" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>
        </div>

        <x-primary-button class="w-full mt-2">
            {{ __('Create Account') }}
        </x-primary-button>

        <p class="text-center text-sm text-gray-500 pt-2">
            Already have an account?
            <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">Log in</a>
        </p>
    </form>
</x-guest-layout>
