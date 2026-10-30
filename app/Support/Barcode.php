<?php

namespace App\Support;

/** Code 128 (subset B) as an inline SVG, for the price checker screen. Scannable by any reader; the digits are shown by the caller. */
class Barcode
{
    /** Bar/space widths of the symbols 0..105 (start A/B/C = 103..105) and the stop (106). */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public static function svg(string $code, int $height = 60): string
    {
        $code = trim($code);
        if ($code === '' || preg_match('/[^\x20-\x7E]/', $code)) {
            return '';
        }

        $symbols = [104]; // start B
        $sum = 104;
        foreach (str_split($code) as $i => $char) {
            $value = ord($char) - 32;
            $symbols[] = $value;
            $sum += $value * ($i + 1);
        }
        $symbols[] = $sum % 103;
        $symbols[] = 106;

        $x = 10; // quiet zone
        $bars = '';
        foreach ($symbols as $symbol) {
            $isBar = true;
            foreach (str_split(self::PATTERNS[$symbol]) as $width) {
                if ($isBar) {
                    $bars .= '<rect x="'.$x.'" y="0" width="'.$width.'" height="'.$height.'"/>';
                }
                $x += (int) $width;
                $isBar = ! $isBar;
            }
        }
        $width = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'" preserveAspectRatio="none" class="pcs-bars" role="img" aria-label="'.e($code).'"><rect width="'.$width.'" height="'.$height.'" fill="#fff"/><g fill="#000">'.$bars.'</g></svg><div class="pcs-bartext">'.e($code).'</div>';
    }
}
