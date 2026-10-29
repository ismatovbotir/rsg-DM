<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <title>Badge</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, "Segoe UI", sans-serif; }
        .sheet { display: grid; grid-template-columns: repeat(2, 85.6mm); gap: 6mm; }
        .badge { width: 85.6mm; height: 54mm; border: 1px solid #000; border-radius: 3mm; padding: 4mm; display: flex; gap: 4mm; align-items: center; break-inside: avoid; }
        .info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
        .org { font-size: 9pt; letter-spacing: .08em; text-transform: uppercase; color: #444; }
        .name { font-size: 17pt; font-weight: 700; line-height: 1.15; overflow-wrap: anywhere; }
        .role { font-size: 10pt; color: #333; }
        .hint { font-size: 7pt; color: #666; }
        .qr { width: 34mm; height: 34mm; flex: none; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .noprint { margin: 0 0 6mm; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <p class="noprint">{{ $cards->count() }} ta badge. Chop etish oynasi ochiladi; o‘lcham 85.6 × 54 mm (plastik karta).</p>
    <div class="sheet">
        @foreach ($cards as $card)
            <div class="badge">
                <div class="info">
                    <div class="org">{{ config('menu.app_name') }}</div>
                    <div>
                        <div class="name">{{ $card['user']->name }}</div>
                        <div class="role">{{ $card['user']->role_label }}</div>
                    </div>
                    <div class="hint">Kirish ekranida QR kodni skanerlang</div>
                </div>
                <div class="qr">{!! $card['qr'] !!}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
