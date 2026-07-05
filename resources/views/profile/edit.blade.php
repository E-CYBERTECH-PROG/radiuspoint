<x-sidebar-layout title="Profile">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Profile') }}</h1>
    </div>

    <div class="max-w-7xl space-y-6">
        <div class="p-4 sm:p-8 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 shadow-sm sm:rounded-xl">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 shadow-sm sm:rounded-xl">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 shadow-sm sm:rounded-xl">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-sidebar-layout>
