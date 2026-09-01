<?php

namespace App\Support;

/**
 * Single source of truth for the app's accent-color system. Emits one CSS override
 * block per non-default color, overriding the `--tblr-primary*` custom properties that
 * every Tabler component (buttons, links, badges' `-lt` tints, focus rings, ...) already
 * reads from — so a single `data-accent-theme` attribute on <html> re-themes the whole
 * app with no rebuild. See layouts/sidebar.blade.php for where this attribute is set.
 *
 * Previously this covered three independently choosable roles (text/card/button),
 * mirroring three separate sets of Tailwind utility-class overrides. Tabler's palette is
 * driven by one `--tblr-primary` variable per surface, so that distinction no longer maps
 * onto anything real — three attributes would just fight over the same variable — and has
 * been collapsed into one accent choice.
 */
class ThemePalette
{
    /**
     * "blue" is absent — it's the app's default color (Tabler's own $primary, set in
     * resources/css/_tabler-variables.scss), so it needs no override rule.
     */
    public const COLORS = [
        'indigo' => ['600' => '#4f46e5', 'label' => 'Indigo'],
        'emerald' => ['600' => '#059669', 'label' => 'Emerald'],
        'rose' => ['600' => '#e11d48', 'label' => 'Rose'],
        'amber' => ['600' => '#d97706', 'label' => 'Amber'],
        'slate' => ['600' => '#475569', 'label' => 'Slate'],
    ];

    public static function accentCss(string $attr = 'data-accent-theme'): string
    {
        $css = '';

        foreach (self::COLORS as $key => $c) {
            $rgb = self::hexToRgb($c['600']);

            $css .= <<<CSS
                html[{$attr}="{$key}"] {
                    --tblr-primary: {$c['600']};
                    --tblr-primary-rgb: {$rgb};
                    --tblr-link-color: {$c['600']};
                    --tblr-link-color-rgb: {$rgb};
                }

                CSS;
        }

        return $css;
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        return implode(', ', array_map(
            fn (string $c) => hexdec($c),
            str_split($hex, 2)
        ));
    }
}
