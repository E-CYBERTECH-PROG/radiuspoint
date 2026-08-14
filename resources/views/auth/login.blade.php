<x-guest-layout>
    <div
        x-data="loginLockout({{ session('login_locked_until') ? (int) session('login_locked_until') : 'null' }})"
        x-init="init()"
    >
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-gray-900">Welcome back</h1>
            <p class="text-sm text-gray-500 mt-1">Log in to your RadiusPoint dashboard.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

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

        <!-- Live countdown while locked out — the error message above already says "try again in
             N seconds" once, as plain text; this keeps it visibly ticking down and, more
             importantly, actually disables the form so the user can't just keep clicking through
             the lockout (every one of those clicks used to just hit the server and re-fail). -->
        <template x-if="locked">
            <div class="mb-5 flex items-center gap-2.5 bg-amber-50 border border-amber-100 text-amber-700 text-sm rounded-lg px-4 py-3">
                <i class="bx bx-time-five text-lg shrink-0"></i>
                <span>Too many attempts. Try again in <strong x-text="secondsLeft"></strong>s.</span>
            </div>
        </template>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" x-bind:disabled="locked" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" x-bind:disabled="locked" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" name="remember" x-bind:disabled="locked">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-indigo-600 hover:text-indigo-800 font-medium" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full" x-bind:disabled="locked" x-bind:class="locked ? 'opacity-50 cursor-not-allowed' : ''">
                <span x-show="!locked">{{ __('Log in') }}</span>
                <span x-show="locked" x-cloak>Try again in <span x-text="secondsLeft"></span>s</span>
            </x-primary-button>

            @if (Route::has('register'))
                <p class="text-center text-sm text-gray-500 pt-2">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">Get started</a>
                </p>
            @endif
        </form>
    </div>

    <script>
        function loginLockout(lockedUntil) {
            return {
                lockedUntil: lockedUntil,
                secondsLeft: 0,
                locked: false,

                init() {
                    if (!this.lockedUntil) return;
                    this.tick();
                    const interval = setInterval(() => {
                        this.tick();
                        if (!this.locked) clearInterval(interval);
                    }, 1000);
                },

                tick() {
                    this.secondsLeft = Math.max(0, this.lockedUntil - Math.floor(Date.now() / 1000));
                    this.locked = this.secondsLeft > 0;
                },
            };
        }
    </script>
</x-guest-layout>
