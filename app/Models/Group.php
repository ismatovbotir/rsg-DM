<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Product group tree: parent_id is null for first-level groups. Items belong to a group. */
class Group extends Model
{
    protected $fillable = ['id', 'parent_id', 'name', 'is_active', 'color_scheme'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Colour set key for the price checker: this group's own, else the nearest parent's, else null (the theme's colours). */
    public function effectiveColorScheme(): ?string
    {
        $node = $this;

        for ($depth = 0; $node && $depth < 20; $depth++) { // depth cap guards against a bad cycle
            if ($node->color_scheme && isset(\App\Support\CheckerDesign::PALETTES[$node->color_scheme])) {
                return $node->color_scheme;
            }
            $node = $node->parent;
        }

        return null;
    }

    /** This group and every group below it. */
    public static function branchIds(int $id): array
    {
        $ids = [$id];
        $level = [$id];

        for ($depth = 0; $depth < 20 && $level; $depth++) { // depth cap guards against a bad cycle
            $level = static::whereIn('parent_id', $level)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = array_merge($ids, $level);
        }

        return $ids;
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
