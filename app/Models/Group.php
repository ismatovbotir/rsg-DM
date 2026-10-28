<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Product group tree: parent_id is null for first-level groups. Items belong to a group. */
class Group extends Model
{
    protected $fillable = ['id', 'parent_id', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /** "Oziq-ovqat / Sut mahsulotlari / Sut" */
    public function getPathAttribute(): string
    {
        $names = [$this->name];
        $node = $this;

        for ($depth = 0; $node->parent && $depth < 20; $depth++) { // depth cap guards against a bad cycle
            $node = $node->parent;
            array_unshift($names, $node->name);
        }

        return implode(' / ', $names);
    }
}
