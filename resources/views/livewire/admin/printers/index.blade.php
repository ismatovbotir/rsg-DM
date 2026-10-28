@section('title', 'Printerlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>Tarmoq (ethernet / LAN) printerlari. Narx yorliqlarini bitta printer chop etadi; PDA ga alohida printer biriktirish mumkin.</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.printers.create') }}">+ Yangi printer</a>
    </div>

    <table>
        <thead><tr><th>Nomi</th><th>Manzil</th><th>Yorliq o‘lchami</th><th>Narx yorliqlari</th><th class="num">PDA lar</th><th>Holat</th><th></th></tr></thead>
        <tbody>
            @forelse ($printers as $printer)
                <tr wire:key="printer-{{ $printer->id }}">
                    <td>{{ $printer->name }}</td>
                    <td>{{ $printer->address }}</td>
                    <td>{{ $printer->label_width_mm }} × {{ $printer->label_height_mm }} mm</td>
                    <td>@if ($printer->is_price_tag)<span class="badge completed">Asosiy</span>@endif</td>
                    <td class="num">{{ $deviceCounts[$printer->id] ?? 0 }}</td>
                    <td><span class="badge {{ $printer->is_active ? 'completed' : 'error' }}">{{ $printer->is_active ? 'Faol' : 'Nofaol' }}</span></td>
                    <td class="text-right">
                        <button class="btn gray" wire:click="test({{ $printer->id }})" wire:loading.attr="disabled">Sinov</button>
                        <a class="btn gray" href="{{ route('admin.printers.edit', $printer) }}">Tahrirlash</a>
                        <button class="btn red" wire:click="delete({{ $printer->id }})" wire:confirm="Printer o‘chirilsinmi?">O‘chirish</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Hali printer yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>