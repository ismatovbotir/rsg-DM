<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Open by default; when ONEC_API_TOKEN is set, inbound Adines calls must send it as a Bearer token or X-Api-Key. */
class EnsureOneCToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('onec.api_token');

        if (filled($token) && ! hash_equals($token, (string) ($request->bearerToken() ?: $request->header('X-Api-Key')))) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
