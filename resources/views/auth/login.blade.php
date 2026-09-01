<x-guest-layout>
    <div class="mb-4 text-center">
        <h1 class="h2 mb-1">Welcome back</h1>
        <p class="text-muted">Log in to your RadiusPoint dashboard.</p>
    </div>

    <x-auth-session-status class="mb-3" :status="session('status')" />

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

    <div class="alert alert-warning d-flex align-items-center gap-2" id="rp-lockout-alert" style="display:none">
        <i class="ti ti-clock icon flex-shrink-0"></i>
        <span>Too many attempts. Try again in <strong id="rp-lockout-seconds"></strong>s.</span>
    </div>

    <form method="POST" action="{{ route('login') }}" id="rp-login-form">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <label class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <span class="form-check-label">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="small" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-100" id="rp-login-submit">
            <span id="rp-login-submit-label">{{ __('Log in') }}</span>
        </x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-muted small pt-2 mb-0">
                Don't have an account?
                <a href="{{ route('register') }}" class="fw-semibold">Get started</a>
            </p>
        @endif
    </form>

    <script>
        (function () {
            var lockedUntil = {{ session('login_locked_until') ? (int) session('login_locked_until') : 'null' }};
            if (!lockedUntil) return;

            var alertEl = document.getElementById('rp-lockout-alert');
            var secondsEl = document.getElementById('rp-lockout-seconds');
            var submitBtn = document.getElementById('rp-login-submit');
            var submitLabel = document.getElementById('rp-login-submit-label');
            var email = document.getElementById('email');
            var password = document.getElementById('password');
            var remember = document.getElementById('remember_me');

            function tick() {
                var secondsLeft = Math.max(0, lockedUntil - Math.floor(Date.now() / 1000));
                var locked = secondsLeft > 0;

                alertEl.style.display = locked ? '' : 'none';
                secondsEl.textContent = secondsLeft;
                submitBtn.disabled = locked;
                email.disabled = locked;
                password.disabled = locked;
                remember.disabled = locked;
                submitLabel.textContent = locked ? 'Try again in ' + secondsLeft + 's' : '{{ __('Log in') }}';

                if (!locked) clearInterval(interval);
            }

            tick();
            var interval = setInterval(tick, 1000);
        })();
    </script>
</x-guest-layout>
