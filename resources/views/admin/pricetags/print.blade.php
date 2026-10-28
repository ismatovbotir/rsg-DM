<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <title>Narx yorliqlari</title>
    <style>
        body { margin: 10mm; font-family: system-ui, sans-serif; }
        .sheet { display: grid; grid-template-columns: repeat(3, 60mm); gap: 4mm; }
        .tag { height: 40mm; border: 1px solid #000; padding: 3mm; display: flex; flex-direction: column; justify-content: space-between; break-inside: avoid; }
        .name { font-size: 12pt; line-height: 1.2; }
        .price { font-size: 26pt; font-weight: 700; text-align: right; }
        .price small { font-size: 10pt; font-weight: 400; }
        .code { font-size: 9pt; letter-spacing: 1px; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <p class="noprint">{{ $checks->count() }} ta yorliq. Chop etgandan so‘ng “Chop etilgan deb belgilash” tugmasini bosing.</p>
    <div class="sheet">
        @foreach ($checks as $check)
            <div class="tag">
                <div class="name">{{ $check->item->name }}</div>
                <div class="price">{{ number_format($check->item->price, 0, '.', ' ') }} <small>so‘m / {{ $check->item->unit }}</small></div>
                <div class="code">{{ $check->item->barcode ?: $check->item->code }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
