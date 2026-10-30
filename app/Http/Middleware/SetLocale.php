<?php

namespace App\Http\Middleware;

use App\Support\Tr;
use Closure;
use Illuminate\Http\Request;

/** Interface language: the signed-in user's own choice, else the `lang` cookie (sign-in page), else Uzbek. */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $code = $request->user()?->locale ?: $request->cookie('lang');

        app()->setLocale(is_string($code) && isset(Tr::locales()[$code]) ? $code : config('app.fallback_locale'));

        return $next($request);
    }
}
