<?php

namespace App\Support;

use App\Models\PriceType;

/** Settings of a price checker screen (a CheckerTheme): colours, pictures, blocks, texts, rotation. Always read through merge()/sanitize(). */
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
    public const IMAGE_POS = ['left' => 'Chapda', 'right' => 'O‘ngda'];
    public const ARROWS = ['none' => 'Yo‘q', 'up' => '↑ Yuqoriga', 'down' => '↓ Pastga', 'left' => '← Chapga', 'right' => '→ O‘ngga'];

    public const COLOR_LABELS = [
        'bg' => 'Fon', 'text' => 'Matn', 'name_color' => 'Tovar nomi', 'price_color' => 'Narx', 'old_color' => 'Eski narx',
        'badge_bg' => 'Aksiya belgisi', 'badge_text' => 'Belgi matni', 'card_bg' => 'Ma’lumot kartasi', 'error_color' => 'Xato xabari',
        'bar_bg' => 'Yuqori/pastki panel', 'bar_text' => 'Panel matni',
    ];

    /** Max length of each text setting. */
    public const TEXT_MAX = [
        'header_text' => 80, 'footer_text' => 80, 'hint_text' => 120, 'not_found_text' => 120, 'not_found_hint' => 120,
        'currency_text' => 20, 'promo_text' => 30, 'second_price_label' => 40, 'auth_title' => 80, 'auth_text' => 200,
    ];

    /**
     * Ready colour sets (order = COLOR_LABELS: bg, text, name, price, old price, badge bg, badge text, card, error, bar bg, bar text).
     * All keep strong text/background contrast for reading from 1-2 m.
     */
    public const PALETTES = [
        'rsg' => ['RSG (logotip rangi)', '#e51932', '#ffffff', '#ffffff', '#ffffff', '#ffd0d6', '#ffffff', '#e51932', '#b8102a', '#ffffff', '#b8102a', '#ffd9de'],
        'rsg_dark' => ['RSG qora + qizil', '#17181c', '#ffffff', '#ffffff', '#ff4b61', '#9aa0aa', '#e51932', '#ffffff', '#24262b', '#ff8a96', '#e51932', '#ffffff'],
        'navy_gold' => ['Tungi ko‘k + oltin', '#10253f', '#ffffff', '#ffffff', '#ffd24a', '#ff9e94', '#d32f2f', '#ffffff', '#0b1b30', '#ffb4a8', '#0b1b30', '#c9d6e8'],
        'black_yellow' => ['Qora + sariq', '#111111', '#ffffff', '#ffffff', '#ffe600', '#ff8a80', '#e53935', '#ffffff', '#000000', '#ff8a80', '#000000', '#bdbdbd'],
        'white_red' => ['Oq + qizil', '#ffffff', '#1b1b1b', '#1b1b1b', '#d32f2f', '#757575', '#d32f2f', '#ffffff', '#f3f3f3', '#c62828', '#d32f2f', '#ffffff'],
        'white_blue' => ['Oq + ko‘k', '#f5f8fc', '#13233a', '#13233a', '#0d47a1', '#6b7a90', '#e53935', '#ffffff', '#ffffff', '#c62828', '#0d47a1', '#ffffff'],
        'forest' => ['To‘q yashil + sariq', '#0b3d2e', '#ffffff', '#ffffff', '#ffe082', '#ffab91', '#e53935', '#ffffff', '#07291f', '#ffab91', '#07291f', '#b8e0cf'],
        'white_green' => ['Oq + yashil', '#f1f8f2', '#12301c', '#12301c', '#1b7a34', '#6d7f72', '#e53935', '#ffffff', '#ffffff', '#c62828', '#1b7a34', '#ffffff'],
        'orange' => ['Apelsin', '#e65100', '#ffffff', '#ffffff', '#fff176', '#ffe0b2', '#b71c1c', '#ffffff', '#bf4300', '#fff3e0', '#bf4300', '#ffe0b2'],
        'purple' => ['Binafsha + oltin', '#2a1457', '#ffffff', '#ffffff', '#ffd54f', '#ffab91', '#e91e63', '#ffffff', '#1d0e3d', '#ffab91', '#1d0e3d', '#d1c4e9'],
        'red' => ['Qizil + sariq', '#b71c1c', '#ffffff', '#ffffff', '#ffeb3b', '#ffcdd2', '#ffeb3b', '#b71c1c', '#8e1515', '#ffe0b2', '#8e1515', '#ffcdd2'],
        'teal' => ['Turkuaz', '#004d5c', '#ffffff', '#ffffff', '#ffe082', '#ffab91', '#ff5252', '#ffffff', '#003845', '#ffab91', '#003845', '#b2ebf2'],
        'graphite' => ['Grafit + moviy', '#263238', '#eceff1', '#ffffff', '#80deea', '#ff8a80', '#ef5350', '#ffffff', '#1c2529', '#ff8a80', '#1c2529', '#b0bec5'],
        'light_grey' => ['Och kulrang + yashil', '#eceff1', '#263238', '#263238', '#00695c', '#78909c', '#e53935', '#ffffff', '#ffffff', '#c62828', '#455a64', '#ffffff'],
        'coffee' => ['Qahva', '#3e2723', '#fff8e1', '#fff8e1', '#ffca28', '#ffab91', '#e53935', '#ffffff', '#2b1b18', '#ffab91', '#2b1b18', '#d7ccc8'],
        'cream' => ['Krem + qizil', '#fff8e7', '#3e2f1c', '#3e2f1c', '#c62828', '#8d7b68', '#c62828', '#ffffff', '#ffffff', '#b71c1c', '#6d4c41', '#fff8e1'],
        'neon_green' => ['Qora + neon yashil', '#0a0a0a', '#ffffff', '#ffffff', '#76ff03', '#ff8a80', '#ff1744', '#ffffff', '#161616', '#ff8a80', '#161616', '#9e9e9e'],
        'black_orange' => ['Qora + to‘q sariq', '#101010', '#ffffff', '#ffffff', '#ff9800', '#ff8a80', '#e53935', '#ffffff', '#1c1c1c', '#ffab91', '#1c1c1c', '#bdbdbd'],
        'royal' => ['Moviy + sariq', '#0d47a1', '#ffffff', '#ffffff', '#ffeb3b', '#ffcdd2', '#e53935', '#ffffff', '#0a3880', '#ffcdd2', '#082c66', '#bbdefb'],
        'pink' => ['Pushti', '#fce4ec', '#4a1030', '#4a1030', '#ad1457', '#8e6b7b', '#ad1457', '#ffffff', '#ffffff', '#c62828', '#ad1457', '#ffffff'],
        'olive' => ['Zaytun', '#2f3b17', '#f7f9ea', '#f7f9ea', '#d4e157', '#ffab91', '#e53935', '#ffffff', '#222c10', '#ffab91', '#222c10', '#d9e2b0'],
        'contrast' => ['Yuqori kontrast', '#000000', '#ffffff', '#ffffff', '#ffff00', '#ff6e6e', '#ffff00', '#000000', '#000000', '#ff6e6e', '#222222', '#ffffff'],
    ];

    /** @return array<string, string>|null colour settings of a palette */
    public static function palette(string $key): ?array
    {
        $row = self::PALETTES[$key] ?? null;

        return $row ? array_combine(array_keys(self::COLOR_LABELS), array_slice($row, 1)) : null;
    }

    public const IDS = ['old_price_type_id', 'second_price_type_id'];

    public const DEFAULTS = [
        'layout' => 'stacked', 'rotation' => 0, 'font' => 'system', 'align' => 'center',
        'image_pos' => 'left', 'scan_arrow' => 'none',
        'name_scale' => 100, 'price_scale' => 100, 'logo_scale' => 100, 'card_opacity' => 0, 'bg_dim' => 0, 'stock_low' => 5, 'timeout' => 10,
        'bg_image' => '', 'logo' => '', 'banners' => [], 'banner_interval' => 6,
        'old_price_type_id' => null, 'second_price_type_id' => null,
        // colours
        'bg' => '#e51932', 'text' => '#ffffff', 'name_color' => '#ffffff', 'price_color' => '#ffffff', 'old_color' => '#ffd0d6',
        'badge_bg' => '#ffffff', 'badge_text' => '#e51932', 'card_bg' => '#b8102a', 'error_color' => '#ffffff',
        'bar_bg' => '#b8102a', 'bar_text' => '#ffd9de',
        // blocks
        'show_header' => true, 'show_footer' => true, 'show_logo' => true, 'show_clock' => false,
        'show_image' => true, 'show_unit' => true, 'show_stock' => false,
        'show_code' => false, 'show_article' => false, 'show_barcode' => false, 'show_group' => false,
        // texts
        'header_text' => '', 'footer_text' => '', 'currency_text' => 'so‘m',
        'hint_text' => 'Tovar shtrix-kodini skanerlang', 'not_found_text' => 'Tovar topilmadi',
        'not_found_hint' => 'Narx yorlig‘iga qarang yoki xodimni chaqiring',
        'promo_text' => 'Aksiya', 'second_price_label' => 'Karta bilan',
        'auth_title' => 'Terminalni faollashtiring', 'auth_text' => 'Boshqaruv panelidagi «Narx tekshirgichlar» bo‘limida ko‘rsatilgan QR kodni skanerlang.',
    ];

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
        $text = fn ($v, $default, $max) => is_string($v) ? mb_substr(trim(strip_tags($v)), 0, $max) : $default;
        $pick = fn ($v, array $allowed, $default) => is_string($v) && array_key_exists($v, $allowed) ? $v : $default;
        $path = fn ($v) => is_string($v) && preg_match('#^(checker|items)/[A-Za-z0-9._-]+$#', $v) ? $v : '';
        $typeId = fn ($v) => is_numeric($v) && PriceType::whereKey((int) $v)->exists() ? (int) $v : null;

        $out = [
            'layout' => $pick($in['layout'] ?? null, self::LAYOUTS, $d['layout']),
            'rotation' => in_array((int) ($in['rotation'] ?? 0), [0, 90, 180, 270], true) ? (int) ($in['rotation'] ?? 0) : 0,
            'font' => $pick($in['font'] ?? null, self::FONTS, $d['font']),
            'align' => $pick($in['align'] ?? null, self::ALIGNS, $d['align']),
            'image_pos' => $pick($in['image_pos'] ?? null, self::IMAGE_POS, $d['image_pos']),
            'scan_arrow' => $pick($in['scan_arrow'] ?? null, self::ARROWS, $d['scan_arrow']),
            'name_scale' => $int($in['name_scale'] ?? null, 50, 200, 100),
            'price_scale' => $int($in['price_scale'] ?? null, 50, 200, 100),
            'logo_scale' => $int($in['logo_scale'] ?? null, 50, 250, 100),
            'card_opacity' => $int($in['card_opacity'] ?? null, 0, 100, 0),
            'bg_dim' => $int($in['bg_dim'] ?? null, 0, 90, 0),
            'stock_low' => $int($in['stock_low'] ?? null, 1, 1000, 5),
            'timeout' => $int($in['timeout'] ?? null, 3, 60, 10),
            'banner_interval' => $int($in['banner_interval'] ?? null, 2, 60, 6),
            'banners' => array_slice(array_values(array_unique(array_filter(array_map($path, is_array($in['banners'] ?? null) ? $in['banners'] : [])))), 0, 8),
            'bg_image' => $path($in['bg_image'] ?? ''),
            'logo' => $path($in['logo'] ?? ''),
        ];

        foreach (array_keys(self::COLOR_LABELS) as $key) {
            $out[$key] = $color($in[$key] ?? null, $d[$key]);
        }
        foreach (array_filter($d, 'is_bool') as $key => $default) {
            $out[$key] = filter_var($in[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
        }
        foreach (self::IDS as $key) {
            $out[$key] = $typeId($in[$key] ?? null);
        }
        foreach (self::TEXT_MAX as $key => $max) {
            $default = $d[$key];
            $value = $text($in[$key] ?? null, $default, $max);
            // an emptied text falls back to its default, except the optional bar texts and the second price label
            $out[$key] = $value === '' && ! in_array($key, ['header_text', 'footer_text', 'currency_text'], true) ? $default : $value;
        }

        return $out;
    }

    public static function imageUrl(?string $path): ?string
    {
        return $path ? asset('uploads/'.$path) : null;
    }

    /** CSS variables of a ready colour set only (the price checker applies them over the theme for items of a group with a scheme). */
    public static function schemeVars(?string $key, int $cardOpacity): string
    {
        if (! $key || ! ($c = self::palette($key))) {
            return '';
        }

        [$r, $g, $b] = sscanf($c['card_bg'], '#%02x%02x%02x');

        return implode(';', [
            '--bg:'.$c['bg'], '--text:'.$c['text'], '--name:'.$c['name_color'], '--price:'.$c['price_color'], '--err:'.$c['error_color'],
            '--old:'.$c['old_color'], '--badge-bg:'.$c['badge_bg'], '--badge-text:'.$c['badge_text'],
            '--card:rgba('.$r.','.$g.','.$b.','.($cardOpacity / 100).')', '--bar-bg:'.$c['bar_bg'], '--bar-text:'.$c['bar_text'],
        ]);
    }

    /** CSS custom properties of the screen. Only validated colours / numbers / font stacks end up here. */
    public static function cssVars(array $d): string
    {
        [$r, $g, $b] = sscanf($d['card_bg'], '#%02x%02x%02x');
        $vars = [
            '--bg:'.$d['bg'], '--text:'.$d['text'], '--name:'.$d['name_color'], '--price:'.$d['price_color'], '--err:'.$d['error_color'],
            '--old:'.$d['old_color'], '--badge-bg:'.$d['badge_bg'], '--badge-text:'.$d['badge_text'],
            '--card:rgba('.$r.','.$g.','.$b.','.($d['card_opacity'] / 100).')',
            '--bar-bg:'.$d['bar_bg'], '--bar-text:'.$d['bar_text'],
            '--name-scale:'.($d['name_scale'] / 100), '--price-scale:'.($d['price_scale'] / 100), '--logo-scale:'.($d['logo_scale'] / 100),
            '--dim:'.($d['bg_dim'] / 100),
            '--font:'.self::FONTS[$d['font']][1], '--align:'.$d['align'], '--chips-just:'.['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$d['align']],
        ];

        if ($url = self::imageUrl($d['bg_image'])) {
            $vars[] = "--bg-image:url('".$url."')";
        }

        return implode(';', $vars);
    }
}
