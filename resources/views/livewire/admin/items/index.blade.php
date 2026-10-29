@section('title', 'Tovarlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <input wire:model.live.debounce.300ms="q" placeholder="Qidirish: kod, shtrix-kod, nomi" size="34">
        <span class="grow"></span>
    </div>

    <table>
        <thead><tr><th>Kod</th><th>Artikul</th><th>Shtrix-kod</th><th>Nomi</th><th>Birlik</th><th>Guruh</th><th class="num">Narx</th></tr></thead>
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
                <tr><td colspan="7">Tovarlar yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $items])
</div>
