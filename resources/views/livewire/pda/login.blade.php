<div class="contents" @if ($mode === 'pin') x-data="{ pin: '', busy: false,
        press(d) { if (this.busy || this.pin.length >= 4) return; this.pin += d; if (this.pin.length === 4) this.send(); },
        send() { this.busy = true; $wire.submitPin(this.pin).finally(() => { this.pin = ''; this.busy = false; }); } }"
        @keydown.window="if (/^[0-9]$/.test($event.key)) press($event.key); else if ($event.key === 'Escape') $wire.back()" @endif>
    <x-pda.page :title="config('menu.app_name')">
        @php $hue = fn ($name) => crc32($name) % 360; @endphp
        <div class="auth">
            <div class="auth-brand">
                <img class="auth-logo !p-0" src="{{ asset('img/logo.png') }}" alt="{{ config('menu.app_name') }}">
                <h1>{{ config('menu.app_name') }}</h1>
                <p>@if ($mode === 'pin') PIN kodni kiriting @elseif ($mode === 'password') Parol bilan kirish @else Tizimga kirish @endif</p>
            </div>

            @foreach ($errors->all() as $error)<div class="auth-error" role="alert">{{ $error }}</div>@endforeach

            @if ($mode === 'pick')
                {{-- a wedge scanner types the badge QR here and presses Enter --}}
                <form wire:submit="scanBadge" class="auth-scan">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 3h6v2H5v4H3V3zm12 0h6v6h-2V5h-4V3zM3 15h2v4h4v2H3v-6zm16 0h2v6h-6v-2h4v-4zM7 11h10v2H7v-2z"/></svg>
                    <input wire:model="scan" placeholder="Badge QR kodni skanerlang" autocomplete="off" autofocus>
                </form>
                <div class="auth-or"><span>yoki ismingizni tanlang</span></div>
                <div class="auth-users">
                    @forelse ($users as $user)
                        <button type="button" class="auth-user" wire:click="choose({{ $user->id }})" wire:key="u-{{ $user->id }}">
                            <span class="auth-avatar" style="background:hsl({{ $hue($user->name) }} 55% 42%)">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <span class="auth-name">{{ $user->name }}<small>{{ $user->role_label }}</small></span>
                        </button>
                    @empty
                        <p class="auth-empty">Faol foydalanuvchilar yo‘q.</p>
                    @endforelse
                </div>
                <button type="button" class="auth-link" wire:click="password">Parol bilan kirish</button>
            @elseif ($mode === 'pin')
                <div class="auth-who">
                    <span class="auth-avatar big" style="background:hsl({{ $hue($chosen?->name ?? '') }} 55% 42%)">{{ mb_strtoupper(mb_substr($chosen?->name ?? '?', 0, 1)) }}</span>
                    <strong>{{ $chosen?->name }}</strong>
                </div>
                <div class="pin-dots" aria-label="PIN">
                    @for ($i = 0; $i < 4; $i++)<span :class="{ on: pin.length > {{ $i }} }"></span>@endfor
                </div>
                <div class="keypad">
                    @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $d)<button type="button" @click="press('{{ $d }}')">{{ $d }}</button>@endforeach
                    <button type="button" class="alt" wire:click="back" aria-label="Orqaga">←</button>
                    <button type="button" @click="press('0')">0</button>
                    <button type="button" class="alt" @click="pin = pin.slice(0, -1)" aria-label="O‘chirish">⌫</button>
                </div>
                <button type="button" class="auth-link" wire:click="back">Boshqa foydalanuvchi</button>
            @else
                <form wire:submit="login" class="auth-form">
                    <input class="field" type="email" wire:model="email" placeholder="Email" autocomplete="username" autofocus required>
                    <input class="field" type="password" wire:model="password" placeholder="Parol" autocomplete="current-password" required>
                    <button class="btn">Kirish</button>
                </form>
                <button type="button" class="auth-link" wire:click="back">← Foydalanuvchilar ro‘yxati</button>
            @endif

            @if (\App\Http\Middleware\DevAutoLogin::quick())
                <div class="auth-dev">
                    <span>DEV: avtorizatsiyani emulyatsiya qilish</span>
                    <a class="btn green" href="{{ route('dev.login', 'user') }}">Operator sifatida kirish</a>
                    <a class="btn gray" href="{{ route('dev.login', 'admin') }}">Admin sifatida kirish</a>
                </div>
            @endif
        </div>
    </x-pda.page>
</div>
