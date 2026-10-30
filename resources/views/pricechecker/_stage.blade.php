{{--
    The checker screen. Used by the terminal (full viewport), the pairing page and the live preview in the admin (fixed box).
    $d design (CheckerDesign), $title bar text, $state idle|result|error|auth, $item CheckerItem::payload() or null,
    $w/$h CSS size of the physical screen, $u CSS length of 1% of its shorter side. The design rotation turns the content inside the box.
    $preview true in the admin (placeholders for missing pictures, a fake QR), $live true on the pairing page (real form, $errors list).
--}}
@php
    $r = $d['rotation'];
    $place = match ($r) {
        90 => "width:{$h};height:{$w};left:{$w};top:0;transform:rotate(90deg)",
        180 => "width:{$w};height:{$h};left:0;top:0;transform:rotate(180deg)",
        270 => "width:{$h};height:{$w};left:0;top:{$h};transform:rotate(270deg)",
        default => "width:{$w};height:{$h};left:0;top:0",
    };
    $item = $item ?? [];
    $preview = $preview ?? false;
    $live = $live ?? false;
    $header = $d['header_text'] !== '' ? $d['header_text'] : $title;
    $logo = \App\Support\CheckerDesign::imageUrl($d['logo']);
    $showLogo = $d['show_logo'] && $logo;
    $banners = array_map(fn ($b) => \App\Support\CheckerDesign::imageUrl($b), $d['banners']);
    $slideSecs = $d['banner_interval'];
    $loopSecs = max(1, count($banners)) * $slideSecs;
    $sid = $id ?? 'pcs';
    $arrows = ['up' => '↑', 'down' => '↓', 'left' => '←', 'right' => '→'];
    $gone = fn ($v) => empty($v) ? 'hidden' : '';
    $chips = [];
    if ($d['show_code']) { $chips['code'] = '№ '; }
    if ($d['show_article']) { $chips['article'] = 'Art. '; }
    if ($d['show_group']) { $chips['group'] = ''; }
@endphp
<div style="position:relative;width:{{ $w }};height:{{ $h }};overflow:hidden;background:#000">
    <div class="pcs" id="{{ $id ?? 'pcs' }}" data-state="{{ $state }}" data-layout="{{ $d['layout'] }}" data-img="{{ $d['image_pos'] }}"
         data-arrow="{{ $d['scan_arrow'] }}" data-stock="{{ $item['stock']['state'] ?? '' }}" @if ($d['bg_image']) data-bgimg @endif
         style="position:absolute;transform-origin:0 0;{{ $place }};--u:{{ $u }};{{ \App\Support\CheckerDesign::cssVars($d) }}{{ $state === 'result' && ! empty($item['scheme_vars']) ? ';'.$item['scheme_vars'] : '' }}">
        @if ($d['show_header'])
            <div class="pcs-bar">
                @if ($showLogo)<img class="pcs-logo" src="{{ $logo }}" alt="">@endif
                <span class="pcs-bar-text">{{ $header }}</span>
                @if ($d['show_clock'])<span class="pcs-clock" data-clock>{{ now()->format('H:i') }}</span>@endif
            </div>
        @endif
        <div class="pcs-main">
            @if ($banners)
                @if (count($banners) > 1)
                    @php $share = round(100 / count($banners), 2); @endphp
                    <style>@keyframes pcs-slide-{{ $sid }}{0%{opacity:0}3%{opacity:1}{{ $share - 3 }}%{opacity:1}{{ $share }}%{opacity:0}100%{opacity:0}}</style>
                @endif
                <div class="pcs-banners" aria-hidden="true">
                    @foreach ($banners as $i => $src)
                        <img src="{{ $src }}" alt="" @if (count($banners) > 1) style="animation:pcs-slide-{{ $sid }} {{ $loopSecs }}s linear {{ $i * $slideSecs }}s infinite both;opacity:0" @endif>
                    @endforeach
                </div>
            @endif
            <div class="pcs-idle @if ($banners) pcs-idle-over @endif" data-s="idle">
                @if ($showLogo && ! $banners)<img class="pcs-logo-big" src="{{ $logo }}" alt="">@endif
                <div class="pcs-hint">{{ $d['hint_text'] }}</div>
                @if ($d['scan_arrow'] !== 'none')<div class="pcs-arrow" aria-hidden="true">{{ $arrows[$d['scan_arrow']] }}</div>@endif
            </div>

            <div class="pcs-result" data-s="result">
                @if ($d['show_image'] || $d['show_barcode'])
                    <div class="pcs-media">
                        @if ($d['show_image'])
                            <div class="pcs-img">
                                <img data-f="image" src="{{ $item['image'] ?? '' }}" alt="" @if (empty($item['image'])) hidden @endif>
                                <img class="pcs-img-ph" src="{{ $logo ?: asset('img/logo.png') }}" alt="" @if (! empty($item['image'])) hidden @endif>
                            </div>
                        @endif
                        @if ($d['show_barcode'])<div class="pcs-barsbox" data-svg="barcode_svg">{!! $item['barcode_svg'] ?? '' !!}</div>@endif
                    </div>
                @endif
                <div class="pcs-info">
                    <div class="pcs-name" data-f="name">{{ $item['name'] ?? '' }}</div>
                    <div class="pcs-priceblock">
                        <div class="pcs-opt pcs-old" {{ $gone($item['old_price_text'] ?? '') }}><s><span data-f="old_price_text">{{ $item['old_price_text'] ?? '' }}</span> {{ $d['currency_text'] }}</s> <span class="pcs-badge">{{ $d['promo_text'] }}</span></div>
                        <div class="pcs-price"><span data-f="price_text">{{ $item['price_text'] ?? '' }}</span> <small>{{ $d['currency_text'] }}@if ($d['show_unit']) / <span data-f="unit">{{ $item['unit'] ?? '' }}</span>@endif</small></div>
                        <div class="pcs-opt pcs-second" {{ $gone($item['second_price_text'] ?? '') }}>{{ $d['second_price_label'] }}: <b data-f="second_price_text">{{ $item['second_price_text'] ?? '' }}</b> {{ $d['currency_text'] }}</div>
                    </div>
                    @if ($d['show_stock'])<div class="pcs-opt pcs-stock" {{ $gone($item['stock']['text'] ?? '') }}><span data-f="stock_text">{{ $item['stock']['text'] ?? '' }}</span></div>@endif
                    @if ($chips)
                        <div class="pcs-chips">
                            @foreach ($chips as $field => $prefix)
                                <span class="pcs-opt pcs-chip" {{ $gone($item[$field] ?? '') }}>{{ $prefix }}<span data-f="{{ $field }}">{{ $item[$field] ?? '' }}</span></span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="pcs-err" data-s="error">
                <div class="pcs-err-title">{{ $d['not_found_text'] }}</div>
                <div class="pcs-err-hint">{{ $d['not_found_hint'] }}</div>
            </div>

            <div class="pcs-auth" data-s="auth">
                @if ($showLogo)<img class="pcs-logo-big" src="{{ $logo }}" alt="">@endif
                <div class="pcs-auth-title">{{ $d['auth_title'] }}</div>
                <div class="pcs-auth-text">{{ $d['auth_text'] }}</div>
                @if ($live)
                    @foreach ($errors->all() as $error)<div class="pcs-auth-err">{{ $error }}</div>@endforeach
                    <form method="POST" action="{{ route('checker.activate') }}">
                        @csrf
                        <input class="pcs-auth-input" name="code" autocomplete="off" autofocus placeholder="{{ __('QR kod') }}">
                    </form>
                @else
                    <svg class="pcs-qr" viewBox="0 0 21 21" aria-hidden="true"><path fill="currentColor" fill-rule="evenodd" d="M0 0h7v7H0zM1 1v5h5V1zM2 2h3v3H2zM14 0h7v7h-7zM15 1v5h5V1zM16 2h3v3h-3zM0 14h7v7H0zM1 15v5h5v-5zM2 16h3v3H2zM8 0h1v1H8zM10 1h2v1h-2zM8 3h2v1H8zM11 3h1v2h-1zM9 5h2v1H9zM8 8h2v1H8zM11 8h3v1h-3zM15 8h1v2h-1zM18 8h3v1h-3zM0 8h2v1H0zM3 8h1v2H3zM5 9h3v1H5zM9 10h2v2H9zM12 10h1v1h-1zM17 10h2v1h-2zM0 11h1v2H0zM2 11h3v1H2zM6 12h2v1H6zM14 12h2v2h-2zM18 12h3v1h-3zM8 14h2v1H8zM11 14h2v1h-2zM17 14h1v2h-1zM20 14h1v3h-1zM9 16h1v2H9zM12 16h2v1h-2zM15 17h2v1h-2zM8 19h3v1H8zM12 19h1v2h-1zM14 19h3v1h-3zM18 18h1v3h-1z"/></svg>
                @endif
            </div>
        </div>
        @if ($d['show_footer'])<div class="pcs-bar pcs-bar-foot"><span class="pcs-bar-text">{{ $d['footer_text'] !== '' ? $d['footer_text'] : config('menu.app_name') }}</span></div>@endif
    </div>
</div>
