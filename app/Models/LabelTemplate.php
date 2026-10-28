<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A price tag design: label size, whole-label rotation and a list of elements placed in millimetres.
 * Element: type (text|barcode|qr|line|box), source (name|price|code|article|barcode|unit|static), x, y, w, h in mm
 * (before rotation), size (text height, mm), align (L|C|R), rotation (0|90|180|270, around the element centre).
 */
class LabelTemplate extends Model
{
    public const TYPES = ['text', 'barcode', 'qr', 'line', 'box'];
    public const SOURCES = ['name', 'price', 'code', 'article', 'barcode', 'unit', 'static'];
    public const ROTATIONS = [0, 90, 180, 270];
    public const MAX_ELEMENTS = 40;

    protected $fillable = ['name', 'width_mm', 'height_mm', 'rotation', 'dpi', 'font', 'elements', 'is_default'];

    protected $casts = ['elements' => 'array', 'width_mm' => 'float', 'height_mm' => 'float', 'rotation' => 'integer', 'dpi' => 'integer', 'is_default' => 'boolean'];

    /** The design used for queued price tags, if one is chosen. */
    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public function makeDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /** A starting design: name on top, big price, barcode at the bottom. */
    public static function starter(): array
    {
        return [
            ['type' => 'text', 'source' => 'name', 'x' => 2, 'y' => 2, 'w' => 54, 'h' => 10, 'size' => 3.5, 'align' => 'L', 'rotation' => 0],
            ['type' => 'text', 'source' => 'price', 'text' => ' so‘m', 'x' => 2, 'y' => 13, 'w' => 54, 'h' => 10, 'size' => 8, 'align' => 'R', 'rotation' => 0],
            ['type' => 'barcode', 'source' => 'barcode', 'symbology' => 'code128', 'show_text' => true, 'x' => 4, 'y' => 26, 'w' => 50, 'h' => 11, 'size' => 2.5, 'align' => 'C', 'rotation' => 0],
        ];
    }

    /** Whitelist and clamp whatever the browser sent, so a broken design cannot produce a broken label. */
    public static function sanitize(mixed $raw): array
    {
        $num = fn ($v, float $min, float $max, float $default) => is_numeric($v) ? max($min, min($max, round((float) $v, 2))) : $default;
        $out = [];

        foreach (array_slice(is_array($raw) ? $raw : [], 0, self::MAX_ELEMENTS) as $e) {
            if (! is_array($e) || ! in_array($e['type'] ?? null, self::TYPES, true)) {
                continue;
            }

            $rotation = (int) ($e['rotation'] ?? 0);

            $out[] = [
                'type' => $e['type'],
                'source' => in_array($e['source'] ?? null, self::SOURCES, true) ? $e['source'] : 'static',
                'text' => mb_substr(str_replace(['^', '~', "\r", "\n"], ' ', (string) ($e['text'] ?? '')), 0, 200),
                'x' => $num($e['x'] ?? 0, -200, 400, 0),
                'y' => $num($e['y'] ?? 0, -200, 400, 0),
                'w' => $num($e['w'] ?? 20, 0.2, 400, 20),
                'h' => $num($e['h'] ?? 6, 0.2, 400, 6),
                'size' => $num($e['size'] ?? 3, 1, 60, 3),
                'align' => in_array($e['align'] ?? 'L', ['L', 'C', 'R'], true) ? ($e['align'] ?? 'L') : 'L',
                'rotation' => in_array($rotation, self::ROTATIONS, true) ? $rotation : 0,
                'symbology' => in_array($e['symbology'] ?? null, ['code128', 'ean13'], true) ? $e['symbology'] : 'code128',
                'show_text' => (bool) ($e['show_text'] ?? false),
                'border' => $num($e['border'] ?? 0.3, 0.1, 5, 0.3),
            ];
        }

        return $out;
    }
}
