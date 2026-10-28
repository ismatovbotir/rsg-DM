{{--
    The checker screen. Used by the terminal (full viewport) and by the live preview in the admin (fixed box).
    $d design (CheckerDesign), $title bar text, $state idle|result|error, $item sample ['name','price_text','unit','code'] or null,
    $w/$h CSS size of the physical screen, $u CSS length of 1% of its shorter side. The design rotation turns the content inside the box.
--}}
@php
    $r = $d['rotation'];
    $place = match ($r) {
        90 => "width:{$h};height:{$w};left:{$w};top:0;transform:rotate(90deg)",
        180 => "width:{$w};height:{$h};left:0;top:0;transform:rotate(180deg)",
        270 => "width:{$h};height:{$w};left:0;top:{$h};transform:rotate(270deg)",
        default => "width:{$w};height:{$h};left:0;top:0",
    };
    $item = $item ?? null;
    $header = $d['header_text'] !== '' ? $d['header_text'] : $title;
@endphp
<div style="position:relative;width:{{ $w }};height:{{ $h }};overflow:hidden;background:#000">
    <div class="pcs" id="{{ $id ?? 'pcs' }}" data-state="{{ $state }}" data-layout="{{ $d['layout'] }}"
         style="position:absolute;transform-origin:0 0;{{ $place }};--u:{{ $u }};{{ \App\Support\CheckerDesign::cssVars($d) }}">
        @if ($d['show_header'])<div class="pcs-bar">{{ $header }}</div>@endif
        <div class="pcs-main">
            <div class="pcs-hint" data-s="idle">{{ $d['hint_text'] }}</div>
            <div data-s="result">
                <div class="pcs-name" data-f="name">{{ $item['name'] ?? '' }}</div>
                <div>
                    <div class="pcs-price"><span data-f="price">{{ $item['price_text'] ?? '' }}</span> <small>{{ $d['currency_text'] }}@if ($d['show_unit']) / <span data-f="unit">{{ $item['unit'] ?? '' }}</span>@endif</small></div>
                    @if ($d['show_code'])<div class="pcs-meta">№ <span data-f="code">{{ $item['code'] ?? '' }}</span></div>@endif
                </div>
            </div>
            <div class="pcs-err" data-s="error">{{ $d['not_found_text'] }}</div>
        </div>
        @if ($d['show_footer'])<div class="pcs-bar">{{ $d['footer_text'] !== '' ? $d['footer_text'] : config('menu.app_name') }}</div>@endif
    </div>
</div>
