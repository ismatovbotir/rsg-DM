<?php

namespace App\Livewire\Pda;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Sign in on the PDA. Hosted by the login page (guests of "/" and /login), so it has no layout of its own.
 * Everyone (operators and admin-panel staff) picks their name and taps a 4-digit PIN, or scans their personal badge QR code
 * (a keyboard-wedge scanner types it into the scan field). Email + password stays as a fallback.
 */
class Login extends Component
{
    public string $mode = 'pick'; // pick | pin | password

    #[Locked]
    public ?int $userId = null;

    public string $scan = '';

    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        if (session('badge_error')) {
            $this->addError('scan', session('badge_error'));
        }
    }

    public function choose(int $id): void
    {
        $user = User::where('is_active', true)->findOrFail($id);

        if (! $user->pin) {
            $this->addError('pin', 'Bu foydalanuvchiga PIN berilmagan. Administratorga murojaat qiling yoki badge QR dan foydalaning.');

            return;
        }

        $this->userId = $user->id;
        $this->resetErrorBag();
        $this->mode = 'pin';
    }

    public function back(): void
    {
        $this->reset('userId', 'scan', 'password');
        $this->resetErrorBag();
        $this->mode = 'pick';
    }

    public function password(): void
    {
        $this->resetErrorBag();
        $this->mode = 'password';
    }

    /** The 4 digits are collected in the browser (taps are never lost to a slow request); only the finished PIN comes here. */
    public function submitPin(string $pin)
    {
        $user = User::where('is_active', true)->find($this->userId);
        $this->resetErrorBag();

        if (! $user) {
            return $this->back();
        }

        if (! preg_match('/^\d{4}$/', $pin)) {
            $this->addError('pin', 'PIN 4 ta raqamdan iborat.');

            return null;
        }

        $throttle = 'pin:'.$user->id.'|'.request()->ip();

        if ($user->isPinLocked() || RateLimiter::tooManyAttempts($throttle, 10)) {
            $this->addError('pin', 'Juda ko‘p noto‘g‘ri urinish. Bir necha daqiqadan keyin qayta urinib ko‘ring yoki badge QR dan foydalaning.');

            return null;
        }

        if (! $user->checkPin($pin)) {
            RateLimiter::hit($throttle, 300);
            $this->addError('pin', $user->isPinLocked() ? 'PIN ketma-ket noto‘g‘ri kiritildi. PIN bilan kirish vaqtincha bloklandi.' : 'PIN noto‘g‘ri.');

            return null;
        }

        RateLimiter::clear($throttle);

        return $this->signIn($user);
    }

    /** The scan field: a wedge scanner types the badge QR content and presses Enter. */
    public function scanBadge()
    {
        $scanned = $this->scan;
        $this->scan = '';
        $throttle = 'badge:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttle, 20)) {
            $this->addError('scan', 'Juda ko‘p urinish. Bir ozdan keyin qayta urinib ko‘ring.');

            return null;
        }

        $user = User::byBadge($scanned);

        if (! $user) {
            RateLimiter::hit($throttle, 60);
            $this->addError('scan', 'Badge tanilmadi yoki eskirgan.');

            return null;
        }

        return $this->signIn($user);
    }

    public function login()
    {
        $credentials = $this->validate(['email' => 'required|email', 'password' => 'required'], [], ['email' => 'Email', 'password' => 'Parol']);

        $throttle = 'login:'.mb_strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttle, 8)) {
            $this->addError('email', 'Juda ko‘p urinish. Bir ozdan keyin qayta urinib ko‘ring.');

            return;
        }

        if (! Auth::attempt($credentials + ['is_active' => true], true)) {
            RateLimiter::hit($throttle, 60);
            $this->addError('email', 'Login yoki parol noto‘g‘ri.');
            $this->password = '';

            return;
        }

        RateLimiter::clear($throttle);

        return $this->afterSignIn();
    }

    private function signIn(User $user)
    {
        Auth::login($user, true);

        return $this->afterSignIn();
    }

    private function afterSignIn()
    {
        session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        $user = Auth::user();

        return $user->pda_user ? redirect($user->homeUrl()) : redirect()->intended($user->homeUrl());
    }

    public function render()
    {
        return view('livewire.pda.login', [
            'users' => $this->mode === 'pick' ? User::with('roleModel')->where('is_active', true)->orderBy('name')->get() : collect(),
            'chosen' => $this->userId ? User::find($this->userId) : null,
        ]);
    }
}
