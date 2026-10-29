<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\CheckerTheme;
use App\Models\PriceChecker;
use App\Support\CheckerItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/** Customer-facing price checker: scan a barcode, see the name and price. Needs the token from the QR code. */
class PriceCheckerController extends Controller
{
    public function screen(Request $request)
    {
        $checker = $request->attributes->get('checker');

        return view('pricechecker.screen', ['checker' => $checker, 'd' => $checker->screen, 'version' => $this->version($checker)]);
    }

    public function price(Request $request)
    {
        $checker = $request->attributes->get('checker');
        $code = trim((string) $request->query('code'));
        $found = $code !== '' ? Item::resolve($code) : null;

        if (! $found) {
            return response()->json(['found' => false, 'v' => $this->version($checker)], 404);
        }

        [$item] = $found;

        return response()->json(CheckerItem::payload($item, $checker->screen, $checker->price_type_id) + ['v' => $this->version($checker)]);
    }

    /** The screen polls this while idle and reloads itself when the admin changed the design. */
    public function version(PriceChecker $checker): string
    {
        return $checker->screenVersion();
    }

    public function versionJson(Request $request)
    {
        return response()->json(['v' => $this->version($request->attributes->get('checker'))]);
    }

    /** The "scan the QR code" page, shown to a terminal that has no token yet. */
    public function activateShow(Request $request)
    {
        // a terminal that already holds a valid token (session or 5-year cookie) goes straight to the screen
        if (PriceChecker::byToken($request->session()->get('checker_token') ?? $request->cookie('checker_token'))) {
            return redirect()->route('checker.screen');
        }

        $theme = CheckerTheme::default();

        return view('pricechecker.activate', ['d' => $theme?->screen ?? \App\Support\CheckerDesign::merge(null)]);
    }

    /** The QR content opens this URL directly (camera) or is typed in by a keyboard-wedge scanner (form). */
    public function activate(Request $request, ?string $code = null)
    {
        $code = $code ?? basename(trim((string) $request->input('code')));
        $checker = PriceChecker::byToken($code);

        if (! $checker) {
            return redirect()->route('checker.activate.show')->withErrors(['code' => 'QR kod noto‘g‘ri yoki eskirgan.']);
        }

        $request->session()->regenerate();
        $request->session()->put('checker_token', $code);
        Cookie::queue(Cookie::make('checker_token', $code, 60 * 24 * 365 * 5, null, null, null, true, false, 'lax'));

        return redirect()->route('checker.screen');
    }
}
