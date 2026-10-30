{{-- Item entry window: qty, price and production date for the picked or scanned item (or the shelf price of a price check). --}}
@section('title', $item->name)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('docs.show', $doc->id) }}'">
    <x-pda.page :notice="$notice" :failure="$failure">
        <x-slot:header>
            <a class="hbtn" href="{{ route('docs.show', $doc->id) }}" aria-label="{{ __('Orqaga') }}">←</a>
            <span class="title">{{ $item->name }}<small class="block text-xs font-normal opacity-80">{{ $doc->number }}</small></span>
        </x-slot:header>

        <form id="entry" class="pad" wire:submit="save">
            <div class="mb-3">
                <strong>{{ $item->name }}</strong>
                @if ($lineId)<span class="badge">{{ __('Tahrirlash') }}</span> @endif<span class="sub">{{ $item->code }}@if ($item->article) · {{ $item->article }}@endif · {{ $item->unit }}@if ($item->shelf_life_days !== null) {{ __('· yaroqlilik') }} {{ $item->shelf_life_days }} {{ __('kun') }} @endif</span>
            </div>

            @if ($doc->isPriceCheck())
                <div class="kv"><span>{{ __('Tizimdagi narx') }}</span><span class="text-3xl font-bold">{{ number_format($priceSystem, 0, '.', ' ') }}</span></div>
                <div class="sub mb-2">{{ __('Javondagi yorliqdagi narx shu narxga tengmi?') }}</div>
                <label class="lbl">{{ __('Javondagi narx') }} <small>{{ __('(ixtiyoriy, noto‘g‘ri bo‘lsa)') }}</small>
                    <input class="field" type="number" step="any" min="0" wire:model="shelf_price" autocomplete="off" @kb>
                </label>
                @error('shelf_price')<div class="flash error !mx-0">{{ $message }}</div>@enderror
                <div class="sub">{{ __('Noto‘g‘ri bo‘lsa, yangi yorliq navbatga qo‘shiladi va tekshiruv oxirida chop etiladi.') }}</div>
            @endif
            @if ($doc->canEditQty())
                <label class="lbl">{{ __('Miqdor (') }}{{ $item->unit }})<span class="req"> *</span>
                    <input class="field" type="number" step="any" min="0.001" wire:model="qty" required autofocus autocomplete="off" @kb>
                </label>
            @endif
            @if ($doc->canEditPrice())
                <label class="lbl">{{ __('Narx') }}<span class="req"> *</span>
                    <input class="field" type="number" step="any" min="0" wire:model="price" required autocomplete="off" @kb>
                </label>
            @endif
            @if ($doc->tracksProduction())
                <label class="lbl">{{ __('Ishlab chiqarilgan sana') }} <small>{{ __('(bo‘sh bo‘lishi mumkin)') }}</small>
                    <input class="field" type="date" wire:model.live="production_date" max="{{ now()->toDateString() }}">
                </label>
                @if ($expiry)<div class="sub">{{ __('Yaroqlilik muddati:') }} {{ $expiry }}</div>@endif
            @endif

            @if ($stocks->isNotEmpty())
                <div class="sub">{{ __('Qoldiq (Adines):') }}</div>
                @foreach ($stocks as $st)
                    <div class="kv"><span>{{ $st->name }}</span><span>{{ $st->qty + 0 }} {{ $item->unit }}</span></div>
                @endforeach
            @endif

            @if ($lines->isNotEmpty() && ! $doc->isPriceCheck())
                <div class="sub mt-3.5">{{ __('Hujjatda allaqachon:') }}</div>
                @foreach ($lines as $l)
                    <div class="kv"><span>{{ $l->production_date?->format('d.m.Y') ?? 'sanasiz' }}</span><span>{{ $l->qty + 0 }} {{ $item->unit }}</span></div>
                @endforeach
            @endif
        </form>

        <x-slot:footer>
            @if ($doc->isPriceCheck())
                <div class="footer grid grid-cols-2 gap-2">
                    <button type="button" class="btn red" wire:click="verdict('wrong')">{{ __('Noto‘g‘ri') }}</button>
                    <button type="button" class="btn green" wire:click="verdict('ok')">{{ __('To‘g‘ri') }}</button>
                </div>
            @else
                <div class="footer"><button class="btn green" form="entry">{{ $lineId ? 'Saqlash' : 'Qo‘shish' }}</button></div>
            @endif
        </x-slot:footer>
    </x-pda.page>
</div>