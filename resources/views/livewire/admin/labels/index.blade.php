@section('title', 'Yorliq dizayneri')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>{{ __('Narx yorliqlari dizaynlari. «Asosiy» dizayn navbatdagi narx yorliqlarini chop etishda ishlatiladi.') }}</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.labels.create') }}">{{ __('+ Yangi dizayn') }}</a>
    </div>

    <table>
        <thead><tr><th>{{ __('Nomi') }}</th><th>{{ __('O‘lcham') }}</th><th>{{ __('Burilish') }}</th><th class="num">{{ __('Elementlar') }}</th><th>{{ __('Asosiy') }}</th><th></th></tr></thead>
        <tbody>
            @forelse ($templates as $t)
                <tr wire:key="label-{{ $t->id }}">
                    <td><a class="lnk" href="{{ route('admin.labels.edit', $t->id) }}">{{ $t->name }}</a></td>
                    <td>{{ $t->width_mm + 0 }} × {{ $t->height_mm + 0 }} {{ __('mm ·') }} {{ $t->dpi }} {{ __('dpi') }}</td>
                    <td>{{ $t->rotation }}°</td>
                    <td class="num">{{ count($t->elements ?? []) }}</td>
                    <td>
                        @if ($t->is_default)<span class="badge completed">{{ __('Asosiy') }}</span>
                        @else<button class="btn sm gray" wire:click="makeDefault({{ $t->id }})">{{ __('Asosiy qilish') }}</button>@endif
                    </td>
                    <td class="text-right">
                        <button class="btn sm red" wire:click="delete({{ $t->id }})" wire:confirm="{{ __('Dizayn o‘chirilsinmi?') }}">{{ __('O‘chirish') }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('Hali dizayn yo‘q. Dizayn bo‘lmasa oddiy yorliq chop etiladi.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>