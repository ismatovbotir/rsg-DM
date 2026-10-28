<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceCheck extends Model
{
    public const CONFIRMED = 'confirmed';
    public const PRINT = 'print';

    protected $fillable = ['item_id', 'user_id', 'document_id', 'price', 'action', 'printed_at'];

    protected $casts = ['price' => 'decimal:2', 'printed_at' => 'datetime'];

    /** Tags waiting to be printed. */
    public function scopeQueued($query)
    {
        return $query->where('action', self::PRINT)->whereNull('printed_at');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
