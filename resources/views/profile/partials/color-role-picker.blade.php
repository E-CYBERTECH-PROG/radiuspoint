{{--
    App accent-color picker. See App\Support\ThemePalette — one CSS custom-property
    override per choice, persisted to localStorage, applied instantly with no rebuild.
    Expects: $themes (ThemePalette::COLORS).
--}}
<div>
    <div class="d-flex flex-wrap gap-3" id="rp-accent-picker">
        <button type="button" class="rp-accent-swatch" data-accent="blue" style="background:#066fd1">
            <span class="visually-hidden">Blue (default)</span>
        </button>
        @foreach($themes as $key => $c)
            <button type="button" class="rp-accent-swatch" data-accent="{{ $key }}" style="background:{{ $c['600'] }}">
                <span class="visually-hidden">{{ $c['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>

@once
    <style>
        .rp-accent-swatch {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            border: 2px solid transparent;
            padding: 0;
            cursor: pointer;
        }
        .rp-accent-swatch.active {
            border-color: var(--tblr-body-color);
            box-shadow: 0 0 0 2px var(--tblr-body-bg);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var current = localStorage.getItem('rp_accent_theme') || 'blue';

            document.querySelectorAll('.rp-accent-swatch').forEach(function (btn) {
                if (btn.getAttribute('data-accent') === current) btn.classList.add('active');

                btn.addEventListener('click', function () {
                    var accent = btn.getAttribute('data-accent');
                    localStorage.setItem('rp_accent_theme', accent);
                    document.documentElement.setAttribute('data-accent-theme', accent);
                    document.querySelectorAll('.rp-accent-swatch').forEach(function (b) {
                        b.classList.toggle('active', b === btn);
                    });
                });
            });
        });
    </script>
@endonce
