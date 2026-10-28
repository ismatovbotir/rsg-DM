<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPrice extends Model
{
    public $timestamps = false;

    protected $fillable = ['item_id', 'price_type_id', 'price'];

    protected $casts = ['price' => 'decimal:2'];
}
