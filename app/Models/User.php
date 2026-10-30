<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Permissions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'menu_access',
        'role_id',
        'warehouse_id',
        'pda_user',
        'locale',
        'denied_permissions',
    ];

    /** The user's own shop (default warehouse); null = not tied to one shop. */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Warehouses ticked for this user on the "Do'konlar" tab (besides the own shop). */
    public function warehouseAccess()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse');
    }

    /** Price lists ticked for this user on the "Narx turlari" tab. */
    public function priceTypeAccess()
    {
        return $this->belongsToMany(PriceType::class, 'user_price_type');
    }

    /**
     * Warehouses whose stock the user may see and use as the source: the ticked ones plus the own shop.
     * null = no restriction (nothing ticked and no shop).
     * @return list<int>|null
     */
    public function accessibleWarehouseIds(): ?array
    {
        $ids = $this->warehouseAccess()->pluck('warehouses.id')->map(fn ($id) => (int) $id)->all();
        $this->shopId() && $ids[] = $this->shopId();

        return $ids ? array_values(array_unique($ids)) : null;
    }

    /**
     * Price lists the user may see: the ticked ones plus the price list of the own shop. null = all.
     * @return list<int>|null
     */
    public function accessiblePriceTypeIds(): ?array
    {
        $ids = $this->priceTypeAccess()->pluck('price_types.id')->map(fn ($id) => (int) $id)->all();
        $this->shopPriceTypeId() && $ids[] = $this->shopPriceTypeId();

        return $ids ? array_values(array_unique($ids)) : null;
    }

    /** Id of the active shop the user is tied to, or null. Stock and prices the user sees are those of this shop. */
    public function shopId(): ?int
    {
        return $this->warehouse_id && $this->warehouse?->is_active ? (int) $this->warehouse_id : null;
    }

    /** Price list of the user's shop. */
    public function shopPriceTypeId(): ?int
    {
        return $this->shopId() ? ($this->warehouse->price_type_id ? (int) $this->warehouse->price_type_id : null) : null;
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Full access. Users created before roles existed (no role_id) fall back to the old role column. */
    public function isAdmin(): bool
    {
        return $this->roleModel ? $this->roleModel->is_admin : $this->role === 'admin';
    }

    /** @return list<string> permission keys; a full administrator has all of them */
    public function permissions(): array
    {
        if ($this->isAdmin()) {
            return Permissions::all();
        }

        // no role yet = the old "operator": the whole PDA menu, no admin panel
        // the role's permissions minus the ones switched off for this user
        return $this->roleModel ? array_values(array_diff($this->roleModel->permissions ?? [], $this->denied_permissions ?? [])) : array_keys(Permissions::pdaItems());
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissions(), true);
    }

    /** May open the admin panel at all (some admin.* permission). */
    public function canAdminPanel(): bool
    {
        return $this->isAdmin() || collect($this->permissions())->contains(fn ($k) => str_starts_with($k, 'admin.'));
    }

    /** May use an admin section; the documents section is open when any document type is allowed. */
    public function canAdmin(?string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($key === null) {
            return false;
        }

        return $this->hasPermission($key) || ($key === 'admin.documents' && collect($this->permissions())->contains(fn ($k) => str_starts_with($k, 'admin.documents.')));
    }

    /** Document types the user may see in the admin panel (null = all of them). */
    public function adminDocumentTypes(): ?array
    {
        if ($this->canAdminAll('admin.documents')) {
            return null;
        }

        return collect(array_keys(Document::types()))->filter(fn ($t) => $this->hasPermission('admin.documents.'.$t))->values()->all();
    }

    private function canAdminAll(string $key): bool
    {
        return $this->isAdmin() || $this->hasPermission($key);
    }

    /** Where to go after signing in: the admin panel for admin-panel users without a PDA menu, else the PDA menu. */
    public function homeUrl(): string
    {
        if ($this->pda_user) {
            return route('menu'); // ticked in the user form: always the PDA view
        }

        $hasPda = $this->isAdmin() || collect($this->permissions())->contains(fn ($k) => str_starts_with($k, 'pda.'));

        return $this->canAdminPanel() && ($this->isAdmin() || ! $hasPda) ? route('admin.home') : route('menu');
    }

    /** First admin page this user may open (the admin panel landing). */
    public function firstAdminUrl(): ?string
    {
        foreach (Permissions::adminGroups() as $group) {
            foreach ($group['items'] as $item) {
                if ($this->canAdmin($item['key']) && ($this->isAdmin() || $this->hasPermission($item['key']))) {
                    return route($item['route'], $item['params']);
                }
            }
        }

        return null;
    }

    /** PDA menu items. Tasks and messages are always available; the rest come from the role. */
    public function canAccess(string $menuKey): bool
    {
        return $this->isAdmin() || $menuKey === 'tasks' || $this->hasPermission('pda.'.$menuKey);
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->roleModel?->name ?? ($this->isAdmin() ? 'Administrator' : 'Operator');
    }

    public function scopeOperators($query)
    {
        return $query->where('role', 'user')->where('is_active', true);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin',
        'badge_token',
        'badge_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'pda_user' => 'boolean',
        'denied_permissions' => 'array',
        'last_login_at' => 'datetime',
        'menu_access' => 'array',
        'password' => 'hashed',
        'pin' => 'hashed',
        'badge_token' => 'encrypted',
        'pin_locked_until' => 'datetime',
    ];

    public const PIN_MAX_FAILURES = 5;

    public const PIN_LOCK_MINUTES = 5;

    /** A random 4-digit PIN that is not trivial (1111, 1234, ...). Returned in plain text once; only its hash is stored. */
    public static function randomPin(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (preg_match('/^(\d)\1{3}$/', $pin) || in_array($pin, ['1234', '4321', '0123', '3210'], true));

        return $pin;
    }

    /** Set the PIN (random when none is given) and clear the lockout. Returns the plain PIN to show to the admin. */
    public function issuePin(?string $pin = null): string
    {
        $pin ??= self::randomPin();
        $this->forceFill(['pin' => $pin, 'pin_failed' => 0, 'pin_locked_until' => null])->save();

        return $pin;
    }

    /** New badge token (the old badge stops working). */
    public function regenerateBadge(): string
    {
        $token = Str::random(40);
        $this->forceFill(['badge_token' => $token, 'badge_hash' => hash('sha256', $token)])->save();

        return $token;
    }

    /** The badge token, created on first use. */
    public function ensureBadge(): string
    {
        return $this->badge_token ?: $this->regenerateBadge();
    }

    public function getBadgeUrlAttribute(): ?string
    {
        return $this->badge_token ? url('/login/badge/'.$this->badge_token) : null;
    }

    /** The active user a scanned badge (token, or the URL in its QR code) belongs to. */
    public static function byBadge(?string $scanned): ?self
    {
        $token = is_string($scanned) ? basename(trim($scanned)) : '';

        return $token === '' ? null : static::where('is_active', true)->where('badge_hash', hash('sha256', $token))->first();
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until !== null && $this->pin_locked_until->isFuture();
    }

    /** Check a PIN; five wrong ones lock PIN sign-in of this user for a few minutes. */
    public function checkPin(string $pin): bool
    {
        if (! $this->pin || $this->isPinLocked()) {
            return false;
        }

        if (Hash::check($pin, $this->pin)) {
            $this->forceFill(['pin_failed' => 0, 'pin_locked_until' => null])->save();

            return true;
        }

        $failed = $this->pin_failed + 1;
        $this->forceFill($failed >= self::PIN_MAX_FAILURES
            ? ['pin_failed' => 0, 'pin_locked_until' => now()->addMinutes(self::PIN_LOCK_MINUTES)]
            : ['pin_failed' => $failed])->save();

        return false;
    }
}
