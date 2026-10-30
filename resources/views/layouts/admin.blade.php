<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>{!! \App\Support\Tr::t($__env->yieldContent('title', 'Boshqaruv')) !!} — {{ config('menu.app_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/checker.css') }}?v={{ @filemtime(public_path('css/checker.css')) }}">
</head>
<body>
    <aside>
        <div class="brand flex items-center gap-2"><img src="{{ asset('img/logo.png') }}" alt="" class="h-8 w-8 rounded"><span>{{ config('menu.app_name') }}<small class="block text-[10px] font-normal opacity-70">{{ __('Admin') }}</small></span></div>
        @php $me = auth()->user(); @endphp
        {{-- the groups and items come from App\Support\Permissions; a role only sees what it ticked --}}
        @foreach (\App\Support\Permissions::adminGroups() as $group)
            @php $items = array_filter($group['items'], fn ($i) => $me->isAdmin() || $me->hasPermission($i['key'])); @endphp
            @if ($items)
                <details class="grp" data-grp="{{ md5($group['title']) }}">
                    <summary>{{ $group['title'] }}</summary>
                    @foreach ($items as $i)
                        @php
                            $on = $i['type'] !== null
                                ? request()->routeIs($i['pattern']) && request('type') === $i['type']
                                : (request()->routeIs($i['pattern']) && ! (str_starts_with($i['key'], 'admin.documents') && request('type')));
                        @endphp
                        <a href="{{ route($i['route'], $i['params']) }}" @class(['active' => $on])>{{ $i['title'] }}</a>
                    @endforeach
                </details>
            @endif
        @endforeach
        <div class="spacer"></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>{{ __('Chiqish (') }}{{ auth()->user()->name }})</button></form>
    </aside>
    <div class="main">
        <header class="topbar"><h1>{!! \App\Support\Tr::t($__env->yieldContent('title')) !!}</h1></header>
        <div class="scroll">
            @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @foreach ($errors->all() as $error)<div class="flash error">{{ $error }}</div>@endforeach
            @yield('content')
        </div>
        <footer class="botbar"><span>{{ config('menu.app_name') }} {{ __('· Admin') }}</span><span>{{ auth()->user()->name }} · {{ now()->format('d.m.Y') }}</span></footer>
    </div>
    <script>
        // Sidebar groups: collapsible, state kept per browser; the group holding the current page is always open.
        (function () {
            var key = 'admin_nav_closed', closed = [];
            try { closed = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) {}
            document.querySelectorAll('aside details.grp').forEach(function (d) {
                var id = d.dataset.grp, hasActive = !!d.querySelector('a.active');
                d.open = hasActive || closed.indexOf(id) < 0;
                d.addEventListener('toggle', function () {
                    var i = closed.indexOf(id);
                    if (d.open && i >= 0) closed.splice(i, 1);
                    if (!d.open && i < 0 && !d.querySelector('a.active')) closed.push(id);
                    try { localStorage.setItem(key, JSON.stringify(closed)); } catch (e) {}
                });
            });
        })();
    </script>
</body>
</html>
