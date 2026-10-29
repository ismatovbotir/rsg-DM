<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A named set of permissions the admin assigns to users. The role with is_admin has everything and cannot be edited. */
class Role extends Model
{
    protected $fillable = ['name', 'permissions', 'is_admin'];

    protected $casts = ['permissions' => 'array', 'is_admin' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
