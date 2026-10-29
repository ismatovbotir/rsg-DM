@section('title', config('menu.app_name'))

{{-- Hardware keypad: the digit opens the operation it is bound to (config 'hotkey'); Esc closes the drawer. --}}
<div class="contents" x-data="{ drawer: false }" x-effect="document.body.classList.toggle('drawer-open', drawer)" wire:poll.30s
     @keydown.window="
        if ($event.key === 'Escape') { drawer = false; return }
        if (/^[0-9]$/.test($event.key)) { const row = document.querySelector('.row[data-key=\'' + $event.key + '\']'); row && row.click() }
     ">
    <x-pda.page :home="true" :notice="$notice" :failure="$failure">
        <x-slot:header>
            <button class="hbtn" type="button" aria-label="Menyu" @click="drawer = true">☰</button>
            <span class="title">{{ config('menu.app_name') }}</span>
            <a class="hbtn" href="{{ route('messages.index') }}" aria-label="Xabarlar">
                <svg class="ico"><use href="#i-bell"/></svg>
                @if ($unread)<span class="dot">{{ $unread }}</span>@endif
            </a>
            <button class="hbtn" type="button" id="exchange" aria-label="Server bilan almashinuv" title="Server bilan almashinuv">⇅</button>
        </x-slot:header>

        {{-- Urgent unread messages show once as a banner on the home screen only, never inside a scan flow. --}}
        @foreach ($urgent as $r)
            <a class="flash error block no-underline" href="{{ route('messages.show', $r->message_id) }}" wire:key="urgent-{{ $r->id }}">
                <strong>{{ $r->message->title }}</strong>
            </a>
        @endforeach

        <ul class="list">
            @foreach ($items as $item)
                @php($count = $badges[$item['key']] ?? 0)
                <li wire:key="menu-{{ $item['key'] }}">
                    <a class="row" href="{{ route('module', $item['key']) }}" @if (isset($item['hotkey'])) data-key="{{ $item['hotkey'] }}" @endif>
                        <span class="icon"><svg class="ico"><use href="#i-{{ $item['icon'] }}"/></svg></span>
                        <span class="label">{{ $item['title'] }}</span>
                        {{-- red = needs action (tasks), grey = open drafts --}}
                        @if ($count)<span class="badge {{ $item['key'] === 'tasks' ? 'error' : '' }}">{{ $count }}</span>@endif
                        @if (isset($item['hotkey']))<span class="num">{{ $item['hotkey'] }}</span>@endif
                        <span class="chev">›</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div id="scrim" @click="drawer = false"></div>
        <nav id="drawer">
            <div class="head">{{ config('menu.app_name') }}<small>{{ auth()->user()->name }}</small></div>
            <ul class="list">
                @if (auth()->user()->canAdminPanel())
                    <li>
                        <a class="row" href="{{ route('admin.home') }}">
                            <span class="icon"><svg class="ico"><use href="#i-directories"/></svg></span>
                            <span class="label">Boshqaruv paneli</span>
                        </a>
                    </li>
                @endif
                @foreach (config('menu.side') as $item)
                    <li>
                        @if (! empty($item['logout']))
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="row">
                                    <span class="icon"><svg class="ico"><use href="#i-{{ $item['icon'] }}"/></svg></span>
                                    <span class="label">{{ $item['title'] }}</span>
                                </button>
                            </form>
                        @else
                            <a class="row" href="{{ route('module', $item['key']) }}">
                                <span class="icon"><svg class="ico"><use href="#i-{{ $item['icon'] }}"/></svg></span>
                                <span class="label">{{ $item['title'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </x-pda.page>
</div>