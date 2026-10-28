<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLine extends Model
{
    protected $fillable = ['document_id', 'item_id', 'qty', 'price', 'shelf_price', 'check_action', 'batch_no', 'production_date', 'expiry_date'];

    protected $casts = ['qty' => 'decimal:3', 'price' => 'decimal:2', 'production_date' => 'date', 'expiry_date' => 'date'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
