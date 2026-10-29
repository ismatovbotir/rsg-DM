<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['id', 'code', 'name', 'price_type_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function priceType()
    {
        return $this->belongsTo(PriceType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
