<div class="contents">
    <x-pda.page :title="config('menu.app_name')">
        <form wire:submit="login" class="pad center">
            @foreach ($errors->all() as $error)<div class="flash error !mx-0">{{ $error }}</div>@endforeach
            <p><input class="field" type="email" wire:model="email" placeholder="Email" autocomplete="username" autofocus required></p>
            <p><input class="field" type="password" wire:model="password" placeholder="Parol" autocomplete="current-password" required></p>
            <button class="btn">Kirish</button>
        </form>

        @if (\App\Http\Middleware\DevAutoLogin::quick())
            <div class="pad center">
                <span class="sub">DEV: avtorizatsiyani emulyatsiya qilish</span>
                <p><a class="btn green" href="{{ route('dev.login', 'user') }}">Operator sifatida kirish</a></p>
                <p><a class="btn gray" href="{{ route('dev.login', 'admin') }}">Admin sifatida kirish</a></p>
            </div>
        @endif
    </x-pda.page>
</div>