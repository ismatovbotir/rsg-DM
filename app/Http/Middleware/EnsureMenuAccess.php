<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Route middleware `menu:<key>`: the user must be allowed to open that main-menu item. */
class EnsureMenuAccess
{
    public function handle(Request $request, Closure $next, string $key)
    {
        abort_unless($request->user()?->canAccess($key), 403, 'Bu bo‘limga ruxsat yo‘q.');

        return $next($request);
    }
}
