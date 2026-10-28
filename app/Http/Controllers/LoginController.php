<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DevAutoLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Sign out. Signing in is the Livewire component App\Livewire\Pda\Login. */
class LoginController extends Controller
{
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect()->route('login');

        // In dev auto-login mode, stay logged out until a dev link is used.
        return DevAutoLogin::enabled() ? $response->withCookie(cookie('dev_noauto', '1', 60 * 24)) : $response;
    }
}