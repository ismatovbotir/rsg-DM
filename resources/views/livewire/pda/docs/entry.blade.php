{{-- Item entry window: qty, price and production date for the picked or scanned item (or the shelf price of a price check). --}}
@section('title', $item->name)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('docs.show', $doc->id) }}'">
    <x-pda.page :notice="$notice" :failure="$failure">
        <x-slot:header>
            <a class="hbtn" href="{{ route('docs.show', $doc->id) }}" aria-label="Orqaga">←</a>
            <span class="title">{{ $item->name }}<small class="block text-xs font-normal opacity-80">{{ $doc->number }}</small></span>
        </x-slot:header>

        <form id="entry" class="pad" wire:submit="save">
            <div class="mb-3">
                <strong>{{ $item->name }}</strong>
                <span class="sub">{{ $item->code }}@if ($item->article) · {{ $item->article }}@endif · {{ $item->unit }}@if ($item->shelf_life_days !== null) · yaroqlilik {{ $item->shelf_life_days }} kun @endif</span>
            </div>

            @if ($doc->isPriceCheck())
                <div class="kv"><span>Tizimdagi narx</span><span class="text-3xl font-bold">{{ number_format($priceSystem, 0, '.', ' ') }}</span></div>
                <label class="lbl">Javondagi narx<span class="req"> *</span>
                    <input class="field" type="number" step="any" min="0" wire:model="shelf_price" required autofocus autocomplete="off" @kb>
                </label>
                <div class="sub">Narx mos kelmasa, yorliq chop etish ro‘yxatiga qo‘shiladi.</div>
            @endif
            @if ($doc->canEditQty())
                <label class="lbl">Miqdor ({{ $item->unit }})<span class="req"> *</span>
                    <input class="field" type="number" step="any" min="0.001" wire:model="qty" required autofocus autocomplete="off" @kb>
                </label>
            @endif
            @if ($doc->canEditPrice())
                <label class="lbl">Narx<span class="req"> *</span>
                    <input class="field" type="number" step="any" min="0" wire:model="price" required autocomplete="off" @kb>
                </label>
            @endif
            @if ($doc->tracksProduction())
                <label class="lbl">Ishlab chiqarilgan sana <small>(bo‘sh bo‘lishi mumkin)</small>
                    <input class="field" type="date" wire:model.live="production_date" max="{{ now()->toDateString() }}">
                </label>
                @if ($expiry)<div class="sub">Yaroqlilik muddati: {{ $expiry }}</div>@endif
            @endif

            @if ($stocks->isNotEmpty())
                <div class="sub">Qoldiq (Adines):</div>
                @foreach ($stocks as $st)
                    <div class="kv"><span>{{ $st->name }}</span><span>{{ $st->qty + 0 }} {{ $item->unit }}</span></div>
                @endforeach
            @endif

            @if ($lines->isNotEmpty() && ! $doc->isPriceCheck())
                <div class="sub mt-3.5">Hujjatda allaqachon:</div>
                @foreach ($lines as $l)
                    <div class="kv"><span>{{ $l->production_date?->format('d.m.Y') ?? 'sanasiz' }}</span><span>{{ $l->qty + 0 }} {{ $item->unit }}</span></div>
                @endforeach
            @endif
        </form>

        <x-slot:footer>
            <div class="footer"><button class="btn green" form="entry">{{ $doc->isPriceCheck() ? 'Tasdiqlash' : 'Qo‘shish' }}</button></div>
        </x-slot:footer>
    </x-pda.page>
</div>