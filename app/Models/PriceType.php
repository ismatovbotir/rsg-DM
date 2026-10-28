<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A price list (e.g. retail, wholesale). Deactivated, never deleted: documents reference it. */
class PriceType extends Model
{
    protected $fillable = ['id', 'code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
