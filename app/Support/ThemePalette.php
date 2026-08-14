<?php

namespace App\Support;

/**
 * Single source of truth for the app's accent-color system — previously this lived inline in a
 * layouts/sidebar.blade.php <style> @php block (one hardcoded palette, one role: text+buttons
 * together). Pulled out here as part of splitting it into three *independently* choosable roles
 * (text, card background, button) per user request, and to stop duplicating color values
 * between the picker UI and the CSS-generation code.
 *
 * How it works: each role gets its own `data-{role}-theme` attribute on <html> (see
 * layouts/sidebar.blade.php's flash-prevention script), and this class emits one CSS rule block
 * per non-default color, scoped by `html[data-{role}-theme="key"]`, re-targeting the exact
 * Tailwind utility classes that role's elements already use. "Reskin every one of 50+ pages"
 * would mean rewriting every class site to a CSS variable instead — out of scope; this reaches
 * every page for free because the override rules live in the shared layout, but only affects
 * elements using these specific hardcoded Tailwind classes.
 */
class ThemePalette
{
    /**
     * "blue" is deliberately absent — it's every role's default (the app's original color), so
     * selecting it needs no override rule at all; the untouched Tailwind classes already are blue.
     */
    public const COLORS = [
        'indigo' => ['400' => '#818cf8', '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca', '50' => '#eef2ff', 'dark' => '#1e1b4b', 'dark20' => 'rgba(79,70,229,.2)', 'dark10' => 'rgba(79,70,229,.1)', 'gradFrom' => '#4f46e5', 'gradTo' => '#7c3aed', 'label' => 'Indigo'],
        'emerald' => ['400' => '#34d399', '500' => '#10b981', '600' => '#059669', '700' => '#047857', '50' => '#ecfdf5', 'dark' => '#022c22', 'dark20' => 'rgba(5,150,105,.2)', 'dark10' => 'rgba(5,150,105,.1)', 'gradFrom' => '#059669', 'gradTo' => '#0d9488', 'label' => 'Emerald'],
        'rose' => ['400' => '#fb7185', '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c', '50' => '#fff1f2', 'dark' => '#4c0519', 'dark20' => 'rgba(225,29,72,.2)', 'dark10' => 'rgba(225,29,72,.1)', 'gradFrom' => '#e11d48', 'gradTo' => '#db2777', 'label' => 'Rose'],
        'amber' => ['400' => '#fbbf24', '500' => '#f59e0b', '600' => '#d97706', '700' => '#b45309', '50' => '#fffbeb', 'dark' => '#451a03', 'dark20' => 'rgba(217,119,6,.2)', 'dark10' => 'rgba(217,119,6,.1)', 'gradFrom' => '#d97706', 'gradTo' => '#dc2626', 'label' => 'Amber'],
        'slate' => ['400' => '#94a3b8', '500' => '#64748b', '600' => '#475569', '700' => '#334155', '50' => '#f8fafc', 'dark' => '#0f172a', 'dark20' => 'rgba(71,85,105,.2)', 'dark10' => 'rgba(71,85,105,.1)', 'gradFrom' => '#475569', 'gradTo' => '#1e293b', 'label' => 'Slate'],
    ];

    public static function textCss(string $attr = 'data-text-theme'): string
    {
        $css = '';
        foreach (self::COLORS as $key => $c) {
            $css .= <<<CSS
                html[{$attr}="{$key}"] .text-blue-400 { color: {$c['400']} !important; }
                html[{$attr}="{$key}"] .text-blue-500 { color: {$c['500']} !important; }
                html[{$attr}="{$key}"] .text-blue-600 { color: {$c['600']} !important; }
                html[{$attr}="{$key}"] .text-blue-700 { color: {$c['700']} !important; }
                html[{$attr}="{$key}"] .hover\:text-blue-600:hover { color: {$c['600']} !important; }
                html[{$attr}="{$key}"].dark .dark\:text-blue-400 { color: {$c['400']} !important; }
                html[{$attr}="{$key}"].dark .dark\:text-blue-500 { color: {$c['500']} !important; }
                html[{$attr}="{$key}"].dark .dark\:hover\:text-blue-400:hover { color: {$c['400']} !important; }
                html[{$attr}="{$key}"] .border-blue-500 { border-color: {$c['500']} !important; }
                html[{$attr}="{$key}"] .focus\:ring-blue-500:focus { --tw-ring-color: {$c['500']} !important; }

                CSS;
        }

        return $css;
    }

    public static function buttonCss(string $attr = 'data-button-theme'): string
    {
        $css = '';
        foreach (self::COLORS as $key => $c) {
            $css .= <<<CSS
                html[{$attr}="{$key}"] .bg-blue-500 { background-color: {$c['500']} !important; }
                html[{$attr}="{$key}"] .bg-blue-600 { background-color: {$c['600']} !important; }
                html[{$attr}="{$key}"] .hover\:bg-blue-700:hover { background-color: {$c['700']} !important; }
                html[{$attr}="{$key}"] .border-blue-600 { border-color: {$c['600']} !important; }
                html[{$attr}="{$key}"] .from-blue-600 { --tw-gradient-from: {$c['gradFrom']} var(--tw-gradient-from-position) !important; }
                html[{$attr}="{$key}"] .to-indigo-600 { --tw-gradient-to: {$c['gradTo']} var(--tw-gradient-to-position) !important; }
                html[{$attr}="{$key}"] .from-blue-400 { --tw-gradient-from: {$c['400']} var(--tw-gradient-from-position) !important; }
                html[{$attr}="{$key}"] .to-blue-600 { --tw-gradient-to: {$c['600']} var(--tw-gradient-to-position) !important; }

                CSS;
        }

        return $css;
    }

    /**
     * Card background is the one role that doesn't reuse "blue" utility classes — cards are
     * `bg-white`/`dark:bg-gray-950` everywhere in the app, so this re-targets *those* instead,
     * tinting them to the chosen color's lightest/darkest shade rather than recoloring blue.
     */
    public static function cardCss(string $attr = 'data-card-theme'): string
    {
        $css = '';
        foreach (self::COLORS as $key => $c) {
            $css .= <<<CSS
                html[{$attr}="{$key}"] .bg-white { background-color: {$c['50']} !important; }
                html[{$attr}="{$key}"].dark .dark\:bg-gray-950 { background-color: {$c['dark']} !important; }

                CSS;
        }

        return $css;
    }
}
