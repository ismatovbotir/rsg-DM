@section('title', $item->name)

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <div class="text-xs text-ink-muted"><a class="lnk" href="{{ route('admin.items.index') }}">Tovarlar</a> / {{ $item->group?->path ?? 'guruhsiz' }}</div>

    {{-- header: title, status and the actions of the document --}}
    <div class="bar">
        <h1 class="m-0 text-base font-semibold">{{ $item->name }}</h1>
        <span class="badge {{ $item->is_active ? 'completed' : 'error' }}">{{ $item->is_active ? 'Faol' : 'Nofaol' }}</span>
        <span class="badge">Adines · faqat ko‘rish</span>
        <span class="grow"></span>
        <label class="m-0 flex items-center gap-1 text-xs">Nusxa <input type="number" min="1" max="100" wire:model="copies" class="!w-16"></label>
        <button type="button" class="btn sm" wire:click="printTag" wire:loading.attr="disabled" wire:target="printTag" @disabled(! $printer) title="{{ $printer ? $printer->name.' ('.$printer->address.')' : 'Yorliq printeri tanlanmagan' }}">Yorliq chop etish</button>
        <button type="button" class="btn sm gray" wire:click="queueTag" @disabled($queued)>{{ $queued ? 'Navbatda' : 'Chop etish navbatiga' }}</button>
        <a class="btn sm gray" href="{{ route('admin.items.index') }}">Yopish</a>
    </div>
    @error('copies')<div class="flash error">{{ $message }}</div>@enderror

    {{-- key figures --}}
    <div class="grid grid-cols-2 gap-px overflow-hidden rounded border border-line bg-line sm:grid-cols-4">
        @foreach ([['Kod', $item->code], ['Asosiy narx', number_format($item->price, 0, '.', ' ')], ['Jami qoldiq', $stocks->isEmpty() ? '—' : ($stocks->sum('qty') + 0).' '.$item->unit], ['Shtrix-kodlar', $item->barcodes->count() + ($item->barcode ? 1 : 0)]] as [$label, $value])
            <div class="bg-white px-3 py-2"><div class="text-[11px] uppercase text-ink-muted">{{ $label }}</div><div class="text-base font-semibold tabular-nums">{{ $value }}</div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="tabs">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">Asosiy</button>
            <button type="button" :class="{ on: tab === 'barcodes' }" @click="tab = 'barcodes'">Shtrix-kodlar ({{ $item->barcodes->count() + ($item->barcode ? 1 : 0) }})</button>
            <button type="button" :class="{ on: tab === 'prices' }" @click="tab = 'prices'">Narxlar</button>
            <button type="button" :class="{ on: tab === 'stock' }" @click="tab = 'stock'">Qoldiq ({{ $stocks->count() }})</button>
        </div>

        <div class="tabpanel on" x-show="tab === 'main'">
            <div class="full">
                <table>
                    <tbody>
                        <tr><th class="w-56">Kod</th><td>{{ $item->code }}</td></tr>
                        <tr><th>Artikul</th><td>{{ $item->article ?: '—' }}</td></tr>
                        <tr><th>Nomi</th><td>{{ $item->name }}</td></tr>
                        <tr><th>Guruh</th><td>{{ $item->group?->path ?? '—' }}</td></tr>
                        <tr><th>Birlik</th><td>{{ $item->unit }}@if ($item->is_weighed) · tarozi tovar @endif</td></tr>
                        <tr><th>Yaroqlilik muddati</th><td>{{ $item->shelf_life_days !== null ? $item->shelf_life_days.' kun' : '—' }}</td></tr>
                        <tr><th>QQS</th><td>{{ $item->vat_rate + 0 }} %</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="full">
                <label>Tovar rasmi (narx tekshirgich ekrani uchun; Adines rasm yubormaydi) <input type="file" accept="image/*" wire:model="photo"></label>
                @error('photo')<div class="flash error">{{ $message }}</div>@enderror
                @if ($item->image)
                    <img class="mb-2 h-24 rounded border border-line" src="{{ \App\Support\CheckerDesign::imageUrl($item->image) }}" alt="">
                    <button type="button" class="btn sm gray" wire:click="removePhoto">Rasmni olib tashlash</button>
                @endif
            </div>
        </div>

        <div class="tabpanel on" x-show="tab === 'barcodes'" x-cloak>
            <table class="full">
                <thead><tr><th>Shtrix-kod</th><th class="num">Qadoqdagi miqdor</th><th>Holat</th></tr></thead>
                <tbody>
                    @if ($item->barcode)<tr><td>{{ $item->barcode }} <span class="badge">asosiy</span></td><td class="num">1</td><td><span class="badge completed">Faol</span></td></tr>@endif
                    @foreach ($item->barcodes as $b)
                        <tr><td>{{ $b->barcode }}</td><td class="num">{{ $b->qty_in_pack + 0 }}</td><td><span class="badge {{ $b->is_active ? 'completed' : 'error' }}">{{ $b->is_active ? 'Faol' : 'Nofaol' }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tabpanel on" x-show="tab === 'prices'" x-cloak>
            <table class="full">
                <thead><tr><th>Narx turi</th><th class="num">Narx</th></tr></thead>
                <tbody>
                    <tr><td>Asosiy narx</td><td class="num">{{ number_format($item->price, 0, '.', ' ') }}</td></tr>
                    @foreach ($priceTypes as $pt)
                        @php $row = $item->prices->firstWhere('price_type_id', $pt->id); @endphp
                        <tr><td>{{ $pt->name }}</td><td class="num">{{ $row ? number_format($row->price, 0, '.', ' ') : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tabpanel on" x-show="tab === 'stock'" x-cloak>
            <table class="full">
                <thead><tr><th>Ombor</th><th class="num">Qoldiq</th><th class="num">Tannarx</th><th>Sana</th></tr></thead>
                <tbody>
                    @forelse ($stocks as $st)
                        <tr><td>{{ $st->name }}</td><td class="num">{{ $st->qty + 0 }} {{ $item->unit }}</td><td class="num">{{ $st->cost !== null ? number_format($st->cost, 0, '.', ' ') : '' }}</td><td>{{ $st->stock_date ? \Illuminate\Support\Carbon::parse($st->stock_date)->format('d.m.Y') : '' }}</td></tr>
                    @empty
                        <tr><td colspan="4">Adines dan qoldiq kelmagan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
