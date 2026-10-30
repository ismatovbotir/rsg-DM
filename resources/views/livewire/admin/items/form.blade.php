@section('title', $item->name)

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <div class="text-xs text-ink-muted"><a class="lnk" href="{{ route('admin.items.index') }}">{{ __('Tovarlar') }}</a> / {{ $item->group?->path ?? 'guruhsiz' }}</div>

    {{-- header: title, status and the actions of the document --}}
    <div class="bar">
        <h1 class="m-0 text-base font-semibold">{{ $item->name }}</h1>
        <span class="badge {{ $item->is_active ? 'completed' : 'error' }}">{{ $item->is_active ? 'Faol' : 'Nofaol' }}</span>
        <span class="badge">{{ __('Adines · faqat ko‘rish') }}</span>
        <span class="grow"></span>
        <label class="m-0 flex items-center gap-1 text-xs">{{ __('Nusxa') }} <input type="number" min="1" max="100" wire:model="copies" class="!w-16"></label>
        <button type="button" class="btn sm" wire:click="printTag" wire:loading.attr="disabled" wire:target="printTag" @disabled(! $printer) title="{{ $printer ? $printer->name.' ('.$printer->address.')' : 'Yorliq printeri tanlanmagan' }}">{{ __('Yorliq chop etish') }}</button>
        <button type="button" class="btn sm gray" wire:click="queueTag" @disabled($queued)>{{ $queued ? 'Navbatda' : 'Chop etish navbatiga' }}</button>
        <a class="btn sm gray" href="{{ route('admin.items.index') }}">{{ __('Yopish') }}</a>
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
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">{{ __('Asosiy') }}</button>
            <button type="button" :class="{ on: tab === 'barcodes' }" @click="tab = 'barcodes'">{{ __('Shtrix-kodlar (') }}{{ $item->barcodes->count() + ($item->barcode ? 1 : 0) }})</button>
            <button type="button" :class="{ on: tab === 'prices' }" @click="tab = 'prices'">{{ __('Narxlar') }}</button>
            <button type="button" :class="{ on: tab === 'stock' }" @click="tab = 'stock'">{{ __('Qoldiq (') }}{{ $stocks->count() }})</button>
        </div>

        <div class="tabpanel on" x-show="tab === 'main'">
            <div class="full">
                <table>
                    <tbody>
                        <tr><th class="w-56">{{ __('Kod') }}</th><td>{{ $item->code }}</td></tr>
                        <tr><th>{{ __('Artikul') }}</th><td>{{ $item->article ?: '—' }}</td></tr>
                        <tr><th>{{ __('Nomi') }}</th><td>{{ $item->name }}</td></tr>
                        <tr><th>{{ __('Guruh') }}</th><td>{{ $item->group?->path ?? '—' }}</td></tr>
                        <tr><th>{{ __('Birlik') }}</th><td>{{ $item->unit }}@if ($item->is_weighed) {{ __('· tarozi tovar') }} @endif</td></tr>
                        <tr><th>{{ __('Yaroqlilik muddati') }}</th><td>{{ $item->shelf_life_days !== null ? $item->shelf_life_days.' kun' : '—' }}</td></tr>
                        <tr><th>{{ __('QQS') }}</th><td>{{ $item->vat_rate + 0 }} %</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="full">
                <label>{{ __('Tovar rasmi (narx tekshirgich ekrani uchun; Adines rasm yubormaydi)') }} <input type="file" accept="image/*" wire:model="photo"></label>
                @error('photo')<div class="flash error">{{ $message }}</div>@enderror
                @if ($item->image)
                    <img class="mb-2 h-24 rounded border border-line" src="{{ \App\Support\CheckerDesign::imageUrl($item->image) }}" alt="">
                    <button type="button" class="btn sm gray" wire:click="removePhoto">{{ __('Rasmni olib tashlash') }}</button>
                @endif
            </div>
        </div>

        <div class="tabpanel on" x-show="tab === 'barcodes'" x-cloak>
            <table class="full">
                <thead><tr><th>{{ __('Shtrix-kod') }}</th><th class="num">{{ __('Qadoqdagi miqdor') }}</th><th>{{ __('Holat') }}</th></tr></thead>
                <tbody>
                    @if ($item->barcode)<tr><td>{{ $item->barcode }} <span class="badge">{{ __('asosiy') }}</span></td><td class="num">1</td><td><span class="badge completed">{{ __('Faol') }}</span></td></tr>@endif
                    @foreach ($item->barcodes as $b)
                        <tr><td>{{ $b->barcode }}</td><td class="num">{{ $b->qty_in_pack + 0 }}</td><td><span class="badge {{ $b->is_active ? 'completed' : 'error' }}">{{ $b->is_active ? 'Faol' : 'Nofaol' }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tabpanel on" x-show="tab === 'prices'" x-cloak>
            <table class="full">
                <thead><tr><th>{{ __('Narx turi') }}</th><th class="num">{{ __('Narx') }}</th></tr></thead>
                <tbody>
                    <tr><td>{{ __('Asosiy narx') }}</td><td class="num">{{ number_format($item->price, 0, '.', ' ') }}</td></tr>
                    @foreach ($priceTypes as $pt)
                        @php $row = $item->prices->firstWhere('price_type_id', $pt->id); @endphp
                        <tr><td>{{ $pt->name }}</td><td class="num">{{ $row ? number_format($row->price, 0, '.', ' ') : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tabpanel on" x-show="tab === 'stock'" x-cloak>
            <table class="full">
                <thead><tr><th>{{ __('Ombor') }}</th><th class="num">{{ __('Qoldiq') }}</th><th class="num">{{ __('Tannarx') }}</th><th>{{ __('Sana') }}</th></tr></thead>
                <tbody>
                    @forelse ($stocks as $st)
                        <tr><td>{{ $st->name }}</td><td class="num">{{ $st->qty + 0 }} {{ $item->unit }}</td><td class="num">{{ $st->cost !== null ? number_format($st->cost, 0, '.', ' ') : '' }}</td><td>{{ $st->stock_date ? \Illuminate\Support\Carbon::parse($st->stock_date)->format('d.m.Y') : '' }}</td></tr>
                    @empty
                        <tr><td colspan="4">{{ __('Adines dan qoldiq kelmagan.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
