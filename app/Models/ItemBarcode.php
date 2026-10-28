<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBarcode extends Model
{
    protected $fillable = ['item_id', 'barcode', 'qty_in_pack', 'is_active'];

    protected $casts = ['qty_in_pack' => 'decimal:3', 'is_active' => 'boolean'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
