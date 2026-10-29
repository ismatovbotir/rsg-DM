{{-- Shared item picker (documents and directory). --}}
@section('title', $title)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ $back }}'">
    <x-pda.page :title="$title" :back="$back" :notice="$notice" :failure="$failure">
        <div class="pad">
            <input class="field" wire:model.live.debounce.250ms="q" placeholder="Qidirish: nomi, kod, artikul, shtrix-kod" autocomplete="off" @kb autofocus x-init="$el.focus()">
        </div>

        <ul class="list">
            @forelse ($items as $item)
                <li wire:key="item-{{ $item->id }}">
                    <button type="button" class="row" wire:click="pick({{ $item->id }})">
                        <span class="label">{{ $item->name }}<span class="sub">{{ $item->code }}@if ($item->article) · {{ $item->article }}@endif · {{ $item->unit }}</span>@if ($item->stocks_sum_qty !== null)<span class="sub">Qoldiq: {{ $item->stocks_sum_qty + 0 }}</span>@endif</span>
                        @if (isset($added[$item->id]))<span class="badge completed">{{ $added[$item->id] + 0 }}</span>@endif
                        <strong>{{ number_format($item->shop_price ?? $item->price, 0, '.', ' ') }}</strong>
                    </button>
                </li>
            @empty
                <li class="placeholder">Topilmadi.</li>
            @endforelse
        </ul>
    </x-pda.page>
</div>