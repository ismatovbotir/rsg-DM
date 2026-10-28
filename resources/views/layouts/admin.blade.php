<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Boshqaruv') — {{ config('menu.app_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/checker.css') }}?v={{ @filemtime(public_path('css/checker.css')) }}">
</head>
<body>
    <aside>
        <div class="brand">{{ config('menu.app_name') }} · Admin</div>
        <details class="grp" data-grp="068296c5236391499649d45deef0298b">
            <summary>Ma’lumotnomalar</summary>
        <a href="{{ route('admin.items.index') }}" @class(['active' => request()->routeIs('admin.items.*')])>Tovarlar</a>
        <a href="{{ route('admin.counterparties.index') }}" @class(['active' => request()->routeIs('admin.counterparties.*')])>Kontragentlar</a>
        <a href="{{ route('admin.warehouses.index') }}" @class(['active' => request()->routeIs('admin.warehouses.*')])>Omborlar</a>
        <a href="{{ route('admin.pricetypes.index') }}" @class(['active' => request()->routeIs('admin.pricetypes.*')])>Narx turlari</a>
        </details>
        <details class="grp" data-grp="e4eadc182cf15d75de4e503abe5e753a">
            <summary>Hujjatlar</summary>
        <a href="{{ route('admin.documents.index') }}" @class(['active' => request()->routeIs('admin.documents.*') && ! request('type')])>Barcha hujjatlar</a>
        @foreach (\App\Models\Document::types() as $typeKey => $typeTitle)
            <a href="{{ route('admin.documents.index', ['type' => $typeKey]) }}" @class(['active' => request()->routeIs('admin.documents.index') && request('type') === $typeKey])>{{ $typeTitle }}</a>
        @endforeach
        </details>
        <details class="grp" data-grp="3888991cb2cf45fd2179dcc88933f167">
            <summary>Operatsiyalar</summary>
        <a href="{{ route('admin.tasks.index') }}" @class(['active' => request()->routeIs('admin.tasks.*')])>Vazifalar</a>
        <a href="{{ route('admin.messages.index') }}" @class(['active' => request()->routeIs('admin.messages.*')])>Xabarlar</a>
        </details>
        <details class="grp" data-grp="7c1316cbffd14e2e80b35a08a7fe5884">
            <summary>Qurilmalar</summary>
        <a href="{{ route('admin.devices.index') }}" @class(['active' => request()->routeIs('admin.devices.*')])>PDA qurilmalar</a>
        <a href="{{ route('admin.checkers.index') }}" @class(['active' => request()->routeIs('admin.checkers.*')])>Narx tekshirgichlar</a>
        <a href="{{ route('admin.printers.index') }}" @class(['active' => request()->routeIs('admin.printers.*')])>Printerlar</a>
        <a href="{{ route('admin.labels.index') }}" @class(['active' => request()->routeIs('admin.labels.*')])>Yorliq dizayneri</a>
        <a href="{{ route('admin.pricetags.index') }}" @class(['active' => request()->routeIs('admin.pricetags.*')])>Narx yorliqlari</a>
        </details>
        <details class="grp" data-grp="bf386a4433b0dccb313b18a251b77b17">
            <summary>Almashinuv</summary>
        <a href="{{ route('admin.exchange.index') }}" @class(['active' => request()->routeIs('admin.exchange.*')])>Adines almashinuvi</a>
        </details>
        <details class="grp" data-grp="5b869a683815a27440315fa7e3c1c386">
            <summary>Tizim</summary>
        <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>Foydalanuvchilar</a>
        </details>
        <a href="{{ route('menu') }}">PDA interfeysi</a>
        <div class="spacer"></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Chiqish ({{ auth()->user()->name }})</button></form>
    </aside>
    <div class="main">
        <header class="topbar"><h1>@yield('title')</h1></header>
        <div class="scroll">
            @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @foreach ($errors->all() as $error)<div class="flash error">{{ $error }}</div>@endforeach
            @yield('content')
        </div>
        <footer class="botbar"><span>{{ config('menu.app_name') }} · Admin</span><span>{{ auth()->user()->name }} · {{ now()->format('d.m.Y') }}</span></footer>
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
