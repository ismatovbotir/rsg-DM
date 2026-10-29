<?php

namespace App\Models;

use App\Support\CheckerDesign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A price checker terminal. It has a token, generated when the checker is created and replaceable on the edit page.
 * The admin shows the token as a QR code (its URL); scanning it on the terminal stores the token in the terminal's
 * session (and a long-lived cookie). The token is kept encrypted so the QR code can be shown again; token_hash looks it up.
 * Regenerating the token signs out every terminal that used the old one.
 */
class PriceChecker extends Model
{
    protected $fillable = ['name', 'price_type_id', 'is_active', 'theme_id'];

    protected $hidden = ['token', 'token_hash'];

    protected $casts = [
        'is_active' => 'boolean', 'activated_at' => 'datetime', 'last_seen_at' => 'datetime',
        'token' => 'encrypted',
    ];

    public function theme()
    {
        return $this->belongsTo(CheckerTheme::class);
    }

    public function priceType()
    {
        return $this->belongsTo(PriceType::class);
    }

    /** New token; the old one stops working at once. Returns it. */
    public function regenerateToken(): string
    {
        $token = Str::random(48);
        $this->forceFill(['token' => $token, 'token_hash' => hash('sha256', $token), 'activated_at' => null, 'last_seen_at' => null])->save();

        return $token;
    }

    public static function byToken(?string $token): ?self
    {
        return is_string($token) && $token !== ''
            ? static::where('is_active', true)->where('token_hash', hash('sha256', $token))->first()
            : null;
    }

    /** What the QR code contains: opening it on the terminal activates it. */
    public function getActivationUrlAttribute(): ?string
    {
        return $this->token ? url('/pricechecker/activate/'.$this->token) : null;
    }

    /** Screen settings: the chosen theme, else the default theme, else the built-in defaults. */
    public function getScreenAttribute(): array
    {
        return ($this->theme ?? CheckerTheme::default())?->screen ?? CheckerDesign::merge(null);
    }

    /** Changes when the admin edits what the terminal shows, so the terminal knows to reload (not when it is merely seen). */
    public function screenVersion(): string
    {
        return substr(md5(json_encode([$this->name, $this->screen])), 0, 12);
    }

    public function getIsConnectedAttribute(): bool
    {
        return $this->activated_at !== null;
    }
}
