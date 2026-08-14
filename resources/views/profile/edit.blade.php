<x-sidebar-layout title="Profile">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Profile') }}</h1>
    </div>

    <div class="max-w-7xl space-y-6">
        <div class="p-4 sm:p-8 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 shadow-sm sm:rounded-xl space-y-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Appearance</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Text, cards, and buttons each pick their own color independently. Saved to this browser.</p>
            </div>

            @include('profile.partials.color-role-picker', [
                'role' => 'text', 'label' => 'Text & Links', 'description' => 'Headings, links, and icon accents across the app.',
                'themes' => $themes, 'defaultLabel' => 'Blue', 'defaultSwatch' => 'background: linear-gradient(135deg, #2563eb, #4338ca);',
            ])

            <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
                @include('profile.partials.color-role-picker', [
                    'role' => 'card', 'label' => 'Card Background', 'description' => 'The panel/card surfaces on every page.',
                    'themes' => $themes, 'defaultLabel' => 'White', 'defaultSwatch' => 'background: #fff;',
                ])
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
                @include('profile.partials.color-role-picker', [
                    'role' => 'button', 'label' => 'Buttons', 'description' => 'Primary action buttons throughout the app.',
                    'themes' => $themes, 'defaultLabel' => 'Blue', 'defaultSwatch' => 'background: linear-gradient(135deg, #2563eb, #4338ca);',
                ])
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
                @include('profile.partials.dashboard-layout-picker')
            </div>
        </div>

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
