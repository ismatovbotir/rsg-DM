@section('title', $checker ? 'Narx tekshirgich: '.$checker->name : 'Yangi narx tekshirgich')

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <div class="card">
        <div class="tabs">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">{{ __('Asosiy') }}</button>
            @if ($checker)
                <button type="button" :class="{ on: tab === 'qr' }" @click="tab = 'qr'">{{ __('Token va QR') }}</button>
            @endif
        </div>

        <form class="tabpanel on" x-show="tab === 'main'" wire:submit="save">
            <label>{{ __('Nomi (masalan: Kirish zali)') }} <input wire:model="name" required></label>
            <label>{{ __('Ko‘rsatiladigan narx turi') }}
                <select wire:model="price_type_id">
                    <option value="">{{ __('— asosiy narx —') }}</option>
                    @foreach ($priceTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach
                </select>
            </label>
            <label>{{ __('Ekran dizayni') }}
                <select wire:model="theme_id">
                    <option value="">{{ __('— asosiy dizayn —') }}</option>
                    @foreach ($themes as $theme)<option value="{{ $theme->id }}">{{ $theme->name }}{{ $theme->is_default ? ' (asosiy)' : '' }}</option>@endforeach
                </select>
                <span class="hint">{{ __('Dizaynlarni') }} <a class="lnk" href="{{ route('admin.themes.index') }}">{{ __('Dizaynlar → Narx tekshirgich dizaynlari') }}</a> {{ __('bo‘limida yaratasiz.') }}</span>
            </label>
            <div class="full"><label class="chk"><input type="checkbox" wire:model="is_active"> {{ __('Faol') }}</label></div>
            @unless ($checker)<p class="hint">{{ __('Saqlaganingizda token yaratiladi va QR kod ko‘rsatiladi.') }}</p>@endunless
            <div class="actions">
                <button class="btn">{{ __('Saqlash') }}</button>
                <a class="btn gray" href="{{ route('admin.checkers.index') }}">{{ __('Ro‘yxat') }}</a>
            </div>
        </form>

        @if ($checker)
            <div class="tabpanel on" x-show="tab === 'qr'" x-cloak>
                <div class="full flex flex-wrap items-start gap-8">
                    @if ($qr)
                        <div class="rounded-lg border border-line bg-white p-3">{!! $qr !!}</div>
                    @endif
                    <div class="min-w-[260px] flex-1">
                        @if ($qr)
                            <p>{{ __('Terminalda') }} <code>{{ url('/pricechecker') }}</code> {{ __('sahifasini oching va shu QR kodni skanerlang (kamera yoki skaner bilan). Terminal shu tokenni eslab qoladi.') }}</p>
                            <p class="hint">{{ __('Token QR ichidagi havola:') }} <code class="break-all">{{ $url }}</code></p>
                            <p class="hint">{{ __('Token terminal ulangandan keyin ham shu yerda ko‘rinadi. Yangisini yaratsangiz, eski QR kod va unga ulangan terminallar ishlamay qoladi.') }}</p>
                        @else
                            <p>{{ __('Bu narx tekshirgichda token yo‘q. Yangisini yarating.') }}</p>
                        @endif
                        <button type="button" class="btn" wire:click="regenerate" wire:confirm="{{ __('Yangi token yaratilsa, hozirgi terminal chiqib ketadi. Davom etilsinmi?') }}">{{ $qr ? 'Yangi token yaratish' : 'Token yaratish' }}</button>
                        <p>
                            @if ($checker->is_connected)<span class="badge completed">{{ __('Ulangan') }} {{ $checker->activated_at?->format('d.m.Y H:i') }}</span>@else<span class="badge">{{ __('Hali ulanmagan') }}</span>@endif
                            @if ($checker->last_seen_at)<span class="hint">{{ __('Oxirgi faollik:') }} {{ $checker->last_seen_at->format('d.m.Y H:i') }}</span>@endif
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
