<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemStock extends Model
{
    const CREATED_AT = null;

    protected $fillable = ['item_id', 'warehouse_id', 'qty', 'cost', 'stock_date'];

    protected $casts = ['qty' => 'decimal:3'];
}
