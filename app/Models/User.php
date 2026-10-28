<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Menu keys whose access the admin can switch per user (assigned tasks are always available). */
    public static function manageableMenu(): array
    {
        return collect(config('menu.items'))->reject(fn ($i) => $i['key'] === 'tasks')->pluck('title', 'key')->all();
    }

    /** Admins and users without a restriction (menu_access = null) can open every menu item. */
    public function canAccess(string $menuKey): bool
    {
        return $this->isAdmin() || $menuKey === 'tasks' || $this->menu_access === null || in_array($menuKey, $this->menu_access, true);
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->isAdmin() ? 'Administrator' : 'Operator';
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'menu_access' => 'array',
        'password' => 'hashed',
    ];
}
