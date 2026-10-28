@section('title', 'Narx yorliqlari')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>Chop etish navbatida: <strong>{{ $queue->count() }}</strong></span>
        <span class="grow"></span>
        @if ($queue->isNotEmpty())
            @if ($printer)
                <button class="btn" wire:click="send" wire:loading.attr="disabled">Printerga yuborish ({{ $printer->name }})</button>
            @endif
            <a class="btn gray" href="{{ route('admin.pricetags.print') }}" target="_blank">Brauzerda chop etish</a>
            <button class="btn gray" wire:click="markPrinted" wire:confirm="Navbatdagi barcha yorliqlar chop etilgan deb belgilansinmi?">Chop etilgan deb belgilash</button>
        @endif
    </div>

    <table>
        <thead><tr><th>Kod</th><th>Nomi</th><th class="num">Narx</th><th>Operator</th><th>Qo‘shilgan</th><th></th></tr></thead>
        <tbody>
            @forelse ($queue as $check)
                <tr wire:key="tag-{{ $check->id }}">
                    <td>{{ $check->item->code }}</td>
                    <td>{{ $check->item->name }}</td>
                    <td class="num">{{ number_format($check->item->price, 0, '.', ' ') }}</td>
                    <td>{{ $check->user?->name }}</td>
                    <td>{{ $check->created_at->format('d.m.Y H:i') }}</td>
                    <td class="text-right"><button class="btn red" wire:click="remove({{ $check->id }})">Olib tashlash</button></td>
                </tr>
            @empty
                <tr><td colspan="6">Navbat bo‘sh.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>