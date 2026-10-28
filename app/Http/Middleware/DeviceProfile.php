<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Identifies the physical PDA by a secret cookie (only its hash is stored) and shares its keyboard settings.
 * A new browser/device registers itself as an unnamed row; the admin names it and picks the keyboard mode.
 * This is configuration only: it is never used for authentication or authorisation.
 */
class DeviceProfile
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('device_token');
        $device = is_string($token) && $token !== '' ? Device::where('token_hash', hash('sha256', $token))->first() : null;

        if (! $device) {
            $token = Str::random(40);
            $device = Device::create([
                'uuid' => (string) Str::uuid(),
                'token_hash' => hash('sha256', $token),
                'keyboard_mode' => 'auto',
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);

            Cookie::queue(Cookie::make('device_token', $token, 60 * 24 * 365 * 5, null, null, null, true));
        }

        // Not a heartbeat log: refresh at most every 5 minutes.
        if (! $device->last_seen_at || $device->last_seen_at->lt(now()->subMinutes(5))) {
            $device->forceFill(['last_seen_at' => now(), 'last_user_id' => $request->user()?->id])->save();
        }

        $request->attributes->set('device', $device);
        app()->instance('device.current', $device); // reachable from Livewire update requests, where the original request is not the current one
        View::share('device', $device);
        // Until the client decides ("auto"), assume a keypad device: a wrongly shown keyboard hurts more than a missing one.
        View::share('kbOff', $device->keyboard_mode !== 'on');

        return $next($request);
    }
}
