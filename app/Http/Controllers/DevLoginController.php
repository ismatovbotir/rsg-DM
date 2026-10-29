<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DevAutoLogin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/** Local development only: 404 unless DevAutoLogin::enabled(). */
class DevLoginController extends Controller
{
    public function __invoke(Request $request, string $role)
    {
        abort_unless(DevAutoLogin::quick(), 404);

        $email = config("dev.users.{$role}");
        abort_unless($email, 404);

        $user = User::where('email', $email)->firstOrFail();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect($user->homeUrl())
            ->withCookie(Cookie::forget('dev_noauto'));
    }
}
