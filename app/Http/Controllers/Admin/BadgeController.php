<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Qr;
use Illuminate\Http\Request;

/** Printable sheet of user badges: name, role and the personal sign-in QR code (never the PIN). */
class BadgeController extends Controller
{
    public function print(Request $request)
    {
        abort_unless($request->user()->canAdmin('admin.badges') || $request->user()->canAdmin('admin.users'), 403);

        $ids = array_filter((array) $request->query('ids'), 'is_numeric');
        $users = User::with('roleModel')->where('is_active', true)->when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get();

        $cards = $users->map(function (User $user) {
            $user->ensureBadge(); // users created before badges existed get theirs now

            return ['user' => $user, 'qr' => Qr::svg($user->badge_url, 240)];
        });

        return view('admin.badges.print', ['cards' => $cards]);
    }
}
