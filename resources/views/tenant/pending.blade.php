<x-guest-layout>
    <div class="mb-3 text-muted small">
        {{ __('Thanks for verifying your email! Your company account is now awaiting review by our team. We\'ll email you as soon as it\'s approved, and you\'ll be able to log in and start managing your hotspot business.') }}
    </div>

    <div class="d-flex justify-content-end">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-muted">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
