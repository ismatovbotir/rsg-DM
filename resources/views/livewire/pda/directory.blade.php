@section('title', 'Ma’lumotnomalar')

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page title="Ma’lumotnomalar" :back="route('menu')" :notice="$notice" :failure="$failure">
        @include('livewire.pda._scanbar', ['submit' => 'search', 'model' => 'q', 'browse' => route('browse.directory')])

        <ul class="list">
            @forelse ($items as $item)
                <li class="row" wire:key="item-{{ $item->id }}">
                    <span class="label">
                        {{ $item->name }}
                        <span class="sub">{{ $item->code }}@if ($item->article) · {{ $item->article }}@endif · {{ $item->barcode }} · {{ $item->unit }}</span>
                        @if ($item->stocks_sum_qty !== null)<span class="sub">Qoldiq: {{ $item->stocks_sum_qty + 0 }}</span>@endif
                    </span>
                    <strong>{{ number_format($item->price, 0, '.', ' ') }}</strong>
                </li>
            @empty
                @if (filled($q))<li class="placeholder">Topilmadi.</li>@endif
            @endforelse
        </ul>
    </x-pda.page>
</div>