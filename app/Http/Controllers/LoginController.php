<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DevAutoLogin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Sign out, and sign in by the badge QR URL. The login form itself is the Livewire component App\Livewire\Pda\Login. */
class LoginController extends Controller
{
    /** The badge QR code holds /login/badge/{token}: opening it (camera) signs that user in. */
    public function badge(Request $request, string $token)
    {
        $user = User::byBadge($token);

        if (! $user) {
            return redirect()->route('login')->with('badge_error', 'Badge tanilmadi yoki eskirgan.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return $user->pda_user ? redirect($user->homeUrl()) : redirect()->intended($user->homeUrl());
    }

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
