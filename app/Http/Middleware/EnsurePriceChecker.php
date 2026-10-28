<?php

namespace App\Http\Middleware;

use App\Models\PriceChecker;
use Closure;
use Illuminate\Http\Request;

/**
 * The price checker terminal must carry the token it got by scanning the server's QR code:
 * in its session, or in the long-lived cookie when the session expired. Otherwise it sees the "scan the QR" page.
 */
class EnsurePriceChecker
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->session()->get('checker_token') ?? $request->cookie('checker_token');
        $checker = PriceChecker::byToken($token);

        if (! $checker) {
            $request->session()->forget('checker_token');

            return $request->expectsJson()
                ? response()->json(['message' => 'Terminal faollashtirilmagan.'], 401)
                : redirect()->route('checker.activate.show');
        }

        $request->session()->put('checker_token', $token); // an expired session is restored from the cookie
        $checker->activated_at ?? $checker->forceFill(['activated_at' => now()])->save();

        if (! $checker->last_seen_at || $checker->last_seen_at->lt(now()->subMinutes(5))) {
            $checker->forceFill(['last_seen_at' => now()])->save();
        }

        $request->attributes->set('checker', $checker);

        return $next($request);
    }
}
