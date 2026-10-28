{{--
    One PDA screen: top bar, scrolling content, bottom bar. The layout (layouts/pda) is flex column, so a page component
    renders all three itself (inside its own Livewire root, which is display: contents) and stays in sync after updates.
    Props: title, back (url of the ← button), home (main menu: the bar shows the user and logout), notice / failure (Livewire messages). Slots: header, footer (complete bars).
--}}
@props(['title' => null, 'back' => null, 'notice' => null, 'failure' => null, 'subtitle' => null, 'home' => false])
<header>
    @isset($header)
        {{ $header }}
    @else
        @if ($back)<a class="hbtn" href="{{ $back }}" aria-label="Orqaga">←</a>@endif
        <span class="title">{{ $title }}@if ($subtitle)<small class="block text-xs font-normal opacity-80">{{ $subtitle }}</small>@endif</span>
    @endisset
</header>
<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($notice)<div class="flash">{{ $notice }}</div>@endif
    @if ($failure)<div class="flash error">{{ $failure }}</div>@endif
    @foreach ($errors->all() as $error)<div class="flash error">{{ $error }}</div>@endforeach
    {{ $slot }}
</main>
{{-- Bottom bar: the screen's own, or a default bar so every window has one. --}}
@isset($footer)
    {{ $footer }}
@else
    <div class="footer bar-default">
        @auth
            @if ($home)
                <form method="POST" action="{{ route('logout') }}" class="flex w-full items-center gap-2.5">
                    @csrf
                    <span class="sub !m-0 flex-1">{{ auth()->user()->name }}@isset($device) · {{ $device->name ?: 'qurilma' }}@endisset</span>
                    <button class="btn gray !w-auto !min-w-0">Chiqish</button>
                </form>
            @else
                <a class="btn gray" href="{{ route('menu') }}">Bosh menyu</a>
            @endif
        @else
            <span class="sub">{{ config('menu.app_name') }}</span>
        @endauth
    </div>
@endisset
