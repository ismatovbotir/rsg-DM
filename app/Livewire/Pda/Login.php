<?php

namespace App\Livewire\Pda;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/** Sign in. Hosted by the login page (guests of "/" and /login), so it has no layout of its own. */
class Login extends Component
{
    public string $email = '';

    public string $password = '';

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
        session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(Auth::user()->isAdmin() ? route('admin.documents.index') : route('menu'));
    }

    public function render()
    {
        return view('livewire.pda.login');
    }
}