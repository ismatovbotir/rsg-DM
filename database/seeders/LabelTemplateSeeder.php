<?php

namespace Database\Seeders;

use App\Models\LabelTemplate;
use Illuminate\Database\Seeder;

/** The default price tag design (58 x 40 mm): name, big price, barcode with its number, item code. Only created when there is no design yet. */
class LabelTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (LabelTemplate::exists()) {
            return;
        }

        LabelTemplate::create([
            'name' => 'Standart 58x40',
            'width_mm' => 58,
            'height_mm' => 40,
            'rotation' => 0,
            'dpi' => 203,
            'font' => '0',
            'is_default' => true,
            'elements' => LabelTemplate::sanitize([
                ['type' => 'box', 'x' => 0.5, 'y' => 0.5, 'w' => 57, 'h' => 39, 'border' => 0.3],
                ['type' => 'text', 'source' => 'name', 'x' => 2, 'y' => 2, 'w' => 54, 'h' => 9, 'size' => 3.5, 'align' => 'L'],
                ['type' => 'text', 'source' => 'price', 'text' => ' so‘m', 'x' => 2, 'y' => 12, 'w' => 54, 'h' => 9, 'size' => 7.5, 'align' => 'R'],
                ['type' => 'barcode', 'source' => 'barcode', 'symbology' => 'code128', 'show_text' => true, 'x' => 4, 'y' => 23, 'w' => 50, 'h' => 12, 'size' => 2.5, 'align' => 'C'],
                ['type' => 'text', 'source' => 'code', 'x' => 2, 'y' => 35.5, 'w' => 30, 'h' => 3.5, 'size' => 2.5, 'align' => 'L'],
                ['type' => 'text', 'source' => 'unit', 'x' => 40, 'y' => 35.5, 'w' => 16, 'h' => 3.5, 'size' => 2.5, 'align' => 'R'],
            ]),
        ]);
    }
}
