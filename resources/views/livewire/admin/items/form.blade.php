@section('title', $recordId ? 'Tovarni tahrirlash' : 'Yangi tovar')

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <div class="tabs">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">Asosiy</button>
            <button type="button" :class="{ on: tab === 'barcodes' }" @click="tab = 'barcodes'">Shtrix-kodlar ({{ count($barcodes) + ($barcode !== '' ? 1 : 0) }})</button>
            <button type="button" :class="{ on: tab === 'prices' }" @click="tab = 'prices'">Narxlar</button>
            @if ($recordId)<button type="button" :class="{ on: tab === 'stock' }" @click="tab = 'stock'">Qoldiq ({{ $stocks->count() }})</button>@endif
        </div>

        <div class="tabpanel on" x-show="tab === 'main'">
            <label>Kod <input wire:model="code" required></label>
            <label>Artikul <input wire:model="article"></label>
            <label>Nomi <input wire:model="name" required></label>
            <label>Guruh
                <select wire:model="group_id">
                    <option value="">— guruhsiz —</option>
                    @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->path }}</option>@endforeach
                </select>
            </label>
            <label>Birlik <input wire:model="unit" required></label>
            <label>Yaroqlilik muddati (kun) <input type="number" min="0" max="36500" wire:model="shelf_life_days"></label>
            <label>QQS (%) <input type="number" step="0.01" min="0" max="100" wire:model="vat_rate"></label>
            <div class="full">
                <label class="chk"><input type="checkbox" wire:model="is_weighed"> Tarozi tovar (o‘lchanadigan)</label>
                <label class="chk"><input type="checkbox" wire:model="is_active"> Faol (PDA da ko‘rinadi)</label>
            </div>
        </div>

        <div class="tabpanel on" x-show="tab === 'barcodes'" x-cloak>
            <label>Asosiy shtrix-kod <input wire:model="barcode"></label>
            <p class="full hint">Bitta tovarda bir nechta shtrix-kod bo‘lishi mumkin. Korobka (qadoq) kodi uchun miqdorni kiriting: skanerlanganda shuncha dona yoziladi. Nofaol kod skanerlanmaydi.</p>
            <table class="full">
                <thead><tr><th>Shtrix-kod</th><th class="w-40">Qadoqdagi miqdor</th><th class="w-24">Faol</th><th class="w-16"></th></tr></thead>
                <tbody>
                    @foreach ($barcodes as $i => $row)
                        <tr wire:key="bc-{{ $i }}">
                            <td><input wire:model="barcodes.{{ $i }}.barcode"></td>
                            <td><input type="number" step="any" min="0.001" wire:model="barcodes.{{ $i }}.qty_in_pack"></td>
                            <td><input type="checkbox" wire:model="barcodes.{{ $i }}.is_active"></td>
                            <td><button type="button" class="btn red" wire:click="removeBarcode({{ $i }})" aria-label="O‘chirish">✕</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="full"><button type="button" class="btn gray" wire:click="addBarcode">+ Shtrix-kod qo‘shish</button></div>
        </div>

        <div class="tabpanel on" x-show="tab === 'prices'" x-cloak>
            <label>Asosiy narx <input type="number" step="0.01" min="0" wire:model="price" required></label>
            @if ($priceTypes->isNotEmpty())
                <fieldset>
                    <legend>Narx turlari bo‘yicha narxlar (bo‘sh = asosiy narx)</legend>
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-6">
                        @foreach ($priceTypes as $pt)
                            <label>{{ $pt->name }} <input type="number" step="0.01" min="0" wire:model="prices.{{ $pt->id }}"></label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
        </div>

        @if ($recordId)
            <div class="tabpanel on" x-show="tab === 'stock'" x-cloak>
                <table class="full">
                    <thead><tr><th>Ombor</th><th class="num">Qoldiq</th><th class="num">Tannarx</th><th>Sana</th></tr></thead>
                    <tbody>
                        @forelse ($stocks as $st)
                            <tr><td>{{ $st->name }}</td><td class="num">{{ $st->qty + 0 }} {{ $unit }}</td><td class="num">{{ $st->cost !== null ? number_format($st->cost, 0, '.', ' ') : '' }}</td><td>{{ $st->stock_date ? \Illuminate\Support\Carbon::parse($st->stock_date)->format('d.m.Y') : '' }}</td></tr>
                        @empty
                            <tr><td colspan="4">Adines dan qoldiq kelmagan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="actions">
            <button class="btn">Saqlash</button>
            <a class="btn gray" href="{{ route('admin.items.index') }}">Bekor qilish</a>
        </div>
    </form>
</div>
