<?php

namespace App\Support;

/** Settings of a price checker screen: colours, texts, sizes, layout, rotation. Always read through merge()/sanitize(). */
class CheckerDesign
{
    public const FONTS = [
        'system' => ['Tizim shrifti', 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'],
        'rounded' => ['Yumaloq', '"Trebuchet MS", "Segoe UI", Roboto, sans-serif'],
        'serif' => ['Serif', 'Georgia, "Times New Roman", serif'],
        'mono' => ['Mono', 'ui-monospace, Consolas, "Courier New", monospace'],
    ];

    public const LAYOUTS = ['stacked' => 'Ustma-ust (nom tepada, narx ostida)', 'split' => 'Ikki ustun (nom chapda, narx o‘ngda)'];
    public const ALIGNS = ['left' => 'Chapga', 'center' => 'Markazga', 'right' => 'O‘ngga'];

    public const DEFAULTS = [
        'layout' => 'stacked',
        'rotation' => 0,
        'font' => 'system',
        'align' => 'center',
        'bg' => '#1f3a5f',
        'text' => '#ffffff',
        'name_color' => '#ffffff',
        'price_color' => '#ffd24a',
        'error_color' => '#ffb4a8',
        'bar_bg' => '#162b47',
        'bar_text' => '#c9d6e8',
        'name_scale' => 100,
        'price_scale' => 100,
        'show_header' => true,
        'show_footer' => true,
        'header_text' => '',
        'footer_text' => '',
        'hint_text' => 'Tovar shtrix-kodini skanerlang',
        'not_found_text' => 'Tovar topilmadi',
        'currency_text' => 'so‘m',
        'show_unit' => true,
        'show_code' => false,
        'timeout' => 8,
    ];

    /** Saved settings on top of the defaults; unknown keys are dropped. */
    public static function merge(?array $saved): array
    {
        return self::sanitize(array_replace(self::DEFAULTS, array_intersect_key($saved ?? [], self::DEFAULTS)));
    }

    /** Clamp whatever the browser sent to values the screen can safely render. */
    public static function sanitize(array $in): array
    {
        $d = self::DEFAULTS;
        $color = fn ($v, $default) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $default;
        $int = fn ($v, $min, $max, $default) => is_numeric($v) ? (int) max($min, min($max, round((float) $v))) : $default;
        $text = fn ($v, $default, $max = 120) => is_string($v) ? mb_substr(trim(strip_tags($v)), 0, $max) : $default;

        return [
            'layout' => array_key_exists($in['layout'] ?? '', self::LAYOUTS) ? $in['layout'] : $d['layout'],
            'rotation' => in_array((int) ($in['rotation'] ?? 0), [0, 90, 180, 270], true) ? (int) $in['rotation'] : 0,
            'font' => array_key_exists($in['font'] ?? '', self::FONTS) ? $in['font'] : $d['font'],
            'align' => array_key_exists($in['align'] ?? '', self::ALIGNS) ? $in['align'] : $d['align'],
            'bg' => $color($in['bg'] ?? null, $d['bg']),
            'text' => $color($in['text'] ?? null, $d['text']),
            'name_color' => $color($in['name_color'] ?? null, $d['name_color']),
            'price_color' => $color($in['price_color'] ?? null, $d['price_color']),
            'error_color' => $color($in['error_color'] ?? null, $d['error_color']),
            'bar_bg' => $color($in['bar_bg'] ?? null, $d['bar_bg']),
            'bar_text' => $color($in['bar_text'] ?? null, $d['bar_text']),
            'name_scale' => $int($in['name_scale'] ?? null, 50, 200, 100),
            'price_scale' => $int($in['price_scale'] ?? null, 50, 200, 100),
            'show_header' => filter_var($in['show_header'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'show_footer' => filter_var($in['show_footer'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'header_text' => $text($in['header_text'] ?? '', '', 80),
            'footer_text' => $text($in['footer_text'] ?? '', '', 80),
            'hint_text' => $text($in['hint_text'] ?? '', $d['hint_text']) ?: $d['hint_text'],
            'not_found_text' => $text($in['not_found_text'] ?? '', $d['not_found_text']) ?: $d['not_found_text'],
            'currency_text' => $text($in['currency_text'] ?? '', $d['currency_text'], 20),
            'show_unit' => filter_var($in['show_unit'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'show_code' => filter_var($in['show_code'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'timeout' => $int($in['timeout'] ?? null, 3, 60, 8),
        ];
    }

    /** CSS custom properties of the screen. Only validated colours / numbers / font stacks end up here. */
    public static function cssVars(array $d): string
    {
        return implode(';', [
            '--bg:'.$d['bg'], '--text:'.$d['text'], '--name:'.$d['name_color'], '--price:'.$d['price_color'], '--err:'.$d['error_color'],
            '--bar-bg:'.$d['bar_bg'], '--bar-text:'.$d['bar_text'],
            '--name-scale:'.($d['name_scale'] / 100), '--price-scale:'.($d['price_scale'] / 100),
            '--font:'.self::FONTS[$d['font']][1], '--align:'.$d['align'],
        ]);
    }
}
