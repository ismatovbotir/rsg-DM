@section('title', $checker ? 'Narx tekshirgich: '.$checker->name : 'Yangi narx tekshirgich')

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <div class="card">
        <div class="tabs">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">Asosiy</button>
            @if ($checker)
                <button type="button" :class="{ on: tab === 'qr' }" @click="tab = 'qr'">Token va QR</button>
                <button type="button" :class="{ on: tab === 'design' }" @click="tab = 'design'">Ekran dizayni</button>
            @endif
        </div>

        <form class="tabpanel on" x-show="tab === 'main'" wire:submit="save">
            <label>Nomi (masalan: Kirish zali) <input wire:model="name" required></label>
            <label>Ko‘rsatiladigan narx turi
                <select wire:model="price_type_id">
                    <option value="">— asosiy narx —</option>
                    @foreach ($priceTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach
                </select>
            </label>
            <div class="full"><label class="chk"><input type="checkbox" wire:model="is_active"> Faol</label></div>
            @unless ($checker)<p class="hint">Saqlaganingizda token yaratiladi va QR kod ko‘rsatiladi.</p>@endunless
            <div class="actions">
                <button class="btn">Saqlash</button>
                <a class="btn gray" href="{{ route('admin.checkers.index') }}">Ro‘yxat</a>
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
                            <p>Terminalda <code>{{ url('/pricechecker') }}</code> sahifasini oching va shu QR kodni skanerlang (kamera yoki skaner bilan). Terminal shu tokenni eslab qoladi.</p>
                            <p class="hint">Token QR ichidagi havola: <code class="break-all">{{ $url }}</code></p>
                            <p class="hint">Token terminal ulangandan keyin ham shu yerda ko‘rinadi. Yangisini yaratsangiz, eski QR kod va unga ulangan terminallar ishlamay qoladi.</p>
                        @else
                            <p>Bu narx tekshirgichda token yo‘q. Yangisini yarating.</p>
                        @endif
                        <button type="button" class="btn" wire:click="regenerate" wire:confirm="Yangi token yaratilsa, hozirgi terminal chiqib ketadi. Davom etilsinmi?">{{ $qr ? 'Yangi token yaratish' : 'Token yaratish' }}</button>
                        <p>
                            @if ($checker->is_connected)<span class="badge completed">Ulangan {{ $checker->activated_at?->format('d.m.Y H:i') }}</span>@else<span class="badge">Hali ulanmagan</span>@endif
                            @if ($checker->last_seen_at)<span class="hint">Oxirgi faollik: {{ $checker->last_seen_at->format('d.m.Y H:i') }}</span>@endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="tabpanel on" x-show="tab === 'design'" x-cloak>
                <div class="full"><livewire:admin.checkers.designer :checker-id="$checker->id" :key="'designer-'.$checker->id" /></div>
            </div>
        @endif
    </div>
</div>
