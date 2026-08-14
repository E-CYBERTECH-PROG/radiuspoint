{{--
    One reusable picker for one color role (text / card / button) — three of these render on
    the Appearance section, each independently persisted to its own localStorage key and its
    own data-{role}-theme attribute (see layouts/sidebar.blade.php's flash-prevention script and
    App\Support\ThemePalette for how the choice actually takes effect).

    Expects: $role ('text'|'card'|'button'), $label, $description, $themes (ThemePalette::COLORS),
    $defaultLabel, $defaultSwatch (inline CSS background for the "unchanged" option's swatch).
--}}
<div x-data="{ theme: localStorage.getItem('rp_theme_{{ $role }}') || 'blue' }">
    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $label }}</h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ $description }}</p>
    <div class="flex flex-wrap gap-4">
        <button type="button" @click="
            theme = 'blue';
            localStorage.setItem('rp_theme_{{ $role }}', 'blue');
            document.documentElement.setAttribute('data-{{ $role }}-theme', 'blue');
        " class="flex flex-col items-center gap-1.5 group">
            <span class="w-10 h-10 rounded-full transition-transform group-hover:scale-105 border border-gray-200 dark:border-gray-700" :class="theme === 'blue' ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-offset-gray-950' : ''" style="{{ $defaultSwatch }}"></span>
            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400">{{ $defaultLabel }}</span>
        </button>
        @foreach($themes as $key => $c)
            <button type="button" @click="
                theme = '{{ $key }}';
                localStorage.setItem('rp_theme_{{ $role }}', '{{ $key }}');
                document.documentElement.setAttribute('data-{{ $role }}-theme', '{{ $key }}');
            " class="flex flex-col items-center gap-1.5 group">
                <span class="w-10 h-10 rounded-full transition-transform group-hover:scale-105" :class="theme === '{{ $key }}' ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-offset-gray-950' : ''" style="background: linear-gradient(135deg, {{ $c['gradFrom'] }}, {{ $c['gradTo'] }});"></span>
                <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400">{{ $c['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
