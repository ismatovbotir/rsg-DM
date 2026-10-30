@section('title', 'Tovarlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <input wire:model.live.debounce.300ms="q" placeholder="{{ __('Qidirish: kod, shtrix-kod, nomi') }}" size="34">
        <span class="grow"></span>
    </div>

    <table>
        <thead><tr><th>{{ __('Kod') }}</th><th>{{ __('Artikul') }}</th><th>{{ __('Shtrix-kod') }}</th><th>{{ __('Nomi') }}</th><th>{{ __('Birlik') }}</th><th>{{ __('Guruh') }}</th><th class="num">{{ __('Narx') }}</th></tr></thead>
        <tbody>
            @forelse ($items as $item)
                <tr wire:key="item-{{ $item->id }}">
                    <td>{{ $item->code }}</td>
                    <td>{{ $item->article }}</td>
                    <td>{{ $item->barcode }}</td>
                    <td><a class="lnk" href="{{ route('admin.items.edit', $item) }}">{{ $item->name }}</a></td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ $item->group?->path }}</td>
                    <td class="num">{{ number_format($item->price, 2, '.', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Tovarlar yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $items])
</div>
