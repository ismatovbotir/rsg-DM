<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, interactive-widget=resizes-content">
    <meta name="theme-color" content="#e51932">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>@yield('title', config('menu.app_name'))</title>
    <link rel="stylesheet" href="{{ asset('css/pda.css') }}?v={{ @filemtime(public_path('css/pda.css')) }}">
</head>
<body data-kb="{{ $device->keyboard_mode ?? 'auto' }}">
    @include('partials.icons')
    {{-- Every page renders its own header / main / footer (see components/pda/page), so Livewire keeps them in sync. --}}
    @yield('content')
    <script>
        // On-screen keyboard policy. Precedence: local choice on this device (Sozlamalar) > admin device profile > auto-detect.
        // Inputs marked server-side with inputmode="none" keep the keyboard hidden on keypad PDAs; scanner and
        // hardware keys still work. A keyboard button next to each such input calls the keyboard on demand.
        (function () {
            var local = null;
            try { local = localStorage.getItem('kb_mode'); } catch (e) {}
            var mode = local || document.body.getAttribute('data-kb') || 'auto';
            if (mode === 'auto') {
                var touch = window.matchMedia && matchMedia('(pointer: coarse)').matches;
                mode = touch && window.innerWidth >= 600 ? 'on' : 'off'; // uncertain -> assume a keypad device
            }
            window.kbMode = mode;

            function setup() {
                var fields = document.querySelectorAll('input[inputmode="none"], input[data-kb-bound]');
                Array.prototype.forEach.call(fields, function (input) {
                    if (mode === 'on') { input.removeAttribute('inputmode'); return; }

                    // morphing (Livewire) may have removed the button: add it again when it is missing
                    var next = input.nextElementSibling;
                    if (next && next.classList.contains('kb')) return;

                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn gray kb';
                    btn.textContent = '⌨';
                    btn.setAttribute('aria-label', 'Klaviatura');
                    input.insertAdjacentElement('afterend', btn);

                    btn.addEventListener('click', function () {
                        // inputmode must change before the field takes focus, so blur first.
                        input.setAttribute('inputmode', input.type === 'number' ? 'decimal' : 'text');
                        input.setAttribute('data-kb-open', '1');
                        input.blur();
                        input.focus();
                    });

                    if (!input.hasAttribute('data-kb-bound')) {
                        input.setAttribute('data-kb-bound', '1');
                        input.addEventListener('blur', function () {
                            if (input.hasAttribute('data-kb-open')) {
                                input.removeAttribute('data-kb-open');
                                input.setAttribute('inputmode', 'none');
                            }
                        });
                    }
                });
            }
            setup();
            document.addEventListener('livewire:init', function () {
                Livewire.hook('commit', function (ctx) { ctx.succeed(function () { requestAnimationFrame(setup); }); });
            });
            // Esc closes the keyboard first, then the page handlers run.
            document.addEventListener('keydown', function (e) {
                var open = document.querySelector('[data-kb-open]');
                if (e.key === 'Escape' && open) { e.stopImmediatePropagation(); open.blur(); }
            }, true);
        })();

        // Bluetooth printing: the server sends the ZPL label as an event; hand it to the printer app on this device.
        // 1) an Android wrapper exposing AndroidPrinter.printRaw(base64), else 2) the RawBT app through its intent link.
        window.BT_SERVICES = ['000018f0-0000-1000-8000-00805f9b34fb', '0000ffe0-0000-1000-8000-00805f9b34fb', '49535343-fe7d-4ae5-8fa9-9fafd205e455', 'e7810a71-73ae-499d-8c15-faa9aef0c3f2'];
        // Web Bluetooth (Chrome, HTTPS, BLE printers): write the label to the first writable characteristic of the chosen device.
        async function webBtSend(address, bytes) {
            const devices = navigator.bluetooth.getDevices ? await navigator.bluetooth.getDevices() : [];
            const dev = devices.find(function (d) { return d.id === address; });
            if (!dev) throw new Error('no device');
            const server = await dev.gatt.connect();
            for (const svc of await server.getPrimaryServices()) {
                for (const ch of await svc.getCharacteristics()) {
                    if (ch.properties.write || ch.properties.writeWithoutResponse) {
                        for (let i = 0; i < bytes.length; i += 100) {
                            const part = bytes.slice(i, i + 100);
                            if (ch.properties.writeWithoutResponse) await ch.writeValueWithoutResponse(part); else await ch.writeValue(part);
                        }
                        return;
                    }
                }
            }
            throw new Error('no writable characteristic');
        }
        window.btPrint = async function (zpl, address) {
            var bytes = new TextEncoder().encode(zpl);
            var bin = '';
            bytes.forEach(function (b) { bin += String.fromCharCode(b); });
            var b64 = btoa(bin);
            // 1) Android wrapper: print to the chosen paired device, else its default printer
            if (window.AndroidPrinter) {
                if (address && typeof window.AndroidPrinter.printTo === 'function') { window.AndroidPrinter.printTo(address, b64); return; }
                if (typeof window.AndroidPrinter.printRaw === 'function') { window.AndroidPrinter.printRaw(b64); return; }
            }
            // 2) Web Bluetooth device picked on the Printer page
            if (address && window.isSecureContext && navigator.bluetooth) {
                try { await webBtSend(address, bytes); return; } catch (e) { /* fall through to RawBT */ }
            }
            // 3) the RawBT app (it prints to the printer chosen in RawBT)
            window.location.href = 'intent:base64,' + b64 + '#Intent;scheme=rawbt;launchFlags=0x10000000;package=ru.a402d.rawbtprinter;end;';
        };
        window.addEventListener('bt-print', function (e) {
            var d = e.detail || {};
            var zpl = d.zpl || (d[0] && d[0].zpl) || '';
            var address = d.address || (d[0] && d[0].address) || '';
            if (zpl) window.btPrint(zpl, address);
        });

    </script>
    @stack('scripts')
</body>
</html>
