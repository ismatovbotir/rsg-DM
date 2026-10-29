<?php

namespace Database\Seeders;

use App\Models\CheckerTheme;
use App\Support\CheckerDesign;
use Illuminate\Database\Seeder;

/** The sample price checker design for a 10-inch landscape screen (1280x800). Only created when there is no theme yet. */
class CheckerThemeSeeder extends Seeder
{
    public function run(): void
    {
        if (CheckerTheme::exists()) {
            return;
        }

        CheckerTheme::create([
            'name' => 'Standart 10 dyuym (1280x800)',
            'is_default' => true,
            'design' => CheckerDesign::sanitize([
                'layout' => 'stacked', 'rotation' => 0, 'font' => 'system', 'align' => 'left', 'image_pos' => 'left', 'scan_arrow' => 'down',
                'bg' => '#10253f', 'text' => '#ffffff', 'name_color' => '#ffffff', 'price_color' => '#ffd24a', 'old_color' => '#ff9e94',
                'badge_bg' => '#d32f2f', 'badge_text' => '#ffffff', 'card_bg' => '#0b1b30', 'card_opacity' => 85,
                'bar_bg' => '#0b1b30', 'bar_text' => '#c9d6e8',
                'name_scale' => 100, 'price_scale' => 100, 'timeout' => 10,
                'show_header' => true, 'show_footer' => true, 'show_logo' => true, 'show_clock' => true,
                'show_image' => true, 'show_unit' => true, 'show_stock' => true, 'show_code' => true, 'show_barcode' => true,
                'header_text' => 'Narxni tekshiring', 'footer_text' => '',
                'hint_text' => 'Tovar shtrix-kodini skanerga tutib turing',
            ]),
        ]);
    }
}
