<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LabelTemplate;

/**
 * Turns a label design + an item into ZPL.
 *
 * Elements are placed in millimetres in the unrotated design. An element rotates around its own centre; the whole label
 * rotates clockwise (0/90/180/270) around the design, so a portrait design can be fed to a landscape stock and the
 * other way round. ^FO is the upper-left corner of the (rotated) field, so for every element we work out the rotated
 * bounding box around the transformed centre.
 */
class ZplLabel
{
    private const ORIENTATION = [0 => 'N', 90 => 'R', 180 => 'I', 270 => 'B'];

    public function render(LabelTemplate $t, Item $item): string
    {
        $dpm = $t->dpi / 25.4; // dots per millimetre
        $W = (float) $t->width_mm;
        $H = (float) $t->height_mm;
        $R = (int) $t->rotation;
        [$pw, $ph] = in_array($R, [90, 270], true) ? [$H, $W] : [$W, $H];
        $dots = fn (float $mm) => max(1, (int) round($mm * $dpm));

        $zpl = ["^XA^CI28^PW{$dots($pw)}^LL{$dots($ph)}^LH0,0"];

        foreach ($t->elements ?? [] as $e) {
            [$box, $rot] = $this->place($e, $W, $H, $R);
            $line = $this->element($e, $box, $rot, $t, $item, $dpm);
            $line && $zpl[] = $line;
        }

        $zpl[] = '^XZ';

        return implode("\n", $zpl);
    }

    /**
     * Final rotated bounding box [x, y, w, h] in mm on the printed label and the final rotation of the element.
     */
    public function place(array $e, float $W, float $H, int $R): array
    {
        $cx = $e['x'] + $e['w'] / 2;
        $cy = $e['y'] + $e['h'] / 2;

        [$cx, $cy] = match ($R) {
            90 => [$H - $cy, $cx],
            180 => [$W - $cx, $H - $cy],
            270 => [$cy, $W - $cx],
            default => [$cx, $cy],
        };

        $rot = ((int) $e['rotation'] + $R) % 360;
        [$bw, $bh] = in_array($rot, [90, 270], true) ? [$e['h'], $e['w']] : [$e['w'], $e['h']];

        return [[$cx - $bw / 2, $cy - $bh / 2, $bw, $bh], $rot];
    }

    private function element(array $e, array $box, int $rot, LabelTemplate $t, Item $item, float $dpm): ?string
    {
        [$x, $y, $w, $h] = array_map(fn ($mm) => (int) round($mm * $dpm), $box);
        $o = self::ORIENTATION[$rot];
        $fo = '^FO'.max(0, $x).','.max(0, $y);

        switch ($e['type']) {
            case 'line':
            case 'box':
                $thick = $e['type'] === 'line' ? max(1, min($w, $h)) : max(1, (int) round($e['border'] * $dpm));

                return "{$fo}^GB{$w},{$h},{$thick}^FS";

            case 'text':
                $text = $this->clean($this->value($e, $item));
                if ($text === '') {
                    return null;
                }
                // The text runs along the element's own width; ^FB wraps and aligns it (rotates together with the field).
                $along = (int) round($e['w'] * $dpm);
                $lines = max(1, (int) floor($e['h'] / ($e['size'] * 1.15)));
                $size = max(1, (int) round($e['size'] * $dpm));

                return "{$fo}^FB{$along},{$lines},0,{$e['align']},0{$this->font($t->font, $o, $size)}^FD{$text}^FS";

            case 'barcode':
                $data = $this->clean($this->value($e + ['source' => 'barcode'], $item));
                if ($data === '') {
                    return null;
                }
                $length = (int) round(($rot % 180 === 0 ? $e['w'] : $e['h']) * $dpm); // bar run along the element's width
                $height = (int) round(($rot % 180 === 0 ? $e['h'] : $e['w']) * $dpm);
                $text = $e['show_text'] ? 'Y' : 'N';

                if ($e['symbology'] === 'ean13' && preg_match('/^\d{12,13}$/', $data)) {
                    $module = max(1, min(10, intdiv($length, 95)));

                    return "^BY{$module},3,{$height}{$fo}^BE{$o},{$height},{$text},N^FD".substr($data, 0, 12).'^FS';
                }

                $module = max(1, min(10, intdiv($length, 11 * (strlen($data) + 2) + 13)));

                return "^BY{$module},3,{$height}{$fo}^BC{$o},{$height},{$text},N,N^FD{$data}^FS";

            case 'qr':
                $data = $this->clean($this->value($e + ['source' => 'barcode'], $item));
                if ($data === '') {
                    return null;
                }
                $mag = max(1, min(10, intdiv((int) round(min($e['w'], $e['h']) * $dpm), 33)));

                return "{$fo}^BQN,2,{$mag}^FDQA,{$data}^FS";
        }

        return null;
    }

    /** Text of an element for this item. */
    public function value(array $e, Item $item): string
    {
        $suffix = (string) ($e['text'] ?? '');

        return match ($e['source']) {
            'name' => $item->name,
            'price' => number_format((float) $item->price, 0, '.', ' ').$suffix,
            'code' => (string) $item->code,
            'article' => (string) $item->article,
            'barcode' => (string) ($item->barcode ?: $item->code),
            'unit' => (string) $item->unit,
            default => $suffix,
        };
    }

    /** Built-in scalable font "0", or a font file stored in the printer (E:ARIAL.TTF) for Cyrillic / Uzbek letters. */
    private function font(string $font, string $orientation, int $height): string
    {
        return preg_match('/^[A-Za-z]:[\w.\-]+$/', $font)
            ? "^A@{$orientation},{$height},0,{$font}"
            : "^A0{$orientation},{$height},0";
    }

    /** ^ and ~ are ZPL control characters; line breaks would end the field. */
    private function clean(string $text): string
    {
        return trim(str_replace(['^', '~', "\r", "\n"], ' ', $text));
    }
}
