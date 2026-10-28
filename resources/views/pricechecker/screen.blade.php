<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>Narx tekshirgich</title>
    <link rel="stylesheet" href="{{ asset('css/checker.css') }}?v={{ @filemtime(public_path('css/checker.css')) }}">
    <style>html, body { margin: 0; height: 100%; overflow: hidden; background: #000; } #scan { position: absolute; left: -9999px; }</style>
</head>
<body>
    @include('pricechecker._stage', ['d' => $d, 'title' => $checker->name, 'state' => 'idle', 'item' => null, 'w' => '100vw', 'h' => '100vh', 'u' => '1vmin'])
    {{-- the scan field stays focused but invisible: a scanner types into it and sends Enter --}}
    <input id="scan" autocomplete="off" autofocus>
    <script>
        (function () {
            var scan = document.getElementById('scan'), stage = document.getElementById('pcs'), timer,
                version = @json($version), timeout = {{ (int) $d['timeout'] }} * 1000;

            function show(state) {
                stage.dataset.state = state;
                clearTimeout(timer);
                if (state !== 'idle') timer = setTimeout(function () { show('idle'); }, timeout);
            }
            function set(field, text) { var el = stage.querySelector('[data-f=' + field + ']'); if (el) el.textContent = text; }

            function look(code) {
                fetch("{{ route('checker.price') }}?code=" + encodeURIComponent(code), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) {
                        if (r.status === 401) { location.reload(); return null; }
                        return r.json();
                    })
                    .then(function (d) {
                        if (!d) return;
                        if (d.v !== version) { location.reload(); return; } // the design was changed in the admin
                        if (!d.found) { show('error'); return; }
                        set('name', d.name); set('price', d.price_text); set('unit', d.unit); set('code', d.code);
                        show('result');
                    })
                    .catch(function () { show('error'); });
            }

            scan.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                var code = scan.value.trim();
                scan.value = '';
                if (code) look(code);
            });
            // keep the scan field focused, whatever is tapped
            document.addEventListener('click', function () { scan.focus(); });
            setInterval(function () { if (document.activeElement !== scan) scan.focus(); }, 1000);

            // while idle, pick up design changes made in the admin
            setInterval(function () {
                if (stage.dataset.state !== 'idle') return;
                fetch("{{ route('checker.version') }}", { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) { if (d && d.v !== version) location.reload(); })
                    .catch(function () {});
            }, 30000);
        })();
    </script>
</body>
</html>
