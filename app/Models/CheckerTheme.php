<?php

namespace App\Models;

use App\Support\CheckerDesign;
use Illuminate\Database\Eloquent\Model;

/** A reusable look of the price checker screen (colours, picture blocks, texts). A price checker picks one; one theme is the default. */
class CheckerTheme extends Model
{
    protected $fillable = ['name', 'design', 'is_default'];

    protected $casts = ['design' => 'array', 'is_default' => 'boolean'];

    public function checkers()
    {
        return $this->hasMany(PriceChecker::class, 'theme_id');
    }

    /** Settings with defaults filled in. */
    public function getScreenAttribute(): array
    {
        return CheckerDesign::merge($this->design);
    }

    /** The theme of terminals that have none yet (and of the pairing screen). */
    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::orderBy('id')->first();
    }

    public function makeDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
