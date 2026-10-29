{{-- Item card: summary on top (name, price, stock), details in tabs. --}}
@section('title', $item->name)

<div class="contents" x-data="{ tab: 'main' }" @keydown.window.escape="window.location = '{{ route('directory') }}'">
    <x-pda.page :title="$item->name" :back="route('directory')" :notice="$notice" :failure="$failure">
        <form class="pad scanrow" wire:submit="scan">
            <input class="field" wire:model="code" placeholder="Keyingi tovar: shtrix-kod, kod" autocomplete="off" @kb autofocus x-init="$el.focus()">
            <a class="btn browse" href="{{ route('browse.directory') }}">Tanlash</a>
        </form>

        <div class="pad flex items-start gap-3">
            @if ($image)<img src="{{ $image }}" alt="" class="h-20 w-20 shrink-0 rounded-lg object-cover">@endif
            <div class="min-w-0 flex-1">
                <div class="text-base font-semibold leading-tight">{{ $item->name }}</div>
                <div class="sub">{{ $item->code }}@if ($item->article) · {{ $item->article }}@endif · {{ $item->unit }}</div>
                <div class="mt-1 flex items-end justify-between gap-2">
                    <span class="text-3xl font-bold tabular-nums">{{ number_format($price, 0, '.', ' ') }}</span>
                    @if ($stockTotal !== null)
                        <span class="badge {{ $stockTotal > 0 ? 'completed' : 'error' }}">Qoldiq: {{ $stockTotal }} {{ $item->unit }}</span>
                    @endif
                </div>
                @unless ($item->is_active)<span class="badge error">Faol emas</span>@endunless
            </div>
        </div>

        <div class="ptabs" role="tablist">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">Asosiy</button>
            <button type="button" :class="{ on: tab === 'prices' }" @click="tab = 'prices'">Narxlar</button>
            <button type="button" :class="{ on: tab === 'stock' }" @click="tab = 'stock'">Qoldiq</button>
            <button type="button" :class="{ on: tab === 'codes' }" @click="tab = 'codes'">Shtrix-kod ({{ $item->barcodes->count() + ($item->barcode ? 1 : 0) }})</button>
        </div>

        <div x-show="tab === 'main'" class="pad">
            <div class="kv"><span>Kod</span><span>{{ $item->code }}</span></div>
            @if ($item->article)<div class="kv"><span>Artikul</span><span>{{ $item->article }}</span></div>@endif
            @if ($item->barcode)<div class="kv"><span>Shtrix-kod</span><span>{{ $item->barcode }}</span></div>@endif
            <div class="kv"><span>Guruh</span><span class="text-right">{{ $item->group?->path ?? '—' }}</span></div>
            <div class="kv"><span>O‘lchov birligi</span><span>{{ $item->unit }}@if ($item->is_weighed) · tarozi @endif</span></div>
            <div class="kv"><span>QQS</span><span>{{ $item->vat_rate !== null ? ($item->vat_rate + 0).' %' : '—' }}</span></div>
            <div class="kv"><span>Yaroqlilik muddati</span><span>{{ $item->shelf_life_days !== null ? $item->shelf_life_days.' kun' : '—' }}</span></div>
        </div>

        <div x-show="tab === 'prices'" x-cloak>
            <table class="tbl">
                <thead><tr><th>Narx turi</th><th class="num">Narx</th></tr></thead>
                <tbody>
                    @forelse ($priceTypes as $t)
                        <tr class="{{ $t->id === $shopPriceType ? 'font-bold' : '' }}"><td>{{ $t->name }}@if ($t->id === $shopPriceType) <span class="badge completed">Do‘konim</span>@endif</td><td class="num">{{ number_format($t->price, 0, '.', ' ') }}</td></tr>
                    @empty
                        <tr><td>Asosiy narx</td><td class="num">{{ number_format($item->price, 0, '.', ' ') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'stock'" x-cloak>
            <table class="tbl">
                <thead><tr><th>Ombor</th><th class="num">Qoldiq</th></tr></thead>
                <tbody>
                    @forelse ($stocks as $st)
                        <tr><td>{{ $st->name }}</td><td class="num {{ $st->qty > 0 ? '' : 'text-bad' }}">{{ $st->qty + 0 }} {{ $item->unit }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-[#6b7686]">Qoldiq ma’lumoti yo‘q.</td></tr>
                    @endforelse
                </tbody>
                @if ($stocks->count() > 1)<tfoot><tr class="font-bold"><td>Jami</td><td class="num">{{ $stockTotal }} {{ $item->unit }}</td></tr></tfoot>@endif
            </table>
        </div>

        <div x-show="tab === 'codes'" x-cloak>
            <table class="tbl">
                <thead><tr><th>Shtrix-kod</th><th class="num">Qadoqda</th></tr></thead>
                <tbody>
                    @if ($item->barcode)<tr><td>{{ $item->barcode }} <span class="badge">asosiy</span></td><td class="num">1</td></tr>@endif
                    @foreach ($item->barcodes as $b)
                        <tr class="{{ $b->is_active ? '' : 'opacity-50' }}"><td>{{ $b->barcode }}@unless ($b->is_active) <span class="badge error">o‘chirilgan</span>@endunless</td><td class="num">{{ $b->qty_in_pack + 0 }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-pda.page>
</div>
