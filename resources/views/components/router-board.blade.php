@props(['portCount' => 5, 'sfpCount' => 0, 'status' => 'offline', 'compact' => false, 'image' => null])

@php
    $ledColor = match($status) {
        'online', 'active' => 'bg-green-500',
        'pending', 'provisioning' => 'bg-amber-500',
        default => 'bg-red-500',
    };
    $blinkSpeed = match($status) {
        'online', 'active' => '1s',
        'pending', 'provisioning' => '1.8s',
        default => null, // offline = solid, no blink
    };
@endphp

<div {{ $attributes->merge(['class' => 'inline-block']) }}>
    <div class="flex items-center {{ $compact ? 'gap-2' : 'gap-4' }}">
        {{-- Real product photo, hotlinked from MikroTik's official CDN --}}
        <div class="relative shrink-0 {{ $compact ? 'w-10 h-10' : 'w-24 h-24' }} bg-white dark:bg-gray-100 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden">
            @if ($image)
                <img src="{{ $image }}" loading="lazy" alt="MikroTik board photo" class="w-full h-full object-contain p-1">
            @else
                <i class='bx bx-router text-gray-300 {{ $compact ? "text-xl" : "text-4xl" }}'></i>
            @endif
            <span class="router-board-led absolute {{ $compact ? '-top-0.5 -right-0.5 w-2 h-2' : '-top-1 -right-1 w-3 h-3' }} rounded-full {{ $ledColor }} ring-2 ring-white dark:ring-gray-950"
                  @if($blinkSpeed) style="animation: router-board-blink {{ $blinkSpeed }} steps(1) infinite;" @endif
                  title="Uplink status"></span>
        </div>

        {{-- Port schematic: shows exact port count / uplink position, independent of the photo --}}
        <div class="bg-gray-800 dark:bg-gray-900 rounded-lg {{ $compact ? 'p-2' : 'p-4' }} shadow-inner border border-gray-700">
            <div class="flex items-center gap-1">
                @for ($i = 1; $i <= $portCount; $i++)
                    <div class="relative flex flex-col items-center">
                        <div class="{{ $compact ? 'w-3 h-4' : 'w-5 h-7' }} bg-gray-600 dark:bg-gray-700 rounded-sm border {{ $i === 1 ? 'border-blue-400' : 'border-gray-500' }} relative">
                            <div class="absolute inset-x-0 top-0.5 h-0.5 bg-gray-500 mx-0.5"></div>
                        </div>
                        @if ($i === 1 && !$compact)
                            <span class="text-[8px] text-blue-300 font-bold uppercase tracking-wide mt-1">Uplink</span>
                        @endif
                    </div>
                @endfor

                @if ($sfpCount > 0)
                    <div class="w-px h-6 bg-gray-600 mx-1"></div>
                    @for ($i = 1; $i <= $sfpCount; $i++)
                        <div class="{{ $compact ? 'w-2.5 h-4' : 'w-4 h-7' }} bg-gray-500 dark:bg-gray-600 rounded-[2px] border border-gray-400"></div>
                    @endfor
                @endif
            </div>
        </div>
    </div>
    @unless($compact)
        <p class="text-[10px] text-gray-400 text-center mt-1 uppercase tracking-wide">{{ ucfirst($status) }}</p>
    @endunless
</div>

@once
    <style>
        @keyframes router-board-blink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0.15; }
        }
    </style>
@endonce
