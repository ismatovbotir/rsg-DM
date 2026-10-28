<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>Narx tekshirgich</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body { display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: system-ui, sans-serif; background: #1f3a5f; color: #fff; text-align: center; padding: 24px; }
        h1 { font-size: clamp(26px, 5vw, 44px); margin: 0 0 12px; }
        p { font-size: 20px; opacity: .85; max-width: 560px; }
        .err { background: #fdeaea; color: #a32727; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; }
        input { font: inherit; font-size: 20px; padding: 12px 14px; border-radius: 8px; border: 0; width: min(420px, 90vw); }
    </style>
</head>
<body>
    <h1>Terminalni faollashtiring</h1>
    <p>Boshqaruv panelidagi «Narx tekshirgichlar» bo‘limida ko‘rsatilgan QR kodni skanerlang.</p>
    @foreach ($errors->all() as $error)<div class="err">{{ $error }}</div>@endforeach
    <form method="POST" action="{{ route('checker.activate') }}">
        @csrf
        <input name="code" autocomplete="off" autofocus placeholder="QR kod">
    </form>
</body>
</html>
