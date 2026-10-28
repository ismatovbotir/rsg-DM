<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** The main route "/" is the authorization page for guests and the menu once signed in. */
class LoginForGuests
{
    public function handle(Request $request, Closure $next)
    {
        return $request->user() ? $next($request) : response()->view('login');
    }
}