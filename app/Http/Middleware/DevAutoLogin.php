<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Local development only: signs in the default dev user so the login screen can be skipped. */
class DevAutoLogin
{
    public static function enabled(): bool
    {
        return config('dev.autologin') && app()->environment('local');
    }

    /** Dev quick-login links (/dev/login/{role}) on the login page: local env, with auto-login or DEV_QUICKLOGIN. */
    public static function quick(): bool
    {
        return app()->environment('local') && (config('dev.autologin') || config('dev.quick'));
    }

    public function handle(Request $request, Closure $next)
    {
        // The cookie is set on explicit logout so the dev can still see the login screen.
        if (self::enabled() && Auth::guest() && ! $request->hasCookie('dev_noauto')) {
            $email = config('dev.users.'.config('dev.default'));

            if ($user = User::where('email', $email)->first()) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}
