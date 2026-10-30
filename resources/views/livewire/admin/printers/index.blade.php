@section('title', 'Printerlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>{{ __('LAN va Bluetooth printerlar. Narx yorliqlarini bitta printer chop etadi; PDA ga alohida printer biriktirish mumkin.') }}</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.printers.create') }}">{{ __('+ Yangi printer') }}</a>
    </div>

    <table>
        <thead><tr><th>{{ __('Nomi') }}</th><th>{{ __('Manzil') }}</th><th>{{ __('Yorliq o‘lchami') }}</th><th>{{ __('Narx yorliqlari') }}</th><th class="num">{{ __('PDA lar') }}</th><th>{{ __('Holat') }}</th><th></th></tr></thead>
        <tbody>
            @forelse ($printers as $printer)
                <tr wire:key="printer-{{ $printer->id }}">
                    <td><a class="lnk" href="{{ route('admin.printers.edit', $printer) }}">{{ $printer->name }}</a></td>
                    <td>{{ $printer->address }}</td>
                    <td>{{ $printer->label_width_mm }} × {{ $printer->label_height_mm }} {{ __('mm') }}</td>
                    <td>@if ($printer->is_price_tag)<span class="badge completed">{{ __('Asosiy') }}</span>@endif</td>
                    <td class="num">{{ $deviceCounts[$printer->id] ?? 0 }}</td>
                    <td><span class="badge {{ $printer->is_active ? 'completed' : 'error' }}">{{ $printer->is_active ? 'Faol' : 'Nofaol' }}</span></td>
                    <td class="text-right">
                        @unless ($printer->isBluetooth())<button class="btn sm gray" wire:click="test({{ $printer->id }})" wire:loading.attr="disabled">{{ __('Sinov') }}</button>@endunless
                        <button class="btn sm red" wire:click="delete({{ $printer->id }})" wire:confirm="{{ __('Printer o‘chirilsinmi?') }}">{{ __('O‘chirish') }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Hali printer yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>