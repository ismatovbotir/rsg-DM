<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counterparty extends Model
{
    protected $fillable = ['id', 'uuid', 'type', 'name', 'full_name', 'stir', 'phone', 'address', 'is_vat_payer', 'onec_ref', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'is_vat_payer' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Search by name or STIR. */
    public function scopeSearch($query, ?string $q)
    {
        return $query->when(filled($q), fn ($query) => $query->where(function ($w) use ($q) {
            $w->where('name', 'like', "%{$q}%")->orWhere('stir', 'like', "%{$q}%");
        }));
    }

    public function getLabelAttribute(): string
    {
        return $this->name.($this->stir ? " (STIR {$this->stir})" : '');
    }
}
