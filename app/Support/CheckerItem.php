<?php

namespace App\Support;

use App\Models\Item;

/** What the price checker screen shows for one item (kiosk JSON and admin preview share it). */
class CheckerItem
{
    public static function money(float $value): string
    {
        return number_format($value, 0, '.', ' ');
    }

    /** @return array<string, mixed> */
    public static function payload(Item $item, array $design, ?int $priceTypeId): array
    {
        $price = (float) $item->priceFor($priceTypeId);

        // promo: the "old" price list holds a higher regular price than the one shown
        $old = $design['old_price_type_id'] ? (float) $item->priceFor($design['old_price_type_id']) : 0;
        $second = $design['second_price_type_id'] ? (float) $item->priceFor($design['second_price_type_id']) : 0;
        $hasPromo = $old > $price && $price > 0;
        $hasSecond = $second > 0 && abs($second - $price) > 0.0001;

        return [
            'found' => true,
            'name' => $item->name,
            'unit' => $item->unit,
            'code' => (string) $item->code,
            'article' => (string) $item->article,
            'barcode' => (string) $item->barcode,
            'group' => (string) ($item->group?->name ?? ''),
            'image' => self::imageUrlOf($item),
            'price' => $price,
            'price_text' => self::money($price),
            'old_price_text' => $hasPromo ? self::money($old) : '',
            'promo' => $hasPromo,
            'second_price_text' => $hasSecond ? self::money($second) : '',
            'stock' => self::stock($item, $design),
            // the colour set of the item's group (own or inherited); empty = the theme's colours
            'scheme_vars' => CheckerDesign::schemeVars($item->group?->effectiveColorScheme(), $design['card_opacity']),
        ];
    }

    /** ['state' => ok|low|out, 'text' => ...] or empty when the item has no stock rows at all (nothing to claim). Never the exact quantity. */
    public static function stock(Item $item, array $design): array
    {
        if (! $design['show_stock'] || ! $item->stocks()->exists()) {
            return ['state' => '', 'text' => ''];
        }

        $qty = (float) $item->stocks()->sum('qty');

        return match (true) {
            $qty <= 0 => ['state' => 'out', 'text' => 'Tugagan'],
            $qty <= $design['stock_low'] => ['state' => 'low', 'text' => 'Oz qoldi'],
            default => ['state' => 'ok', 'text' => 'Mavjud'],
        };
    }

    public static function imageUrlOf(Item $item): string
    {
        return (string) CheckerDesign::imageUrl($item->image);
    }
}
