<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Thanks for verifying your email! Your company account is now awaiting review by our team. We\'ll email you as soon as it\'s approved, and you\'ll be able to log in and start managing your hotspot business.') }}
    </div>

    <div class="mt-4 flex items-center justify-end">
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
