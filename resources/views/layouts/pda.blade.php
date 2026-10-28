<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, interactive-widget=resizes-content">
    <meta name="theme-color" content="#2a5d9f">
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

    </script>
    @stack('scripts')
</body>
</html>
