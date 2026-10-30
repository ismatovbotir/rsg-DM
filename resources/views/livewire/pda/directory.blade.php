@section('title', 'Mahsulotlar')

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page title="{{ __('Mahsulotlar') }}" :back="route('menu')" :notice="$notice" :failure="$failure">
        @include('livewire.pda._scanbar', ['submit' => 'search', 'model' => 'q', 'browse' => route('browse.directory')])

        <ul class="list">
            @forelse ($items as $item)
                <li wire:key="item-{{ $item->id }}"><a class="row" href="{{ route('item', $item->id) }}">
                    <span class="label">
                        {{ $item->name }}
                        <span class="sub">{{ $item->code }}@if ($item->article) · {{ $item->article }}@endif · {{ $item->barcode }} · {{ $item->unit }}</span>
                        @if ($item->stocks_sum_qty !== null)<span class="sub">{{ __('Qoldiq:') }} {{ $item->stocks_sum_qty + 0 }}</span>@endif
                    </span>
                    <strong>{{ number_format($item->shop_price ?? $item->price, 0, '.', ' ') }}</strong>
                </a></li>
            @empty
                @if (filled($q))<li class="placeholder">{{ __('Topilmadi.') }}</li>@endif
            @endforelse
        </ul>
    </x-pda.page>
</div>