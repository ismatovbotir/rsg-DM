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

            // the colour set of the item's group: set over the theme while the result shows, removed afterwards
            var applied = [];
            function scheme(vars) {
                applied.forEach(function (n) { stage.style.removeProperty(n); });
                applied = [];
                (vars || '').split(';').forEach(function (pair) {
                    var i = pair.indexOf(':');
                    if (i > 0) { var n = pair.slice(0, i).trim(); stage.style.setProperty(n, pair.slice(i + 1).trim()); applied.push(n); }
                });
            }

            function show(state) {
                if (state !== 'result') scheme('');
                stage.dataset.state = state;
                clearTimeout(timer);
                if (state !== 'idle') timer = setTimeout(function () { show('idle'); }, timeout);
            }
            // fill every [data-f] with the payload field; optional blocks (.pcs-opt) vanish when their value is empty
            function fill(d) {
                stage.querySelectorAll('[data-f]').forEach(function (el) {
                    var f = el.dataset.f, v = f === 'stock_text' ? (d.stock && d.stock.text) : d[f];
                    v = v == null ? '' : String(v);
                    if (f === 'image') { el.src = v; el.hidden = !v; var box = stage.querySelector('[data-f-box=image]'); if (box) box.hidden = !v; return; }
                    el.textContent = v;
                });
                stage.querySelectorAll('.pcs-opt').forEach(function (el) {
                    var f = el.querySelector('[data-f]');
                    el.hidden = !(f && f.textContent.trim());
                });
                stage.dataset.stock = (d.stock && d.stock.state) || '';
            }

            var clock = stage.querySelector('[data-clock]');
            if (clock) setInterval(function () { var t = new Date(); clock.textContent = ('0' + t.getHours()).slice(-2) + ':' + ('0' + t.getMinutes()).slice(-2); }, 10000);

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
                        fill(d);
                        scheme(d.scheme_vars);
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
