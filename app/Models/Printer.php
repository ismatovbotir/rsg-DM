<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A label printer on the LAN. Exactly one may be the price-tag printer; PDAs can have their own assigned printer. */
class Printer extends Model
{
    public const CONNECTIONS = ['lan' => 'LAN (tarmoq)', 'bluetooth' => 'Bluetooth (telefon / PDA)'];

    protected $fillable = ['name', 'kind', 'host', 'port', 'label_width_mm', 'label_height_mm', 'is_price_tag', 'is_active', 'bt_name', 'bt_address', 'owner_device_id'];

    protected $casts = ['is_price_tag' => 'boolean', 'is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Printers the administrator manages (not a PDA's own printer). */
    public function scopeShared($query)
    {
        return $query->whereNull('owner_device_id');
    }

    /** The printer that prints price tags, if one is chosen and active. */
    public static function priceTag(): ?self
    {
        return static::active()->where('is_price_tag', true)->first();
    }

    /** Make this the only price-tag printer. */
    public function makePriceTag(): void
    {
        static::where('id', '!=', $this->id)->update(['is_price_tag' => false]);
        $this->update(['is_price_tag' => true]);
    }

    public function isBluetooth(): bool
    {
        return $this->kind === 'bluetooth';
    }

    public function getAddressAttribute(): string
    {
        return $this->isBluetooth() ? ($this->bt_name ?: 'Bluetooth') : "{$this->host}:{$this->port}";
    }
}
