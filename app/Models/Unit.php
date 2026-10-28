<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Unit of measure sent by Adines (piece, kg, pack...). qty = how many base units it holds. */
class Unit extends Model
{
    protected $fillable = ['uuid', 'code', 'name', 'qty', 'weight', 'width', 'length', 'height', 'is_stackable', 'shape'];

    protected $casts = ['qty' => 'decimal:3', 'is_stackable' => 'boolean'];
}
