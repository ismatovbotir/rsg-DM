<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>Narx tekshirgich</title>
    <link rel="stylesheet" href="{{ asset('css/checker.css') }}?v={{ @filemtime(public_path('css/checker.css')) }}">
    <style>html, body { margin: 0; height: 100%; overflow: hidden; background: #000; }</style>
</head>
<body>
    {{-- the pairing screen wears the default theme --}}
    @include('pricechecker._stage', ['d' => $d, 'title' => config('menu.app_name'), 'state' => 'auth', 'item' => null, 'live' => true, 'w' => '100vw', 'h' => '100vh', 'u' => '1vmin'])
</body>
</html>
