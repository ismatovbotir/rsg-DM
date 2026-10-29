<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['id', 'uuid', 'unit_main_id', 'unit_order_id', 'unit_report_id', 'code', 'article', 'barcode', 'name', 'unit', 'price', 'onec_ref', 'is_weighed', 'vat_rate', 'shelf_life_days', 'group_id', 'is_active', 'image'];

    protected $casts = ['price' => 'decimal:2', 'vat_rate' => 'decimal:2', 'shelf_life_days' => 'integer', 'is_weighed' => 'boolean', 'is_active' => 'boolean'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function unitMain()
    {
        return $this->belongsTo(Unit::class, 'unit_main_id');
    }

    public function barcodes()
    {
        return $this->hasMany(ItemBarcode::class);
    }

    public function stocks()
    {
        return $this->hasMany(ItemStock::class);
    }

    public function prices()
    {
        return $this->hasMany(ItemPrice::class);
    }

    /** Price in a price list; falls back to the base price when the list has no row for this item. */
    public function priceFor(?int $priceTypeId): string
    {
        $row = $priceTypeId ? $this->prices()->where('price_type_id', $priceTypeId)->first() : null;

        return $row ? $row->price : $this->price;
    }

    /** Sets ->shop_price on each item: its price in the price list (base price when the list has none). One query for the batch. */
    public static function withShopPrices($items, ?int $priceTypeId)
    {
        $rows = $priceTypeId ? ItemPrice::where('price_type_id', $priceTypeId)->whereIn('item_id', $items->pluck('id'))->pluck('price', 'item_id') : collect();

        foreach ($items as $item) {
            $item->shop_price = $rows[$item->id] ?? $item->price;
        }

        return $items;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Search by name, code, article (artikul) or any barcode. */
    public function scopeSearch($query, ?string $q)
    {
        return $query->when(filled($q), fn ($query) => $query->where(function ($w) use ($q) {
            $w->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->orWhere('article', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")
                ->orWhereHas('barcodes', fn ($b) => $b->where('barcode', 'like', "%{$q}%"));
        }));
    }

    /**
     * Resolve a scanned/typed code to [Item, base-unit quantity], or null.
     * A pack barcode returns its qty_in_pack (a case of 12 books 12), anything else returns 1.
     */
    public static function resolve(string $code): ?array
    {
        $item = static::active()
            ->where(fn ($q) => $q->where('barcode', $code)->orWhere('code', $code)->orWhere('article', $code))
            ->first();

        if ($item) {
            return [$item, 1.0];
        }

        $pack = ItemBarcode::with('item')->where('barcode', $code)->where('is_active', true)->first();

        return $pack && $pack->item->is_active ? [$pack->item, (float) $pack->qty_in_pack] : null;
    }
}
